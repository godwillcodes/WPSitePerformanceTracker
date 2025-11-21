<?php
/**
 * Admin dashboard view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap perfaudit-pro-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="perfaudit-pro-grid">
        <div class="perfaudit-pro-card">
            <h2><?php esc_html_e('Synthetic Audits', 'perfaudit-pro'); ?></h2>
            <div class="perfaudit-pro-chart-container">
                <canvas id="audit-timeline-chart"></canvas>
            </div>
        </div>

        <div class="perfaudit-pro-card">
            <h2><?php esc_html_e('Performance Score Distribution', 'perfaudit-pro'); ?></h2>
            <div class="perfaudit-pro-chart-container">
                <canvas id="score-distribution-chart"></canvas>
            </div>
        </div>

        <div class="perfaudit-pro-card">
            <h2><?php esc_html_e('RUM Metrics - LCP', 'perfaudit-pro'); ?></h2>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-lcp-chart"></canvas>
            </div>
        </div>

        <div class="perfaudit-pro-card">
            <h2><?php esc_html_e('RUM Metrics - CLS', 'perfaudit-pro'); ?></h2>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-cls-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="perfaudit-pro-card">
        <h2><?php esc_html_e('Recent Audits', 'perfaudit-pro'); ?></h2>
        <div id="recent-audits-table">
            <p><?php esc_html_e('Loading...', 'perfaudit-pro'); ?></p>
        </div>
    </div>
</div>

