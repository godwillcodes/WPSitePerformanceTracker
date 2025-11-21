<?php
/**
 * Worker Manager - Auto-configuration and management
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Worker_Manager {

    /**
     * Initialize worker manager
     */
    public static function init() {
        add_action('wp_ajax_perfaudit_start_worker', array(__CLASS__, 'start_worker'));
        add_action('wp_ajax_perfaudit_stop_worker', array(__CLASS__, 'stop_worker'));
        add_action('wp_ajax_perfaudit_check_worker', array(__CLASS__, 'check_worker_status'));
        add_action('wp_ajax_perfaudit_reset_stuck', array(__CLASS__, 'reset_stuck_audits'));
        add_action('wp_ajax_perfaudit_process_now', array(__CLASS__, 'process_now'));
        add_action('admin_init', array(__CLASS__, 'auto_configure'));
    }

    /**
     * Auto-configure worker on plugin activation
     */
    public static function auto_configure() {
        // Generate API token if not exists
        $token = get_option('perfaudit_pro_api_token');
        if (empty($token)) {
            $token = self::generate_api_token();
            update_option('perfaudit_pro_api_token', $token);
        }
    }

    /**
     * Generate secure API token
     */
    private static function generate_api_token() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Start worker process
     */
    public static function start_worker() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        // Auto-configure if needed
        self::auto_configure();

        // Start PHP worker (no Node.js needed)
        $result = self::start_worker_process();

        if ($result['success']) {
            update_option('perfaudit_worker_status', 'running');
            wp_send_json_success(array(
                'message' => 'PHP Worker started successfully (no Node.js required)',
                'pid' => null
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Stop worker process
     */
    public static function stop_worker() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/worker/class-php-worker.php';
        \PerfAuditPro\Worker\PHP_Worker::stop();

        update_option('perfaudit_worker_status', 'stopped');
        delete_option('perfaudit_worker_pid');

        wp_send_json_success(array('message' => 'Worker stopped'));
    }

    /**
     * Check worker status
     */
    public static function check_worker_status() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/worker/class-php-worker.php';
        $is_running = \PerfAuditPro\Worker\PHP_Worker::is_running();
        
        $status = $is_running ? 'running' : 'stopped';

        wp_send_json_success(array(
            'status' => $status,
            'pid' => null,
            'node_available' => true,
            'node_message' => 'PHP Worker (No Node.js required)'
        ));
    }

    /**
     * Reset stuck audits
     */
    public static function reset_stuck_audits() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/worker/class-php-worker.php';
        $count = \PerfAuditPro\Worker\PHP_Worker::reset_stuck_audits_public();

        wp_send_json_success(array(
            'message' => sprintf(
                _n(
                    '%d stuck audit reset to pending.',
                    '%d stuck audits reset to pending.',
                    $count,
                    'perfaudit-pro'
                ),
                $count
            ),
            'count' => $count
        ));
    }

    /**
     * Process audits immediately
     */
    public static function process_now() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/worker/class-php-worker.php';
        \PerfAuditPro\Worker\PHP_Worker::process_pending_audits();

        wp_send_json_success(array(
            'message' => 'Processing triggered. Audits will be processed shortly.'
        ));
    }

    /**
     * Start worker process (PHP-based, no Node.js needed)
     */
    private static function start_worker_process() {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/worker/class-php-worker.php';
        
        \PerfAuditPro\Worker\PHP_Worker::start();
        
        return array(
            'success' => true,
            'pid' => null,
            'message' => 'PHP Worker started (runs via WP-Cron)'
        );
    }
}

