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

    <?php
    $scorecard = \PerfAuditPro\Admin\Scorecard::get_scorecard();
    $grade_colors = array(
        'A' => '#10b981',
        'B' => '#3b82f6',
        'C' => '#f59e0b',
        'D' => '#ef4444',
        'F' => '#dc2626',
    );
    $grade_color = $grade_colors[$scorecard['grade']] ?? '#64748b';
    ?>
    <div class="perfaudit-pro-card" style="margin-bottom: 20px; background: linear-gradient(135deg, <?php echo esc_attr($grade_color); ?> 0%, <?php echo esc_attr($grade_color); ?>dd 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="color: white; margin-top: 0;">📊 Performance Scorecard</h2>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 12px;">
                    <div style="font-size: 64px; font-weight: 700; line-height: 1;"><?php echo esc_html($scorecard['grade']); ?></div>
                    <div>
                        <div style="font-size: 24px; font-weight: 600;"><?php echo esc_html($scorecard['score']); ?>/100</div>
                        <div style="opacity: 0.9; margin-top: 4px;">
                            <?php if ($scorecard['trend'] === 'improving'): ?>
                                📈 Improving
                            <?php elseif ($scorecard['trend'] === 'declining'): ?>
                                📉 Declining
                            <?php else: ?>
                                ➡️ Stable
                            <?php endif; ?>
                        </div>
                        <div style="opacity: 0.8; margin-top: 4px; font-size: 14px;">
                            Based on <?php echo esc_html($scorecard['audit_count']); ?> recent audits
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px; background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%); color: white; border: none;">
        <h2 style="color: white; margin-top: 0;">🤖 Worker Status</h2>
        <div id="worker-status-container">
            <p style="margin-bottom: 16px; opacity: 0.9;">PHP-based worker processes audits automatically using Google PageSpeed Insights API. No Node.js required! Click to start/stop.</p>
            <div style="display: flex; gap: 12px; align-items: center;">
                <button id="worker-start-btn" class="button" style="background: white; color: #007BFF; border: none; font-weight: 600; padding: 10px 24px;">
                    ▶ Start Worker
                </button>
                <button id="worker-stop-btn" class="button" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 600; padding: 10px 24px; display: none;">
                    ⏹ Stop Worker
                </button>
                <span id="worker-status-text" style="margin-left: 12px; font-weight: 500;"></span>
            </div>
            <div id="worker-message" style="margin-top: 12px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 6px; display: none;"></div>
        </div>
    </div>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <h2><?php esc_html_e('Create New Audit', 'perfaudit-pro'); ?></h2>
        <p><?php esc_html_e('Create a new synthetic audit. The built-in PHP worker will process it automatically when started.', 'perfaudit-pro'); ?></p>
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

        <div class="perfaudit-pro-card">
            <h2>⚡ RUM Metrics - FID (First Input Delay)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Real talk:</strong> FID measures how long users wait before they can interact. Under 100ms = instant vibes. 100-300ms = acceptable but not ideal. Over 300ms = your site is giving laggy. Users will bounce if they can't click things.
            </div>
            <div id="rum-fid-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-fid-chart"></canvas>
            </div>
            <div id="rum-fid-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>🚀 RUM Metrics - FCP (First Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>No cap:</strong> FCP is when users first see ANY content. Under 1.8s = you're winning. 1.8-3s = meh, could be faster. Over 3s = users think your site is broken. First impressions matter, periodt.
            </div>
            <div id="rum-fcp-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-fcp-chart"></canvas>
            </div>
            <div id="rum-fcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>⏱️ RUM Metrics - TTFB (Time to First Byte)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Spill the tea:</strong> TTFB measures server response time. Under 800ms = server is slaying. 800-1800ms = server needs coffee. Over 1800ms = your server is having a whole breakdown. This is the foundation of speed.
            </div>
            <div id="rum-ttfb-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-ttfb-chart"></canvas>
            </div>
            <div id="rum-ttfb-recommendations"></div>
        </div>
    </div>

    <div class="perfaudit-pro-card">
        <h2><?php esc_html_e('Recent Audits', 'perfaudit-pro'); ?></h2>
        <div id="recent-audits-table">
            <p><?php esc_html_e('Loading...', 'perfaudit-pro'); ?></p>
        </div>
    </div>

    <div class="perfaudit-pro-card" style="margin-top: 20px; background: #f8f9fa; border-left: 3px solid #007BFF;">
        <h3 style="margin-top: 0;"><?php esc_html_e('How Synthetic Audits Work', 'perfaudit-pro'); ?></h3>
        <ol style="line-height: 1.8;">
            <li><strong><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('Use the form above to create a new audit. This creates a "pending" record in the database.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('External Worker', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('An external worker (Node.js/Puppeteer) polls for pending audits and runs Lighthouse tests.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('Submit Results', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('The worker submits results back via REST API, and the dashboard displays them.', 'perfaudit-pro'); ?></li>
        </ol>
        <p><strong><?php esc_html_e('Note', 'perfaudit-pro'); ?>:</strong> <?php esc_html_e('Start the worker from the status card above to process audits automatically. The worker uses Google PageSpeed Insights API and requires no external setup.', 'perfaudit-pro'); ?></p>
    </div>
</div>

