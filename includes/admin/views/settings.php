<?php
/**
 * Settings page view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap perfaudit-pro-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('perfaudit_pro_settings'); ?>

        <div class="perfaudit-pro-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active">API Settings</a>
                <a href="#thresholds" class="nav-tab">Performance Thresholds</a>
                <a href="#notifications" class="nav-tab">Notifications</a>
                <a href="#worker" class="nav-tab">Worker</a>
                <a href="#rum" class="nav-tab">RUM Tracking</a>
            </nav>

            <div id="api-settings" class="tab-content active">
                <div class="perfaudit-pro-card">
                    <h2>API Configuration</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_psi_api_key">PageSpeed Insights API Key</label>
                            </th>
                            <td>
                                <input type="text" id="perfaudit_pro_psi_api_key" name="perfaudit_pro_psi_api_key" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_psi_api_key', '')); ?>" 
                                    class="regular-text" />
                                <p class="description">Optional. Get your free API key from <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>. Without a key, free tier limits apply.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_api_token">API Token</label>
                            </th>
                            <td>
                                <input type="text" id="perfaudit_pro_api_token" name="perfaudit_pro_api_token" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_api_token', '')); ?>" 
                                    class="regular-text" readonly />
                                <button type="button" class="button" id="regenerate-token">Regenerate</button>
                                <p class="description">Used for external API access. Auto-generated on activation.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="thresholds" class="tab-content">
                <div class="perfaudit-pro-card">
                    <h2>Default Performance Thresholds</h2>
                    <p>Set default thresholds for performance metrics. These can be overridden in rules.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_performance_score">Performance Score</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_performance_score" name="perfaudit_pro_threshold_performance_score" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_performance_score', '90')); ?>" 
                                    min="0" max="100" step="1" />
                                <p class="description">Minimum acceptable performance score (0-100)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_lcp">LCP (Largest Contentful Paint)</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_lcp" name="perfaudit_pro_threshold_lcp" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_lcp', '2500')); ?>" 
                                    min="0" step="100" />
                                <p class="description">Maximum acceptable LCP in milliseconds (recommended: &lt;2500ms)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_fid">FID (First Input Delay)</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_fid" name="perfaudit_pro_threshold_fid" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_fid', '100')); ?>" 
                                    min="0" step="10" />
                                <p class="description">Maximum acceptable FID in milliseconds (recommended: &lt;100ms)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_cls">CLS (Cumulative Layout Shift)</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_cls" name="perfaudit_pro_threshold_cls" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_cls', '0.1')); ?>" 
                                    min="0" max="1" step="0.01" />
                                <p class="description">Maximum acceptable CLS score (recommended: &lt;0.1)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_fcp">FCP (First Contentful Paint)</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_fcp" name="perfaudit_pro_threshold_fcp" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_fcp', '1800')); ?>" 
                                    min="0" step="100" />
                                <p class="description">Maximum acceptable FCP in milliseconds (recommended: &lt;1800ms)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="perfaudit_pro_threshold_ttfb">TTFB (Time to First Byte)</label></th>
                            <td>
                                <input type="number" id="perfaudit_pro_threshold_ttfb" name="perfaudit_pro_threshold_ttfb" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_threshold_ttfb', '800')); ?>" 
                                    min="0" step="50" />
                                <p class="description">Maximum acceptable TTFB in milliseconds (recommended: &lt;800ms)</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="notifications" class="tab-content">
                <div class="perfaudit-pro-card">
                    <h2>Notification Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_notification_enabled">Enable Notifications</label>
                            </th>
                            <td>
                                <input type="checkbox" id="perfaudit_pro_notification_enabled" name="perfaudit_pro_notification_enabled" 
                                    value="1" <?php checked(get_option('perfaudit_pro_notification_enabled', false), true); ?> />
                                <label for="perfaudit_pro_notification_enabled">Send email notifications for rule violations</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_notification_email">Notification Email</label>
                            </th>
                            <td>
                                <input type="email" id="perfaudit_pro_notification_email" name="perfaudit_pro_notification_email" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_notification_email', get_option('admin_email'))); ?>" 
                                    class="regular-text" />
                                <p class="description">Email address to receive violation notifications</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_webhook_url">Webhook URL</label>
                            </th>
                            <td>
                                <input type="url" id="perfaudit_pro_webhook_url" name="perfaudit_pro_webhook_url" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_webhook_url', '')); ?>" 
                                    class="regular-text" />
                                <p class="description">Optional webhook URL for Slack, Discord, or custom integrations</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="worker" class="tab-content">
                <div class="perfaudit-pro-card">
                    <h2>Worker Configuration</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_worker_interval">Processing Interval</label>
                            </th>
                            <td>
                                <select id="perfaudit_pro_worker_interval" name="perfaudit_pro_worker_interval">
                                    <option value="15" <?php selected(get_option('perfaudit_pro_worker_interval', '30'), '15'); ?>>Every 15 seconds</option>
                                    <option value="30" <?php selected(get_option('perfaudit_pro_worker_interval', '30'), '30'); ?>>Every 30 seconds</option>
                                    <option value="60" <?php selected(get_option('perfaudit_pro_worker_interval', '30'), '60'); ?>>Every minute</option>
                                    <option value="300" <?php selected(get_option('perfaudit_pro_worker_interval', '30'), '300'); ?>>Every 5 minutes</option>
                                </select>
                                <p class="description">How often the worker checks for pending audits</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_worker_max_concurrent">Max Concurrent Audits</label>
                            </th>
                            <td>
                                <input type="number" id="perfaudit_pro_worker_max_concurrent" name="perfaudit_pro_worker_max_concurrent" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_worker_max_concurrent', '2')); ?>" 
                                    min="1" max="10" step="1" />
                                <p class="description">Maximum number of audits to process simultaneously</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="rum" class="tab-content">
                <div class="perfaudit-pro-card">
                    <h2>RUM Tracking Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_rum_enabled">Enable RUM Tracking</label>
                            </th>
                            <td>
                                <input type="checkbox" id="perfaudit_pro_rum_enabled" name="perfaudit_pro_rum_enabled" 
                                    value="1" <?php checked(get_option('perfaudit_pro_rum_enabled', true), true); ?> />
                                <label for="perfaudit_pro_rum_enabled">Track real user metrics on frontend</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="perfaudit_pro_rum_sample_rate">Sample Rate</label>
                            </th>
                            <td>
                                <input type="number" id="perfaudit_pro_rum_sample_rate" name="perfaudit_pro_rum_sample_rate" 
                                    value="<?php echo esc_attr(get_option('perfaudit_pro_rum_sample_rate', '100')); ?>" 
                                    min="1" max="100" step="1" />
                                <p class="description">Percentage of page views to track (1-100). Lower values reduce database load.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<style>
.perfaudit-pro-settings-tabs {
    margin-top: 20px;
}
.tab-content {
    display: none;
    margin-top: 20px;
}
.tab-content.active {
    display: block;
}
.nav-tab-wrapper {
    border-bottom: 1px solid #ccc;
    margin-bottom: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });

    $('#regenerate-token').on('click', function() {
        if (confirm('Are you sure you want to regenerate the API token? Existing integrations will need to be updated.')) {
            const newToken = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            $('#perfaudit_pro_api_token').val(newToken);
        }
    });
});
</script>

