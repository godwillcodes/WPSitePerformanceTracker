<?php
/**
 * Export functionality
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Export {

    /**
     * Initialize export
     */
    public static function init() {
        add_action('wp_ajax_perfaudit_export_csv', array(__CLASS__, 'export_csv'));
        add_action('wp_ajax_perfaudit_export_pdf', array(__CLASS__, 'export_pdf'));
    }

    /**
     * Export audits as CSV
     */
    public static function export_csv() {
        // Use wp_verify_nonce with the nonce from GET parameter
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'perfaudit_export')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        // Get filters from request
        $filters = array();
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        $url = isset($_GET['url']) ? sanitize_url($_GET['url']) : null;
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 1000;
        $audits = $repository->get_recent_audits($url, $limit, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="site-performance-tracker-audits-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers - only include fields that exist in synthetic audits table
        fputcsv($output, array('ID', 'URL', 'Device', 'Performance Score', 'LCP (ms)', 'FCP (ms)', 'CLS', 'TBT (ms)', 'Speed Index (ms)', 'TTI (ms)', 'Status', 'Created At', 'Completed At'));

        foreach ($audits as $audit) {
            fputcsv($output, array(
                $audit['id'],
                $audit['url'],
                $audit['device'] ?? 'desktop',
                $audit['performance_score'] ?? '',
                $audit['largest_contentful_paint'] ? round($audit['largest_contentful_paint'], 2) : '',
                $audit['first_contentful_paint'] ? round($audit['first_contentful_paint'], 2) : '',
                $audit['cumulative_layout_shift'] ? round($audit['cumulative_layout_shift'], 4) : '',
                $audit['total_blocking_time'] ? round($audit['total_blocking_time'], 2) : '',
                $audit['speed_index'] ? round($audit['speed_index'], 2) : '',
                $audit['time_to_interactive'] ? round($audit['time_to_interactive'], 2) : '',
                $audit['status'],
                $audit['created_at'],
                $audit['completed_at'] ?? '',
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export HTML report (can be printed as PDF)
     */
    public static function export_pdf() {
        // Use wp_verify_nonce with the nonce from GET parameter
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'perfaudit_export')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        // Get filters from request
        $filters = array();
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        $url = isset($_GET['url']) ? sanitize_url($_GET['url']) : null;
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 100;
        $audits = $repository->get_recent_audits($url, $limit, $filters);

        $html = self::generate_report_html($audits);

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="site-performance-tracker-report-' . date('Y-m-d') . '.html"');
        echo $html;
        exit;
    }

    /**
     * Generate HTML report
     */
    private static function generate_report_html($audits) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Site Performance Tracker Report - <?php echo date('Y-m-d'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { color: #007BFF; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #007BFF; color: white; }
                tr:nth-child(even) { background: #f9f9f9; }
            </style>
        </head>
        <body>
            <h1>Site Performance Tracker Report</h1>
            <p>Generated: <?php echo current_time('mysql'); ?></p>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Device</th>
                        <th>Score</th>
                        <th>LCP (ms)</th>
                        <th>FCP (ms)</th>
                        <th>CLS</th>
                        <th>TBT (ms)</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $audit): ?>
                    <tr>
                        <td><?php echo esc_html($audit['url']); ?></td>
                        <td><?php echo esc_html($audit['device'] ?? 'desktop'); ?></td>
                        <td><?php echo esc_html($audit['performance_score'] ?? 'N/A'); ?></td>
                        <td><?php echo isset($audit['largest_contentful_paint']) ? esc_html(round($audit['largest_contentful_paint'], 2)) : 'N/A'; ?></td>
                        <td><?php echo isset($audit['first_contentful_paint']) ? esc_html(round($audit['first_contentful_paint'], 2)) : 'N/A'; ?></td>
                        <td><?php echo isset($audit['cumulative_layout_shift']) ? esc_html(round($audit['cumulative_layout_shift'], 4)) : 'N/A'; ?></td>
                        <td><?php echo isset($audit['total_blocking_time']) ? esc_html(round($audit['total_blocking_time'], 2)) : 'N/A'; ?></td>
                        <td><?php echo esc_html($audit['status']); ?></td>
                        <td><?php echo esc_html($audit['created_at']); ?></td>
                        <td><?php echo esc_html($audit['completed_at'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

