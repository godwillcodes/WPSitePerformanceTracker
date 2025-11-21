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
        check_ajax_referer('perfaudit_export', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $url = isset($_GET['url']) ? sanitize_url($_GET['url']) : null;
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 1000;
        $audits = $repository->get_recent_audits($url, $limit);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="perfaudit-audits-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'URL', 'Performance Score', 'LCP', 'FID', 'CLS', 'FCP', 'TTFB', 'TBT', 'Status', 'Created At', 'Completed At'));

        foreach ($audits as $audit) {
            fputcsv($output, array(
                $audit['id'],
                $audit['url'],
                $audit['performance_score'] ?? '',
                $audit['largest_contentful_paint'] ?? '',
                $audit['total_blocking_time'] ?? '',
                $audit['cumulative_layout_shift'] ?? '',
                $audit['first_contentful_paint'] ?? '',
                '',
                $audit['total_blocking_time'] ?? '',
                $audit['status'],
                $audit['created_at'],
                $audit['completed_at'] ?? '',
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export PDF report
     */
    public static function export_pdf() {
        check_ajax_referer('perfaudit_export', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Simple HTML report (can be enhanced with TCPDF or similar)
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $url = isset($_GET['url']) ? sanitize_url($_GET['url']) : null;
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 100;
        $audits = $repository->get_recent_audits($url, $limit);

        $html = self::generate_report_html($audits);

        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="perfaudit-report-' . date('Y-m-d') . '.html"');
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
            <title>PerfAudit Pro Report - <?php echo date('Y-m-d'); ?></title>
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
            <h1>PerfAudit Pro Performance Report</h1>
            <p>Generated: <?php echo current_time('mysql'); ?></p>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Score</th>
                        <th>LCP</th>
                        <th>CLS</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $audit): ?>
                    <tr>
                        <td><?php echo esc_html($audit['url']); ?></td>
                        <td><?php echo esc_html($audit['performance_score'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($audit['largest_contentful_paint'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($audit['cumulative_layout_shift'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($audit['status']); ?></td>
                        <td><?php echo esc_html($audit['created_at']); ?></td>
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

