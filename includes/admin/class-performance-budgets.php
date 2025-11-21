<?php
/**
 * Performance budgets management
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Performance_Budgets {

    /**
     * Initialize budgets
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('wp_ajax_perfaudit_save_budget', array(__CLASS__, 'save_budget'));
        add_action('wp_ajax_perfaudit_delete_budget', array(__CLASS__, 'delete_budget'));
        add_action('perfaudit_pro_audit_completed', array(__CLASS__, 'check_budget_violations'), 10, 2);
    }

    /**
     * Add budgets menu
     */
    public static function add_menu() {
        add_submenu_page(
            'perfaudit-pro',
            __('Performance Budgets', 'perfaudit-pro'),
            __('Budgets', 'perfaudit-pro'),
            'manage_options',
            'perfaudit-pro-budgets',
            array(__CLASS__, 'render_page')
        );
    }

    /**
     * Get all budgets
     */
    public static function get_budgets() {
        return get_option('perfaudit_pro_budgets', array());
    }

    /**
     * Check budget violations
     */
    public static function check_budget_violations($audit_id, $results) {
        $budgets = self::get_budgets();
        
        foreach ($budgets as $budget) {
            if (!$budget['enabled']) continue;

            $metric_value = $results[$budget['metric']] ?? null;
            if ($metric_value === null) continue;

            if ($metric_value > $budget['limit']) {
                self::record_budget_violation($audit_id, $budget, $metric_value);
            }
        }
    }

    /**
     * Record budget violation
     */
    private static function record_budget_violation($audit_id, $budget, $actual_value) {
        $violations = get_option('perfaudit_pro_budget_violations', array());
        $violations[] = array(
            'id' => 'violation_' . time(),
            'audit_id' => $audit_id,
            'budget_id' => $budget['id'],
            'budget_name' => $budget['name'],
            'metric' => $budget['metric'],
            'limit' => $budget['limit'],
            'actual' => $actual_value,
            'timestamp' => current_time('mysql'),
        );
        update_option('perfaudit_pro_budget_violations', array_slice($violations, -500));
    }

    /**
     * Save budget
     */
    public static function save_budget() {
        check_ajax_referer('perfaudit_budgets', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $budget_data = json_decode(stripslashes($_POST['budget']), true);
        
        if (empty($budget_data['id'])) {
            $budget_data['id'] = 'budget_' . time();
        }

        $budgets = self::get_budgets();
        $found = false;
        
        foreach ($budgets as $key => $budget) {
            if ($budget['id'] === $budget_data['id']) {
                $budgets[$key] = $budget_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $budgets[] = $budget_data;
        }

        update_option('perfaudit_pro_budgets', $budgets);
        wp_send_json_success(array('message' => 'Budget saved'));
    }

    /**
     * Delete budget
     */
    public static function delete_budget() {
        check_ajax_referer('perfaudit_budgets', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $budget_id = sanitize_text_field($_POST['budget_id']);
        $budgets = self::get_budgets();

        $budgets = array_filter($budgets, function($budget) use ($budget_id) {
            return $budget['id'] !== $budget_id;
        });

        update_option('perfaudit_pro_budgets', array_values($budgets));
        wp_send_json_success(array('message' => 'Budget deleted'));
    }

    /**
     * Render budgets page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'perfaudit-pro'));
        }

        include PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/views/budgets.php';
    }
}

