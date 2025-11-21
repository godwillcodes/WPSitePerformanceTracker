<?php
/**
 * Audit details view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Audit_Details {

    /**
     * Initialize audit details
     */
    public static function init() {
        add_action('wp_ajax_perfaudit_get_audit_details', array(__CLASS__, 'get_audit_details'));
    }

    /**
     * Get audit details
     */
    public static function get_audit_details() {
        check_ajax_referer('perfaudit_audit_details', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $audit_id = absint($_GET['audit_id']);
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $audit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $audit_id
        ), ARRAY_A);

        if (!$audit) {
            wp_send_json_error(array('message' => 'Audit not found'));
            return;
        }

        // Get Lighthouse JSON if available
        $json_table = $wpdb->prefix . 'perfaudit_lighthouse_json';
        $lighthouse_json = $wpdb->get_var($wpdb->prepare(
            "SELECT full_json FROM $json_table WHERE audit_id = %d LIMIT 1",
            $audit_id
        ));

        $audit['lighthouse_json'] = $lighthouse_json ? json_decode($lighthouse_json, true) : null;

        wp_send_json_success($audit);
    }
}

