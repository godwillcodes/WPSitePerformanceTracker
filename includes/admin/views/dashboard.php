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
    $grade_color = $grade_colors[$scorecard['grade']] ?? '#6b7280';
    ?>
    <div class="perfaudit-pro-scorecard" style="border-left: 3px solid <?php echo esc_attr($grade_color); ?>;">
        <div class="perfaudit-pro-scorecard-header">
            <div class="perfaudit-pro-scorecard-content">
                <div class="perfaudit-pro-scorecard-grade" style="color: <?php echo esc_attr($grade_color); ?>;">
                    <?php echo esc_html($scorecard['grade']); ?>
                </div>
                <div class="perfaudit-pro-scorecard-details">
                    <div class="perfaudit-pro-scorecard-title">Performance Scorecard</div>
                    <div class="perfaudit-pro-scorecard-score"><?php echo esc_html($scorecard['score']); ?>/100</div>
                    <div class="perfaudit-pro-scorecard-trend">
                        <?php if ($scorecard['trend'] === 'improving'): ?>
                            Improving
                        <?php elseif ($scorecard['trend'] === 'declining'): ?>
                            Declining
                        <?php else: ?>
                            Stable
                        <?php endif; ?>
                    </div>
                    <div class="perfaudit-pro-scorecard-count">
                        Based on <?php echo esc_html($scorecard['audit_count']); ?> recent audits
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="perfaudit-pro-worker-card">
        <h2>Worker Status</h2>
        <div id="worker-status-container">
            <p>PHP-based worker processes audits automatically using Google PageSpeed Insights API. No Node.js required. Click to start or stop the worker.</p>
            <div class="perfaudit-pro-worker-actions">
                <button id="worker-start-btn" class="perfaudit-pro-worker-button primary">
                    Start Worker
                </button>
                <button id="worker-stop-btn" class="perfaudit-pro-worker-button" style="display: none;">
                    Stop Worker
                </button>
                <span id="worker-status-text" class="perfaudit-pro-worker-status"></span>
            </div>
            <div id="worker-message" class="perfaudit-pro-worker-message"></div>
        </div>
    </div>

    <div class="perfaudit-pro-card">
        <h2><?php esc_html_e('Create New Audit', 'perfaudit-pro'); ?></h2>
        <p><?php esc_html_e('Create a new synthetic audit. The built-in PHP worker will process it automatically when started.', 'perfaudit-pro'); ?></p>
        <form id="create-audit-form">
            <div style="flex: 1;">
                <label for="audit-url"><?php esc_html_e('URL to Audit', 'perfaudit-pro'); ?></label>
                <input type="url" id="audit-url" name="url" value="<?php echo esc_attr(home_url()); ?>" required />
            </div>
            <div>
                <button type="submit"><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></button>
            </div>
        </form>
        <div id="create-audit-message"></div>
    </div>

    <div class="perfaudit-pro-grid">
        <div class="perfaudit-pro-card">
            <h2>Synthetic Audits Timeline</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Overview:</strong> This chart displays your performance scores over time. An upward trend indicates improvement, while a downward trend may require attention. This provides a visual representation of your site's performance history.
            </div>
            <div id="audit-timeline-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="audit-timeline-chart"></canvas>
            </div>
            <div id="audit-timeline-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>Performance Score Distribution</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Analysis:</strong> This chart shows the distribution of your audit scores. Green indicates excellent performance, yellow indicates needs improvement, and red indicates poor performance. The majority of your audits should fall in the green range.
            </div>
            <div id="score-distribution-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="score-distribution-chart"></canvas>
            </div>
            <div id="score-distribution-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>RUM Metrics - LCP (Largest Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> LCP measures how fast your main content loads. Under 2.5 seconds is considered excellent. 2.5-4 seconds is acceptable but could be improved. Over 4 seconds indicates poor performance and may result in user abandonment. This metric uses real user data.
            </div>
            <div id="rum-lcp-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-lcp-chart"></canvas>
            </div>
            <div id="rum-lcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>RUM Metrics - CLS (Cumulative Layout Shift)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> CLS measures visual stability by tracking unexpected layout shifts during page load. Under 0.1 is considered excellent. 0.1-0.25 indicates some instability. Over 0.25 indicates significant layout shifts that negatively impact user experience.
            </div>
            <div id="rum-cls-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-cls-chart"></canvas>
            </div>
            <div id="rum-cls-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>RUM Metrics - FID (First Input Delay)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> FID measures the time from when a user first interacts with your page to when the browser responds. Under 100ms is considered excellent. 100-300ms is acceptable but not ideal. Over 300ms indicates poor responsiveness and may cause users to leave.
            </div>
            <div id="rum-fid-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-fid-chart"></canvas>
            </div>
            <div id="rum-fid-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>RUM Metrics - FCP (First Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> FCP measures when users first see any content rendered on the page. Under 1.8 seconds is considered excellent. 1.8-3 seconds is acceptable but could be faster. Over 3 seconds indicates poor performance and may cause users to think the site is broken.
            </div>
            <div id="rum-fcp-status"></div>
            <div class="perfaudit-pro-chart-container">
                <canvas id="rum-fcp-chart"></canvas>
            </div>
            <div id="rum-fcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>RUM Metrics - TTFB (Time to First Byte)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> TTFB measures server response time from the user's perspective. Under 800ms is considered excellent. 800-1800ms is acceptable but could be improved. Over 1800ms indicates poor server performance. This metric is fundamental to overall page speed.
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

    <div class="perfaudit-pro-card" style="background: var(--color-bg-light); border-left: 3px solid var(--color-primary);">
        <h3><?php esc_html_e('How Synthetic Audits Work', 'perfaudit-pro'); ?></h3>
        <ol>
            <li><strong><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('Use the form above to create a new audit. This creates a "pending" record in the database.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('Worker Processing', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('The PHP worker polls for pending audits and runs performance tests using Google PageSpeed Insights API.', 'perfaudit-pro'); ?></li>
            <li><strong><?php esc_html_e('Results Display', 'perfaudit-pro'); ?></strong>: <?php esc_html_e('Results are submitted back via REST API and displayed on the dashboard.', 'perfaudit-pro'); ?></li>
        </ol>
        <p><strong><?php esc_html_e('Note', 'perfaudit-pro'); ?>:</strong> <?php esc_html_e('Start the worker from the status card above to process audits automatically. The worker uses Google PageSpeed Insights API and requires no external setup.', 'perfaudit-pro'); ?></p>
    </div>
</div>
