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
     * @return int|\WP_Error Audit ID or error
     */
    public function create_synthetic_audit($url, $audit_type = 'lighthouse') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        $result = $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
                'audit_type' => sanitize_text_field($audit_type),
                'status' => 'pending',
            ),
            array('%s', '%s', '%s')
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
     * @return array
     */
    public function get_recent_audits($url = null, $limit = 10) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $limit = absint($limit);

        $where = '';
        if ($url) {
            $url = sanitize_url($url);
            $where = $wpdb->prepare(' WHERE url = %s', $url);
        }

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d",
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A);
    }
}

