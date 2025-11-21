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
    <div class="perfaudit-pro-scorecard-modern" data-grade="<?php echo esc_attr(strtolower($scorecard['grade'])); ?>" style="--grade-color: <?php echo esc_attr($grade_color); ?>;">
        <div class="perfaudit-pro-scorecard-modern-header">
            <div class="perfaudit-pro-scorecard-modern-icon-wrapper">
                <svg class="perfaudit-pro-scorecard-modern-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                </svg>
            </div>
            <div class="perfaudit-pro-scorecard-modern-title-section">
                <h2 class="perfaudit-pro-scorecard-modern-title">Performance Scorecard</h2>
                <p class="perfaudit-pro-scorecard-modern-subtitle">Overall performance score based on your recent synthetic audits</p>
            </div>
        </div>
        
        <div class="perfaudit-pro-scorecard-modern-body">
            <div class="perfaudit-pro-scorecard-modern-main">
                <div class="perfaudit-pro-scorecard-modern-grade-wrapper">
                    <div class="perfaudit-pro-scorecard-modern-grade-circle">
                        <svg class="perfaudit-pro-scorecard-modern-progress" viewBox="0 0 120 120">
                            <circle class="perfaudit-pro-scorecard-modern-progress-bg" cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <circle class="perfaudit-pro-scorecard-modern-progress-fill" cx="60" cy="60" r="54" fill="none" stroke="var(--grade-color)" stroke-width="8" stroke-linecap="round" 
                                stroke-dasharray="<?php echo esc_attr($scorecard['score'] * 3.39); ?> 339" 
                                stroke-dashoffset="84.75" transform="rotate(-90 60 60)"/>
                        </svg>
                        <div class="perfaudit-pro-scorecard-modern-grade-inner">
                            <span class="perfaudit-pro-scorecard-modern-grade-letter"><?php echo esc_html($scorecard['grade']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="perfaudit-pro-scorecard-modern-metrics">
                    <div class="perfaudit-pro-scorecard-modern-score-section">
                        <div class="perfaudit-pro-scorecard-modern-score-large"><?php echo esc_html($scorecard['score']); ?></div>
                        <div class="perfaudit-pro-scorecard-modern-score-label">out of 100</div>
                    </div>
                    
                    <div class="perfaudit-pro-scorecard-modern-meta">
                        <div class="perfaudit-pro-scorecard-modern-trend-badge" data-trend="<?php echo esc_attr($scorecard['trend']); ?>">
                            <?php if ($scorecard['trend'] === 'improving'): ?>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M8 2L10 6H14L11 10L13 14L8 12L3 14L5 10L2 6H6L8 2Z" fill="currentColor"/>
                                </svg>
                                <span>Improving</span>
                            <?php elseif ($scorecard['trend'] === 'declining'): ?>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M8 14L6 10H2L5 6L3 2L8 4L13 2L11 6L14 10H10L8 14Z" fill="currentColor"/>
                                </svg>
                                <span>Declining</span>
                            <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M8 0C3.58 0 0 3.58 0 8C0 12.42 3.58 16 8 16C12.42 16 16 12.42 16 8C16 3.58 12.42 0 8 0ZM8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C11.31 2 14 4.69 14 8C14 11.31 11.31 14 8 14Z" fill="currentColor"/>
                                </svg>
                                <span>Stable</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="perfaudit-pro-scorecard-modern-count">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                <path d="M8 0C3.58 0 0 3.58 0 8C0 12.42 3.58 16 8 16C12.42 16 16 12.42 16 8C16 3.58 12.42 0 8 0ZM8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C11.31 2 14 4.69 14 8C14 11.31 11.31 14 8 14Z" fill="currentColor"/>
                                <path d="M8 4V8L11 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span><?php echo esc_html($scorecard['audit_count']); ?> audits</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="perfaudit-pro-worker-card-modern">
        <div class="perfaudit-pro-worker-card-modern-header">
            <div class="perfaudit-pro-worker-card-modern-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"/>
                </svg>
            </div>
            <div>
                <h2 class="perfaudit-pro-worker-card-modern-title">Worker Status</h2>
                <p class="perfaudit-pro-worker-card-modern-subtitle">Automated audit processing system</p>
            </div>
        </div>

        <div id="worker-status-container">
            <div class="perfaudit-pro-worker-modern-info">
                <div class="perfaudit-pro-worker-modern-info-item">
                    <div class="perfaudit-pro-worker-modern-info-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="currentColor"/>
                            <path d="M10 6C9.45 6 9 6.45 9 7V10C9 10.55 9.45 11 10 11C10.55 11 11 10.55 11 10V7C11 6.45 10.55 6 10 6ZM10 13C9.45 13 9 13.45 9 14C9 14.55 9.45 15 10 15C10.55 15 11 14.55 11 14C11 13.45 10.55 13 10 13Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <strong>What is the Worker?</strong>
                        <p>The worker is a PHP-based background process that automatically processes pending audits using the Google PageSpeed Insights API. It runs entirely within WordPress—no Node.js, external services, or additional setup required.</p>
                    </div>
                </div>
                <div class="perfaudit-pro-worker-modern-info-item">
                    <div class="perfaudit-pro-worker-modern-info-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="currentColor"/>
                            <path d="M10 6C9.45 6 9 6.45 9 7V10C9 10.55 9.45 11 10 11C10.55 11 11 10.55 11 10V7C11 6.45 10.55 6 10 6ZM10 13C9.45 13 9 13.45 9 14C9 14.55 9.45 15 10 15C10.55 15 11 14.55 11 14C11 13.45 10.55 13 10 13Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <strong>When to Start</strong>
                        <p>Start the worker when you have pending audits that need processing. The worker automatically checks for new audits every 30 seconds and processes them in the background. Each audit typically takes 30-90 seconds to complete using the PageSpeed Insights API.</p>
                    </div>
                </div>
            </div>

            <div class="perfaudit-pro-worker-modern-status-section">
                <div class="perfaudit-pro-worker-modern-status-badge" id="worker-status-badge-modern">
                    <span class="perfaudit-pro-worker-modern-status-dot" id="worker-status-dot"></span>
                    <span id="worker-status-text" class="perfaudit-pro-worker-modern-status-text" aria-live="polite" aria-atomic="true"></span>
                </div>
            </div>

            <div class="perfaudit-pro-worker-modern-controls">
                <button id="worker-start-btn" class="perfaudit-pro-worker-modern-button perfaudit-pro-worker-modern-button-primary" aria-label="Start the audit worker process">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 0C3.58 0 0 3.58 0 8C0 12.42 3.58 16 8 16C12.42 16 16 12.42 16 8C16 3.58 12.42 0 8 0ZM6 11V5L11 8L6 11Z" fill="currentColor"/>
                    </svg>
                    Start Worker
                </button>
                <button id="worker-stop-btn" class="perfaudit-pro-worker-modern-button perfaudit-pro-worker-modern-button-secondary" style="display: none;" aria-label="Stop the audit worker process">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 0C3.58 0 0 3.58 0 8C0 12.42 3.58 16 8 16C12.42 16 16 12.42 16 8C16 3.58 12.42 0 8 0ZM10 11H6V5H10V11Z" fill="currentColor"/>
                    </svg>
                    Stop Worker
                </button>
            </div>

            <div class="perfaudit-pro-worker-modern-actions">
                <h3 class="perfaudit-pro-worker-modern-actions-title">Quick Actions</h3>
                <div class="perfaudit-pro-worker-modern-actions-grid">
                    <div class="perfaudit-pro-worker-modern-action-card">
                        <button id="process-now-btn" class="perfaudit-pro-worker-modern-action-button" aria-label="Process all pending audits immediately">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M9 0C4.03 0 0 4.03 0 9C0 13.97 4.03 18 9 18C13.97 18 18 13.97 18 9C18 4.03 13.97 0 9 0ZM9 16C5.13 16 2 12.87 2 9C2 5.13 5.13 2 9 2C12.87 2 16 5.13 16 9C16 12.87 12.87 16 9 16Z" fill="currentColor"/>
                                <path d="M9 5V9L12 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            Process Now
                        </button>
                        <div class="perfaudit-pro-worker-modern-action-content">
                            <strong>Process Now</strong>
                            <p>Immediately process all pending audits. Use this if the worker is stopped or you want to process audits right away.</p>
                        </div>
                    </div>
                    <div class="perfaudit-pro-worker-modern-action-card">
                        <button id="reset-stuck-btn" class="perfaudit-pro-worker-modern-action-button" aria-label="Reset audits stuck in processing status">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M9 0C4.03 0 0 4.03 0 9C0 13.97 4.03 18 9 18C13.97 18 18 13.97 18 9C18 4.03 13.97 0 9 0ZM9 16C5.13 16 2 12.87 2 9C2 5.13 5.13 2 9 2C12.87 2 16 5.13 16 9C16 12.87 12.87 16 9 16Z" fill="currentColor"/>
                                <path d="M9 4.5V9L12 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M6 6L9 4.5L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Retry Stuck Audits
                        </button>
                        <div class="perfaudit-pro-worker-modern-action-content">
                            <strong>Retry Stuck Audits</strong>
                            <p>If audits are stuck in "Processing" status for more than 15 minutes, this will reset them to "Pending" so they can be processed again.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="worker-message" class="perfaudit-pro-worker-message"></div>
        </div>
    </div>

    <div class="perfaudit-pro-create-audit-card">
        <div class="perfaudit-pro-create-audit-header">
            <div class="perfaudit-pro-create-audit-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <h2 class="perfaudit-pro-create-audit-title"><?php esc_html_e('Create New Audit', 'perfaudit-pro'); ?></h2>
                <p class="perfaudit-pro-create-audit-subtitle"><?php esc_html_e('Run a comprehensive performance audit using Google PageSpeed Insights API. Select desktop or mobile testing to analyze your site\'s performance metrics.', 'perfaudit-pro'); ?></p>
            </div>
        </div>
        
        <form id="create-audit-form" class="perfaudit-pro-create-audit-form">
            <div class="perfaudit-pro-form-group">
                <label for="audit-url" class="perfaudit-pro-form-label">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 0C3.58 0 0 3.58 0 8C0 12.42 3.58 16 8 16C12.42 16 16 12.42 16 8C16 3.58 12.42 0 8 0ZM8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C11.31 2 14 4.69 14 8C14 11.31 11.31 14 8 14Z" fill="currentColor"/>
                        <path d="M8 4V8L11 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <?php esc_html_e('URL to Audit', 'perfaudit-pro'); ?>
                </label>
                <div class="perfaudit-pro-input-wrapper">
                    <input type="url" id="audit-url" style="padding: 10px;" name="url" value="<?php echo esc_attr(home_url()); ?>" required 
                           placeholder="https://example.com" 
                           class="perfaudit-pro-form-input" />
                </div>
            </div>

            <div class="perfaudit-pro-form-group">
                <label class="perfaudit-pro-form-label">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 2V14M10 2V14" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <?php esc_html_e('Device Type', 'perfaudit-pro'); ?>
                </label>
                <div class="perfaudit-pro-device-selector" style="padding: 20px; gap: 50px;">
                    <input type="radio" id="device-desktop" name="device" value="desktop" checked class="perfaudit-pro-device-radio">
                    <label for="device-desktop" class="perfaudit-pro-device-option">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <rect x="2" y="3" width="16" height="12" rx="1" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M7 15H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M8 17H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <span>Desktop</span>
                    </label>
                    <input type="radio" id="device-mobile" name="device" value="mobile" class="perfaudit-pro-device-radio">
                    <label for="device-mobile" class="perfaudit-pro-device-option">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <rect x="6" y="2" width="8" height="16" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M9 5H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <span>Mobile</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="perfaudit-pro-create-audit-button">
                <span class="perfaudit-pro-button-content">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M9 0C4.03 0 0 4.03 0 9C0 13.97 4.03 18 9 18C13.97 18 18 13.97 18 9C18 4.03 13.97 0 9 0ZM9 16C5.13 16 2 12.87 2 9C2 5.13 5.13 2 9 2C12.87 2 16 5.13 16 9C16 12.87 12.87 16 9 16Z" fill="currentColor"/>
                        <path d="M9 5V9L12 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span><?php esc_html_e('Create Audit', 'perfaudit-pro'); ?></span>
                </span>
                <span class="perfaudit-pro-button-loader" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                            <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416;0 31.416" repeatCount="indefinite"/>
                            <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416;-31.416" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                    <span><?php esc_html_e('Creating...', 'perfaudit-pro'); ?></span>
                </span>
            </button>
        </form>
        
        <div id="create-audit-message" class="perfaudit-pro-create-audit-message"></div>
    </div>

    <div class="perfaudit-pro-grid" style="margin-top: var(--spacing-xl);">
        <div class="perfaudit-pro-card">
            <h2>Performance Score</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Overview:</strong> Your current performance score from the most recent synthetic audit. This score (0-100) is calculated by Google PageSpeed Insights and reflects your site's performance across Core Web Vitals and other key metrics.
            </div>
            <div id="audit-timeline-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="audit-timeline-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="audit-timeline-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>Performance Score Distribution</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Analysis:</strong> Distribution of your synthetic audit scores across performance ranges. Track your progress over time—aim to have the majority of audits in the excellent range (90-100) for optimal user experience.
            </div>
            <div id="score-distribution-status"></div>
            <div id="score-distribution-container" class="perfaudit-pro-score-distribution">
                <!-- Will be populated by JavaScript -->
            </div>
            <div id="score-distribution-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>LCP (Largest Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> Largest Contentful Paint (LCP) measures when the largest content element becomes visible. Under 2.5 seconds is excellent, 2.5-4 seconds needs improvement, and over 4 seconds indicates poor performance. This metric is collected from real user interactions on your site.
            </div>
            <div id="rum-lcp-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="rum-lcp-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="rum-lcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>CLS (Cumulative Layout Shift)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> Cumulative Layout Shift (CLS) measures visual stability by tracking unexpected layout shifts during page load. Under 0.1 is excellent, 0.1-0.25 needs improvement, and over 0.25 indicates significant layout shifts that frustrate users. Lower scores are better for CLS.
            </div>
            <div id="rum-cls-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="rum-cls-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="rum-cls-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>FID (First Input Delay)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> First Input Delay (FID) measures the time from when a user first interacts with your page to when the browser responds. Under 100ms is excellent, 100-300ms needs improvement, and over 300ms indicates poor responsiveness that can cause user frustration and abandonment.
            </div>
            <div id="rum-fid-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="rum-fid-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="rum-fid-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>FCP (First Contentful Paint)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> First Contentful Paint (FCP) measures when users first see any content rendered on the page. Under 1.8 seconds is excellent, 1.8-3 seconds needs improvement, and over 3 seconds indicates poor performance that may cause users to think the site is broken or unresponsive.
            </div>
            <div id="rum-fcp-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="rum-fcp-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="rum-fcp-recommendations"></div>
        </div>

        <div class="perfaudit-pro-card">
            <h2>TTFB (Time to First Byte)</h2>
            <div class="perfaudit-pro-explanation">
                <strong>Description:</strong> Time to First Byte (TTFB) measures server response time from the user's perspective. Under 800ms is excellent, 800-1800ms needs improvement, and over 1800ms indicates poor server performance. TTFB is fundamental to overall page speed and affects all other metrics.
            </div>
            <div id="rum-ttfb-status"></div>
            <div class="perfaudit-pro-circular-progress-container">
                <div id="rum-ttfb-progress" class="perfaudit-pro-circular-progress"></div>
            </div>
            <div id="rum-ttfb-recommendations"></div>
        </div>
    </div>

    <div class="perfaudit-pro-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
            <h2 style="margin: 0;"><?php esc_html_e('Recent Audits', 'perfaudit-pro'); ?></h2>
            <div style="display: flex; gap: var(--spacing-sm);" role="toolbar" aria-label="Export options">
                <button id="export-csv-btn" class="perfaudit-pro-worker-button" style="font-size: 13px; padding: 8px 16px;" aria-label="Export audit data as CSV file">
                    Export CSV
                </button>
                <button id="export-pdf-btn" class="perfaudit-pro-worker-button" style="font-size: 13px; padding: 8px 16px;" aria-label="Export audit data as HTML report">
                    Export Report
                </button>
            </div>
        </div>
        <div id="recent-audits-table" role="region" aria-label="Recent audits table" aria-busy="true">
            <div class="perfaudit-pro-loading-skeleton" style="padding: 20px;">
                <div style="height: 20px; background: #e5e7eb; border-radius: 4px; margin-bottom: 10px; animation: pulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 20px; background: #e5e7eb; border-radius: 4px; margin-bottom: 10px; width: 80%; animation: pulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 20px; background: #e5e7eb; border-radius: 4px; width: 60%; animation: pulse 1.5s ease-in-out infinite;"></div>
            </div>
            <p class="screen-reader-text"><?php esc_html_e('Loading audit data...', 'perfaudit-pro'); ?></p>
        </div>
    </div>

</div>
