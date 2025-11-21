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
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        self::create_synthetic_audits_table($wpdb, $charset_collate);
        self::create_lighthouse_json_table($wpdb, $charset_collate);
        self::create_rum_metrics_table($wpdb, $charset_collate);
    }

    /**
     * Create synthetic audits table
     *
     * @param \wpdb $wpdb WordPress database object
     * @param string $charset_collate Charset and collation
     */
    private static function create_synthetic_audits_table($wpdb, $charset_collate) {
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
     * @param string $charset_collate Charset and collation
     */
    private static function create_lighthouse_json_table($wpdb, $charset_collate) {
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
     * @param string $charset_collate Charset and collation
     */
    private static function create_rum_metrics_table($wpdb, $charset_collate) {
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

