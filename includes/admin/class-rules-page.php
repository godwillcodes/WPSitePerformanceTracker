<?php
/**
 * Rules configuration page
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Rules_Page {

    /**
     * Initialize rules page
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_rules_menu'));
        add_action('wp_ajax_perfaudit_save_rule', array(__CLASS__, 'save_rule'));
        add_action('wp_ajax_perfaudit_delete_rule', array(__CLASS__, 'delete_rule'));
        add_action('wp_ajax_perfaudit_toggle_rule', array(__CLASS__, 'toggle_rule'));
    }

    /**
     * Add rules submenu
     */
    public static function add_rules_menu() {
        add_submenu_page(
            'perfaudit-pro',
            __('Performance Rules', 'perfaudit-pro'),
            __('Rules', 'perfaudit-pro'),
            'manage_options',
            'perfaudit-pro-rules',
            array(__CLASS__, 'render_page')
        );
    }

    /**
     * Get all rules
     */
    public static function get_rules() {
        $rules = get_option('perfaudit_pro_rules', array());
        if (empty($rules)) {
            $rules = self::get_default_rules();
            update_option('perfaudit_pro_rules', $rules);
        }
        return $rules;
    }

    /**
     * Get default rules
     */
    private static function get_default_rules() {
        return array(
            array(
                'id' => 'rule_1',
                'name' => 'Performance Score',
                'metric' => 'performance_score',
                'threshold' => get_option('perfaudit_pro_threshold_performance_score', 90),
                'operator' => 'lt',
                'enforcement' => 'hard',
                'enabled' => true,
            ),
            array(
                'id' => 'rule_2',
                'name' => 'LCP Threshold',
                'metric' => 'largest_contentful_paint',
                'threshold' => get_option('perfaudit_pro_threshold_lcp', 2500),
                'operator' => 'gt',
                'enforcement' => 'hard',
                'enabled' => true,
            ),
            array(
                'id' => 'rule_3',
                'name' => 'CLS Threshold',
                'metric' => 'cumulative_layout_shift',
                'threshold' => get_option('perfaudit_pro_threshold_cls', 0.1),
                'operator' => 'gt',
                'enforcement' => 'hard',
                'enabled' => true,
            ),
        );
    }

    /**
     * Save rule
     */
    public static function save_rule() {
        check_ajax_referer('perfaudit_rules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $rule_data = json_decode(stripslashes($_POST['rule']), true);
        
        if (empty($rule_data['id'])) {
            $rule_data['id'] = 'rule_' . time();
        }

        $rules = self::get_rules();
        $found = false;
        
        foreach ($rules as $key => $rule) {
            if ($rule['id'] === $rule_data['id']) {
                $rules[$key] = $rule_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $rules[] = $rule_data;
        }

        update_option('perfaudit_pro_rules', $rules);
        wp_send_json_success(array('message' => 'Rule saved', 'rule' => $rule_data));
    }

    /**
     * Delete rule
     */
    public static function delete_rule() {
        check_ajax_referer('perfaudit_rules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $rule_id = sanitize_text_field($_POST['rule_id']);
        $rules = self::get_rules();

        $rules = array_filter($rules, function($rule) use ($rule_id) {
            return $rule['id'] !== $rule_id;
        });

        update_option('perfaudit_pro_rules', array_values($rules));
        wp_send_json_success(array('message' => 'Rule deleted'));
    }

    /**
     * Toggle rule
     */
    public static function toggle_rule() {
        check_ajax_referer('perfaudit_rules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $rule_id = sanitize_text_field($_POST['rule_id']);
        $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);
        $rules = self::get_rules();

        foreach ($rules as $key => $rule) {
            if ($rule['id'] === $rule_id) {
                $rules[$key]['enabled'] = $enabled;
                break;
            }
        }

        update_option('perfaudit_pro_rules', $rules);
        wp_send_json_success(array('message' => 'Rule updated'));
    }

    /**
     * Render rules page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'perfaudit-pro'));
        }

        include PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/views/rules.php';
    }
}

