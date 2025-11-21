<?php
/**
 * Admin dashboard page
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Page {

    /**
     * Initialize admin page
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Site Performance Tracker', 'perfaudit-pro'),
            __('Site Performance Tracker', 'perfaudit-pro'),
            'manage_options',
            'perfaudit-pro',
            array(__CLASS__, 'render_page'),
            'dashicons-performance',
            30
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_admin_assets($hook) {
        $perfaudit_pages = array(
            'toplevel_page_perfaudit-pro',
            'perfaudit-pro_page_perfaudit-pro-settings',
            'perfaudit-pro_page_perfaudit-pro-rules',
            'perfaudit-pro_page_perfaudit-pro-scheduled',
            'perfaudit-pro_page_perfaudit-pro-notifications',
            'perfaudit-pro_page_perfaudit-pro-budgets',
        );

        if (!in_array($hook, $perfaudit_pages)) {
            return;
        }

        wp_enqueue_style(
            'perfaudit-pro-admin',
            PERFAUDIT_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PERFAUDIT_PRO_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            PERFAUDIT_PRO_PLUGIN_URL . 'assets/vendor/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'perfaudit-pro-admin',
            PERFAUDIT_PRO_PLUGIN_URL . 'assets/js/admin.js',
            array('chart-js', 'jquery'),
            PERFAUDIT_PRO_VERSION,
            true
        );

        wp_localize_script('perfaudit-pro-admin', 'perfauditPro', array(
            'apiUrl' => rest_url('perfaudit-pro/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'workerNonce' => wp_create_nonce('perfaudit_worker'),
            'exportNonce' => wp_create_nonce('perfaudit_export'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ));
    }

    /**
     * Render admin page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'perfaudit-pro'));
        }

        include PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }
}

