<?php
/**
 * Settings page
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Settings_Page {

    /**
     * Initialize settings page
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Add settings submenu
     */
    public static function add_settings_menu() {
        add_submenu_page(
            'perfaudit-pro',
            __('Settings', 'perfaudit-pro'),
            __('Settings', 'perfaudit-pro'),
            'manage_options',
            'perfaudit-pro-settings',
            array(__CLASS__, 'render_page')
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        // API Settings
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_psi_api_key');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_api_token');

        // Default Thresholds
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_lcp');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_fid');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_cls');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_fcp');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_ttfb');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_threshold_performance_score');

        // Notification Settings
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_notification_email');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_notification_enabled');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_webhook_url');

        // Worker Settings
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_worker_interval');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_worker_max_concurrent');

        // RUM Settings
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_rum_enabled');
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_rum_sample_rate');

        // Data Retention Settings
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_audit_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => function($value) {
                $value = absint($value);
                return max(7, min(365, $value)); // Clamp between 7 and 365 days
            },
            'default' => 90,
        ));
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_rum_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => function($value) {
                $value = absint($value);
                return max(7, min(365, $value)); // Clamp between 7 and 365 days
            },
            'default' => 90,
        ));
        register_setting('perfaudit_pro_settings', 'perfaudit_pro_auto_cleanup', array(
            'type' => 'boolean',
            'default' => true,
        ));
    }

    /**
     * Render settings page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'perfaudit-pro'));
        }

        include PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }
}

