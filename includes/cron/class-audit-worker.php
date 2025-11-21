<?php
/**
 * Audit worker for processing pending audits
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Cron
 */

namespace PerfAuditPro\Cron;

if (!defined('ABSPATH')) {
    exit;
}

class Audit_Worker {

    /**
     * Process pending audits
     */
    public function process_pending_audits() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Worker needs direct queries for real-time processing
        $pending_audits = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM `' . esc_sql($table_name) . '` WHERE status = %s ORDER BY created_at ASC LIMIT %d',
                'pending',
                10
            ),
            ARRAY_A
        );

        if (empty($pending_audits)) {
            return;
        }

        foreach ($pending_audits as $audit) {
            $this->mark_audit_as_processing($audit['id']);
        }
    }

    /**
     * Mark audit as processing
     *
     * @param int $audit_id Audit ID
     */
    private function mark_audit_as_processing($audit_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Worker needs direct queries for real-time processing
        $wpdb->update(
            $table_name,
            array('status' => 'processing'),
            array('id' => $audit_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Worker callback stub for external worker integration
     *
     * @param int $audit_id Audit ID
     * @param array $results Audit results
     * @return bool|\WP_Error
     */
    public function complete_audit($audit_id, $results) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        return $repository->update_audit_results($audit_id, $results);
    }
}

