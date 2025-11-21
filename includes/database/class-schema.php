<?php
/**
 * Database schema management
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Database
 */

namespace PerfAuditPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Schema {

    /**
     * Create all database tables
     *
     * Creates all custom database tables required by the plugin.
     * Uses WordPress dbDelta for safe table creation/updates.
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        self::create_synthetic_audits_table($wpdb, $charset_collate);
        self::create_lighthouse_json_table($wpdb, $charset_collate);
        self::create_rum_metrics_table($wpdb, $charset_collate);
        
        // Run migrations to add missing columns
        self::migrate_tables();
    }

    /**
     * Migrate tables to add missing columns and indexes
     *
     * Adds columns and indexes that may have been added in schema updates.
     *
     * @return void
     */
    public static function migrate_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }

        // Check if device column exists, if not add it
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        if (!in_array('device', $columns, true)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN device varchar(20) DEFAULT 'desktop' AFTER status");
        }

        // Add composite indexes for better query performance
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'idx_status_created'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_status_created (status, created_at)");
        }

        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'idx_url_status'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_url_status (url, status)");
        }

        // Add index on completed_at for filtering completed audits
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'idx_completed_at'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_completed_at (completed_at)");
        }

        // Add indexes to RUM metrics table
        $rum_table = $wpdb->prefix . 'perfaudit_rum_metrics';
        if ($wpdb->get_var("SHOW TABLES LIKE '$rum_table'") === $rum_table) {
            $rum_indexes = $wpdb->get_results("SHOW INDEX FROM $rum_table WHERE Key_name = 'idx_date_url'");
            if (empty($rum_indexes)) {
                $wpdb->query("ALTER TABLE $rum_table ADD INDEX idx_date_url (date, url)");
            }
        }
    }

    /**
     * Create synthetic audits table
     *
     * @param \wpdb $wpdb WordPress database object
     * @param string $charset_collate Charset and collation string
     * @return void
     */
    private static function create_synthetic_audits_table(\wpdb $wpdb, string $charset_collate): void {
        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            audit_type varchar(50) NOT NULL DEFAULT 'lighthouse',
            performance_score decimal(5,2) DEFAULT NULL,
            first_contentful_paint decimal(10,2) DEFAULT NULL,
            largest_contentful_paint decimal(10,2) DEFAULT NULL,
            total_blocking_time decimal(10,2) DEFAULT NULL,
            cumulative_layout_shift decimal(10,4) DEFAULT NULL,
            speed_index decimal(10,2) DEFAULT NULL,
            time_to_interactive decimal(10,2) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            device varchar(20) DEFAULT 'desktop',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            worker_id varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY url (url),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Create Lighthouse JSON storage table
     *
     * @param \wpdb $wpdb WordPress database object
     * @param string $charset_collate Charset and collation string
     * @return void
     */
    private static function create_lighthouse_json_table(\wpdb $wpdb, string $charset_collate): void {
        $table_name = $wpdb->prefix . 'perfaudit_lighthouse_json';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id bigint(20) UNSIGNED NOT NULL,
            full_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY audit_id (audit_id),
            FOREIGN KEY (audit_id) REFERENCES {$wpdb->prefix}perfaudit_synthetic_audits(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Create RUM aggregated metrics table
     *
     * @param \wpdb $wpdb WordPress database object
     * @param string $charset_collate Charset and collation string
     * @return void
     */
    private static function create_rum_metrics_table(\wpdb $wpdb, string $charset_collate): void {
        $table_name = $wpdb->prefix . 'perfaudit_rum_metrics';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            date date NOT NULL,
            page_views int(11) UNSIGNED NOT NULL DEFAULT 0,
            avg_lcp decimal(10,2) DEFAULT NULL,
            avg_fid decimal(10,2) DEFAULT NULL,
            avg_cls decimal(10,4) DEFAULT NULL,
            avg_fcp decimal(10,2) DEFAULT NULL,
            avg_ttfb decimal(10,2) DEFAULT NULL,
            p75_lcp decimal(10,2) DEFAULT NULL,
            p75_fid decimal(10,2) DEFAULT NULL,
            p75_cls decimal(10,4) DEFAULT NULL,
            p75_fcp decimal(10,2) DEFAULT NULL,
            p75_ttfb decimal(10,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY url_date (url, date),
            KEY date (date),
            KEY url (url)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

