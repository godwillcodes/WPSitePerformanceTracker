<?php
/**
 * Data cleanup utility
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Utils
 */

namespace PerfAuditPro\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class Data_Cleanup {

    /**
     * Initialize cleanup
     */
    public static function init() {
        add_action('wp_ajax_perfaudit_cleanup_now', array(__CLASS__, 'handle_cleanup_now'));
        
        // Schedule daily cleanup if auto cleanup is enabled
        if (get_option('perfaudit_pro_auto_cleanup', true)) {
            if (!wp_next_scheduled('perfaudit_pro_daily_cleanup')) {
                wp_schedule_event(time(), 'daily', 'perfaudit_pro_daily_cleanup');
            }
            add_action('perfaudit_pro_daily_cleanup', array(__CLASS__, 'run_cleanup'));
        } else {
            $timestamp = wp_next_scheduled('perfaudit_pro_daily_cleanup');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'perfaudit_pro_daily_cleanup');
            }
        }
    }

    /**
     * Handle manual cleanup request
     */
    public static function handle_cleanup_now() {
        check_ajax_referer('perfaudit_worker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $result = self::run_cleanup();
        
        wp_send_json_success(array(
            'message' => sprintf(
                'Cleanup completed. Deleted %d audit(s) and %d RUM metric record(s).',
                $result['audits_deleted'],
                $result['rum_deleted']
            ),
            'deleted' => $result,
        ));
    }

    /**
     * Run cleanup based on retention settings
     *
     * @return array Results with counts of deleted records
     */
    public static function run_cleanup(): array {
        global $wpdb;

        $audit_retention_days = absint(get_option('perfaudit_pro_audit_retention_days', 90));
        $rum_retention_days = absint(get_option('perfaudit_pro_rum_retention_days', 90));

        $audit_cutoff = date('Y-m-d H:i:s', strtotime("-{$audit_retention_days} days"));
        $rum_cutoff = date('Y-m-d', strtotime("-{$rum_retention_days} days"));

        // Delete old audits
        $audits_table = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $audits_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $audits_table WHERE created_at < %s",
                $audit_cutoff
            )
        );

        // Delete old RUM metrics
        $rum_table = $wpdb->prefix . 'perfaudit_rum_metrics';
        $rum_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $rum_table WHERE date < %s",
                $rum_cutoff
            )
        );

        // Clear scorecard cache after cleanup
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/class-scorecard.php';
        \PerfAuditPro\Admin\Scorecard::clear_cache();

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
        \PerfAuditPro\Utils\Logger::info('Data cleanup completed', array(
            'audits_deleted' => $audits_deleted,
            'rum_deleted' => $rum_deleted,
            'audit_retention_days' => $audit_retention_days,
            'rum_retention_days' => $rum_retention_days,
        ));

        return array(
            'audits_deleted' => (int) $audits_deleted,
            'rum_deleted' => (int) $rum_deleted,
        );
    }
}

