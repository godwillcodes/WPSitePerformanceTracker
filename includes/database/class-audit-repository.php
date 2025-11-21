<?php
/**
 * Audit repository for database operations
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Database
 */

namespace PerfAuditPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Audit_Repository {

    /**
     * Clear audit cache group
     *
     * Compatibility wrapper for wp_cache_flush_group() which requires WordPress 6.1+
     *
     * @return void
     */
    private static function clear_audit_cache(): void {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('perfaudit_audits');
        } else {
            // Fallback for WordPress < 6.1: increment cache version
            $cache_version = get_option('perfaudit_audit_cache_version', 1);
            update_option('perfaudit_audit_cache_version', $cache_version + 1, false);
        }
    }

    /**
     * Create a synthetic audit record
     *
     * @param string $url URL to audit
     * @param string $audit_type Type of audit (default: 'lighthouse')
     * @param string $device Device type (default: 'desktop')
     * @return int|\WP_Error Audit ID on success, WP_Error on failure
     */
    public function create_synthetic_audit(string $url, string $audit_type = 'lighthouse', string $device = 'desktop') {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        // Validate inputs
        $validated_url = \PerfAuditPro\Utils\Validator::validate_url($url);
        if ($validated_url === null) {
            return new \WP_Error('invalid_url', 'Invalid URL provided', array('status' => 400));
        }

        $validated_audit_type = \PerfAuditPro\Utils\Validator::validate_audit_type($audit_type);
        $validated_device = \PerfAuditPro\Utils\Validator::validate_device($device);

        global $wpdb;
        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        // Ensure table exists and is up to date (in case it wasn't created during activation or needs migration)
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-schema.php';
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, DDL statement doesn't support placeholders
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            \PerfAuditPro\Database\Schema::create_tables();
        } else {
            // Check if device column exists, if not run migration
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, DDL statement doesn't support placeholders
            $columns = $wpdb->get_col("DESCRIBE $table_name");
            if (!in_array('device', $columns, true)) {
                \PerfAuditPro\Database\Schema::migrate_tables();
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Repository needs direct queries for data operations
        $result = $wpdb->insert(
            $table_name,
            array(
                'url' => $validated_url,
                'audit_type' => $validated_audit_type,
                'status' => 'pending',
                'device' => $validated_device,
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            $db_error = $wpdb->last_error ?: 'Unknown database error';
            \PerfAuditPro\Utils\Logger::error('Failed to create audit record', array(
                'url' => $validated_url,
                'error' => $db_error,
                'table' => $table_name,
            ));
            return new \WP_Error('db_error', 'Failed to create audit record: ' . $db_error, array('status' => 500));
        }

        // Clear cache when new audit is created
        self::clear_audit_cache();

        return (int) $wpdb->insert_id;
    }

    /**
     * Update audit with results
     *
     * @param int $audit_id Audit ID
     * @param array<string, mixed> $results Audit results
     * @return bool|\WP_Error True on success, WP_Error on failure
     */
    public function update_audit_results(int $audit_id, array $results) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        $data = array(
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
        );

        $format = array('%s', '%s');

        if (isset($results['performance_score'])) {
            $data['performance_score'] = floatval($results['performance_score']);
            $format[] = '%f';
        }

        if (isset($results['first_contentful_paint'])) {
            $data['first_contentful_paint'] = floatval($results['first_contentful_paint']);
            $format[] = '%f';
        }

        if (isset($results['largest_contentful_paint'])) {
            $data['largest_contentful_paint'] = floatval($results['largest_contentful_paint']);
            $format[] = '%f';
        }

        if (isset($results['total_blocking_time'])) {
            $data['total_blocking_time'] = floatval($results['total_blocking_time']);
            $format[] = '%f';
        }

        if (isset($results['cumulative_layout_shift'])) {
            $data['cumulative_layout_shift'] = floatval($results['cumulative_layout_shift']);
            $format[] = '%f';
        }

        if (isset($results['speed_index'])) {
            $data['speed_index'] = floatval($results['speed_index']);
            $format[] = '%f';
        }

        if (isset($results['time_to_interactive'])) {
            $data['time_to_interactive'] = floatval($results['time_to_interactive']);
            $format[] = '%f';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Repository needs direct queries for data operations
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => absint($audit_id)),
            $format,
            array('%d')
        );

        if ($result === false) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::error('Failed to update audit results', array(
                'audit_id' => $audit_id,
                'error' => $wpdb->last_error,
            ));
            return new \WP_Error('db_error', 'Failed to update audit results', array('status' => 500));
        }

        if (isset($results['lighthouse_json'])) {
            $this->store_lighthouse_json($audit_id, $results['lighthouse_json']);
        }

        // Clear caches when audit results are updated
        self::clear_audit_cache();
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/class-scorecard.php';
        \PerfAuditPro\Admin\Scorecard::clear_cache();

        return true;
    }

    /**
     * Store Lighthouse JSON
     *
     * @param int $audit_id Audit ID
     * @param string|array<string, mixed> $json JSON string or array
     * @return bool|\WP_Error True on success, WP_Error on failure
     */
    public function store_lighthouse_json(int $audit_id, $json) {
        global $wpdb;

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/security/class-sanitizer.php';
        $sanitized_json = \PerfAuditPro\Security\Sanitizer::sanitize_json($json);

        if ($sanitized_json === false) {
            return new \WP_Error('invalid_json', 'Invalid JSON format', array('status' => 400));
        }

        $table_name = $wpdb->prefix . 'perfaudit_lighthouse_json';
        $audit_id = absint($audit_id);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Repository needs direct queries for data operations
        $result = $wpdb->insert(
            $table_name,
            array(
                'audit_id' => $audit_id,
                'full_json' => $sanitized_json,
            ),
            array('%d', '%s')
        );

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to store Lighthouse JSON', array('status' => 500));
        }

        return true;
    }

    /**
     * Get recent audits
     *
     * @param string|null $url Optional URL filter
     * @param int $limit Number of results (default: 10, max: 1000)
     * @param array<string, mixed> $filters Additional filters (status, date_from, date_to, search)
     * @return array<int, array<string, mixed>> Array of audit records
     */
    public function get_recent_audits(?string $url = null, int $limit = 10, array $filters = array()): array {
        global $wpdb;

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $limit = \PerfAuditPro\Utils\Validator::validate_positive_int($limit, 10, 1, 1000);

        // Generate cache key based on parameters and cache version (for compatibility with WordPress < 6.1)
        $cache_version = get_option('perfaudit_audit_cache_version', 1);
        $cache_key = 'perfaudit_recent_audits_' . md5(serialize(array($url, $limit, $filters, $cache_version)));
        $cache_group = 'perfaudit_audits';
        $cache_ttl = 300; // 5 minutes

        // Try to get from cache first
        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached !== false) {
            return $cached;
        }

        $where_conditions = array();
        $where_values = array();

        if ($url !== null && $url !== '') {
            $validated_url = \PerfAuditPro\Utils\Validator::validate_url($url);
            if ($validated_url !== null) {
                $where_conditions[] = 'url = %s';
                $where_values[] = $validated_url;
            }
        }

        if (!empty($filters['status']) && \PerfAuditPro\Utils\Validator::is_valid_status($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = strtolower(sanitize_text_field($filters['status']));
        }

        if (!empty($filters['search'])) {
            $where_conditions[] = 'url LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
        }

        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'DATE(created_at) >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'DATE(created_at) <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }

        $query = 'SELECT * FROM `' . esc_sql($table_name) . '`';
        if (!empty($where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $where_conditions);
        }
        $query .= ' ORDER BY created_at DESC LIMIT %d';
        
        if (!empty($where_values)) {
            $where_values[] = $limit;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is built safely, then prepared
            $prepared_query = $wpdb->prepare($query, $where_values);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is built safely, then prepared
            $prepared_query = $wpdb->prepare($query, $limit);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- Query is prepared via $wpdb->prepare(), caching implemented
        $results = $wpdb->get_results($prepared_query, ARRAY_A);

        // Cache the results
        wp_cache_set($cache_key, $results, $cache_group, $cache_ttl);

        return $results;
    }

    /**
     * Delete audits by IDs
     *
     * @param array<int|string> $audit_ids Array of audit IDs
     * @return int Number of deleted audits (0 on failure or empty input)
     */
    public function delete_audits(array $audit_ids): int {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $valid_ids = \PerfAuditPro\Utils\Validator::validate_audit_ids($audit_ids);
        
        if (empty($valid_ids)) {
            return 0;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        
        // Limit to prevent memory issues with large arrays
        if (count($valid_ids) > 1000) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('Too many audit IDs provided for deletion', array(
                'count' => count($valid_ids),
            ));
            $valid_ids = array_slice($valid_ids, 0, 1000);
        }

        $placeholders = implode(',', array_fill(0, count($valid_ids), '%d'));
        $query = 'DELETE FROM `' . esc_sql($table_name) . '` WHERE id IN (' . $placeholders . ')';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is built safely, then prepared
        $prepared_query = $wpdb->prepare($query, $valid_ids);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- Query is prepared via $wpdb->prepare()
        $deleted = $wpdb->query($prepared_query);
        
        // Clear caches when audits are deleted
        if ($deleted > 0) {
            self::clear_audit_cache();
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/class-scorecard.php';
            \PerfAuditPro\Admin\Scorecard::clear_cache();
        }
        
        if ($deleted === false) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::error('Failed to delete audits', array(
                'error' => $wpdb->last_error,
            ));
            return 0;
        }

        return (int) $deleted;
    }
}

