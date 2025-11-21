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
     * Create a synthetic audit record
     *
     * @param string $url URL to audit
     * @param string $audit_type Type of audit
     * @param string $device Device type (desktop/mobile)
     * @return int|\WP_Error Audit ID or error
     */
    public function create_synthetic_audit($url, $audit_type = 'lighthouse', $device = 'desktop') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        $result = $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
                'audit_type' => sanitize_text_field($audit_type),
                'status' => 'pending',
                'device' => sanitize_text_field($device),
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create audit record', array('status' => 500));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update audit with results
     *
     * @param int $audit_id Audit ID
     * @param array $results Audit results
     * @return bool|\WP_Error
     */
    public function update_audit_results($audit_id, $results) {
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

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $audit_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update audit results', array('status' => 500));
        }

        if (isset($results['lighthouse_json'])) {
            $this->store_lighthouse_json($audit_id, $results['lighthouse_json']);
        }

        return true;
    }

    /**
     * Store Lighthouse JSON
     *
     * @param int $audit_id Audit ID
     * @param string $json JSON string
     * @return bool|\WP_Error
     */
    public function store_lighthouse_json($audit_id, $json) {
        global $wpdb;

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/security/class-sanitizer.php';
        $sanitized_json = \PerfAuditPro\Security\Sanitizer::sanitize_json($json);

        if ($sanitized_json === false) {
            return new \WP_Error('invalid_json', 'Invalid JSON format', array('status' => 400));
        }

        $table_name = $wpdb->prefix . 'perfaudit_lighthouse_json';
        $audit_id = absint($audit_id);

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
     * @param int $limit Number of results
     * @param array $filters Additional filters (status, date_from, date_to, search)
     * @return array
     */
    public function get_recent_audits($url = null, $limit = 10, $filters = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $limit = absint($limit);

        $where_conditions = array();
        $where_values = array();

        if ($url) {
            $url = sanitize_url($url);
            $where_conditions[] = 'url = %s';
            $where_values[] = $url;
        }

        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field($filters['status']);
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

        $where = '';
        if (!empty($where_conditions)) {
            $where = ' WHERE ' . implode(' AND ', $where_conditions);
            if (!empty($where_values)) {
                $where = $wpdb->prepare($where, $where_values);
            }
        }

        $query = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d";
        $query = $wpdb->prepare($query, $limit);

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Delete audits by IDs
     *
     * @param array $audit_ids Array of audit IDs
     * @return int Number of deleted audits
     */
    public function delete_audits($audit_ids) {
        global $wpdb;

        if (empty($audit_ids) || !is_array($audit_ids)) {
            return 0;
        }

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $ids = array_map('absint', $audit_ids);
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM $table_name WHERE id IN ($placeholders)",
            $ids
        );

        return $wpdb->query($query);
    }
}

