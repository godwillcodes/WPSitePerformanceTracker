<?php
/**
 * Scheduled audits management
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Scheduled_Audits {

    /**
     * Initialize scheduled audits
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('wp_ajax_perfaudit_save_schedule', array(__CLASS__, 'save_schedule'));
        add_action('wp_ajax_perfaudit_delete_schedule', array(__CLASS__, 'delete_schedule'));
        add_action('perfaudit_pro_run_scheduled_audits', array(__CLASS__, 'run_scheduled_audits'));
        add_action('init', array(__CLASS__, 'schedule_events'));
    }

    /**
     * Add scheduled audits menu
     */
    public static function add_menu() {
        add_submenu_page(
            'perfaudit-pro',
            __('Scheduled Audits', 'perfaudit-pro'),
            __('Scheduled Audits', 'perfaudit-pro'),
            'manage_options',
            'perfaudit-pro-scheduled',
            array(__CLASS__, 'render_page')
        );
    }

    /**
     * Schedule cron events
     */
    public static function schedule_events() {
        if (!wp_next_scheduled('perfaudit_pro_run_scheduled_audits')) {
            wp_schedule_event(time(), 'hourly', 'perfaudit_pro_run_scheduled_audits');
        }
    }

    /**
     * Run scheduled audits
     */
    public static function run_scheduled_audits() {
        $schedules = get_option('perfaudit_pro_scheduled_audits', array());
        
        foreach ($schedules as $schedule) {
            if (!$schedule['enabled']) {
                continue;
            }

            $last_run = isset($schedule['last_run']) ? strtotime($schedule['last_run']) : 0;
            $interval = self::get_interval_seconds($schedule['frequency']);
            $next_run = $last_run + $interval;

            if (time() >= $next_run) {
                self::execute_schedule($schedule);
                $schedule['last_run'] = current_time('mysql');
                update_option('perfaudit_pro_scheduled_audits', $schedules);
            }
        }
    }

    /**
     * Get interval in seconds
     */
    private static function get_interval_seconds($frequency) {
        $intervals = array(
            'hourly' => HOUR_IN_SECONDS,
            'daily' => DAY_IN_SECONDS,
            'weekly' => WEEK_IN_SECONDS,
            'monthly' => MONTH_IN_SECONDS,
        );
        return $intervals[$frequency] ?? DAY_IN_SECONDS;
    }

    /**
     * Execute scheduled audit
     */
    private static function execute_schedule($schedule) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $urls = is_array($schedule['urls']) ? $schedule['urls'] : array($schedule['urls']);
        $device = $schedule['device'] ?? 'desktop';

        foreach ($urls as $url) {
            $repository->create_synthetic_audit($url, 'lighthouse');
        }
    }

    /**
     * Save schedule
     */
    public static function save_schedule() {
        check_ajax_referer('perfaudit_schedules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $schedule_data = json_decode(stripslashes($_POST['schedule']), true);
        
        if (empty($schedule_data['id'])) {
            $schedule_data['id'] = 'schedule_' . time();
        }

        $schedules = get_option('perfaudit_pro_scheduled_audits', array());
        $found = false;
        
        foreach ($schedules as $key => $schedule) {
            if ($schedule['id'] === $schedule_data['id']) {
                $schedules[$key] = $schedule_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $schedules[] = $schedule_data;
        }

        update_option('perfaudit_pro_scheduled_audits', $schedules);
        wp_send_json_success(array('message' => 'Schedule saved'));
    }

    /**
     * Delete schedule
     */
    public static function delete_schedule() {
        check_ajax_referer('perfaudit_schedules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $schedule_id = sanitize_text_field($_POST['schedule_id']);
        $schedules = get_option('perfaudit_pro_scheduled_audits', array());

        $schedules = array_filter($schedules, function($schedule) use ($schedule_id) {
            return $schedule['id'] !== $schedule_id;
        });

        update_option('perfaudit_pro_scheduled_audits', array_values($schedules));
        wp_send_json_success(array('message' => 'Schedule deleted'));
    }

    /**
     * Render scheduled audits page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'perfaudit-pro'));
        }

        include PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/views/scheduled-audits.php';
    }
}

