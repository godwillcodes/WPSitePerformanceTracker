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

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <h2><?php esc_html_e('Create New Audit', 'perfaudit-pro'); ?></h2>
        <p><?php esc_html_e('Create a new synthetic audit. Note: You need an external worker to process audits. See WORKER_SETUP.md for details.', 'perfaudit-pro'); ?></p>
        <form id="create-audit-form" style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <label for="audit-url" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e('URL to Audit', 'perfaudit-pro'); ?></label>
                <input type="url" id="audit-url" name="url" value="<?php echo esc_attr(home_url()); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
            </div>
            <div>
                <button type="submit" class="button button-primary" style="height: 36px;"><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></button>
            </div>
        </form>
        <div id="create-audit-message" style="margin-top: 10px;"></div>
    </div>

    <div class="perfaudit-pro-grid">
        <div class="perfaudit-pro-card">
            <h2>📈 Synthetic Audits Timeline</h2>
            <div class="perfaudit-pro-explanation">
                <strong>No cap:</strong> This shows your performance scores over time. If it's going up, you're winning. If it's going down... well, we need to talk. Think of it as your site's report card but make it visual.
            </div>
            <div id="audit-timeline-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="audit-timeline-chart"></canvas>
            </div>
            <div id="audit-timeline-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>🥧 Performance Score Distribution</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Real talk:</strong> This pie chart shows where your audits are landing. Green = you're slaying. Yellow = meh, could be better. Red = yikes, we need to fix this ASAP. Most of your pie should be green, periodt.
            </div>
            <div id="score-distribution-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="score-distribution-chart"></canvas>
            </div>
            <div id="score-distribution-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>⚡ RUM Metrics - LCP (Largest Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Spill the tea:</strong> LCP measures how fast your main content loads. Under 2.5s = chef's kiss 👌. 2.5-4s = it's giving slow. Over 4s = your users are probably already gone. This is real user data, so it hits different.
            </div>
            <div id="rum-lcp-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-lcp-chart"></canvas>
            </div>
            <div id="rum-lcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>🎨 RUM Metrics - CLS (Cumulative Layout Shift)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Fr fr:</strong> CLS measures if your page is jumping around while loading. Under 0.1 = smooth like butter. 0.1-0.25 = a bit janky. Over 0.25 = your layout is having a whole identity crisis. Nobody likes a page that can't commit to a layout.
            </div>
            <div id="rum-cls-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-cls-chart"></canvas>
            </div>
            <div id="rum-cls-recommendations"></div>
        </div>
    </div>

    <div class="perfaudit-pro-card">
        <h2><?php esc_html_e('Recent Audits', 'perfaudit-pro'); ?></h2>
        <div id="recent-audits-table">
            <p><?php esc_html_e('Loading...', 'perfaudit-pro'); ?></p>
        </div>
    </div>

    <div class="perfaudit-pro-card" style="margin-top: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
        <h3 style="margin-top: 0;"><?php esc_html_e('How Synthetic Audits Work', 'perfaudit-pro'); ?></h3>
        <ol style="line-height: 1.8;">
            <li><strong><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('Use the form above to create a new audit. This creates a "pending" record in the database.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('External Worker', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('An external worker (Node.js/Puppeteer) polls for pending audits and runs Lighthouse tests.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('Submit Results', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('The worker submits results back via REST API, and the dashboard displays them.', 'perfaudit-pro'); ?></li>
        </ol>
        <p><strong><?php esc_html_e('Note', 'perfaudit-pro'); ?>:</strong> <?php esc_html_e('Without an external worker, audits will remain in "pending" status. See WORKER_SETUP.md for setup instructions.', 'perfaudit-pro'); ?></p>
    </div>
</div>

