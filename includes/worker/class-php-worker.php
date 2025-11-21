<?php
/**
 * PHP-based Worker - No Node.js Required
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Worker
 */

namespace PerfAuditPro\Worker;

if (!defined('ABSPATH')) {
    exit;
}

class PHP_Worker {

    /**
     * Initialize PHP worker
     */
    public static function init() {
        add_action('wp_ajax_perfaudit_process_audits', array(__CLASS__, 'process_pending_audits'));
        add_action('perfaudit_pro_process_audits', array(__CLASS__, 'process_pending_audits_cron'));
    }

    /**
     * Process pending audits
     *
     * Processes up to 5 pending audits to prevent timeout.
     * Called by WP-Cron or AJAX.
     *
     * @return void
     */
    public static function process_pending_audits(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        // First, reset any stuck audits (processing for more than 10 minutes)
        self::reset_stuck_audits();

        // Get pending audits (limit to prevent timeout)
        $pending_audits = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at ASC LIMIT %d",
                'pending',
                5
            ),
            ARRAY_A
        );

        if (empty($pending_audits)) {
            return;
        }

        foreach ($pending_audits as $audit) {
            self::process_single_audit($audit);
        }
    }

    /**
     * Reset stuck audits back to pending
     *
     * Audits stuck in "processing" state for more than 15 minutes are reset.
     * This handles cases where the worker crashed or timed out.
     *
     * @return int Number of audits reset
     */
    private static function reset_stuck_audits(): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $timeout_minutes = 15; // Reset audits stuck in processing for more than 15 minutes

        // Reset audits that have been processing for more than the timeout period
        // We check created_at as a proxy - if audit was created more than timeout ago and still processing, it's stuck
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name 
                SET status = 'pending', worker_id = NULL 
                WHERE status = 'processing' 
                AND created_at < DATE_SUB(NOW(), INTERVAL %d MINUTE)
                AND (completed_at IS NULL OR completed_at = '0000-00-00 00:00:00')",
                $timeout_minutes
            )
        );

        if ($result > 0) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::info('Reset stuck audits', array('count' => $result));
        }

        return (int) $result;
    }

    /**
     * Public method to reset stuck audits (can be called manually)
     *
     * @return int Number of audits reset
     */
    public static function reset_stuck_audits_public(): int {
        return self::reset_stuck_audits();
    }

    /**
     * Process single audit via cron
     */
    public static function process_pending_audits_cron() {
        self::process_pending_audits();
    }

    /**
     * Process a single audit
     *
     * Executes the audit and updates the database with results.
     *
     * @param array<string, mixed> $audit Audit record from database
     * @return void
     */
    private static function process_single_audit(array $audit): void {
        global $wpdb;

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $audit_id = isset($audit['id']) ? (int) $audit['id'] : 0;
        $url = isset($audit['url']) ? (string) $audit['url'] : '';
        
        if ($audit_id <= 0 || empty($url)) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('Invalid audit record', array('audit' => $audit));
            return;
        }
        
        $validated_url = \PerfAuditPro\Utils\Validator::validate_url($url);
        if ($validated_url === null) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('Invalid URL in audit record', array('url' => $url));
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('id' => $audit_id),
                array('%s'),
                array('%d')
            );
            return;
        }

        // Mark as processing
        $wpdb->update(
            $table_name,
            array('status' => 'processing'),
            array('id' => $audit_id),
            array('%s'),
            array('%d')
        );

        try {
            // Run audit using PageSpeed Insights API
            $device = \PerfAuditPro\Utils\Validator::validate_device($audit['device'] ?? 'desktop');
            $results = self::run_pagespeed_audit($validated_url, $device);

            if ($results && !is_wp_error($results)) {
                // Update audit with results
                require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
                $repository = new \PerfAuditPro\Database\Audit_Repository();
                $repository->update_audit_results($audit_id, $results);
                
                // Trigger audit completed action
                do_action('perfaudit_pro_audit_completed', $audit_id, $results);
            } else {
                // Mark as failed
                $wpdb->update(
                    $table_name,
                    array('status' => 'failed'),
                    array('id' => $audit_id),
                    array('%s'),
                    array('%d')
                );
            }
        } catch (\Exception $e) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::error('Error processing audit', array(
                'audit_id' => $audit_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('id' => absint($audit_id)),
                array('%s'),
                array('%d')
            );
        }
    }

    /**
     * Run PageSpeed Insights audit
     *
     * @param string $url URL to audit
     * @param string $device Device type (desktop/mobile)
     * @return array|WP_Error Audit results
     */
    private static function run_pagespeed_audit($url, $device = 'desktop') {
        // Try PageSpeed Insights API first (free, no API key needed for basic usage)
        $api_key = get_option('perfaudit_pro_psi_api_key', '');
        
        $strategy = $device === 'mobile' ? 'MOBILE' : 'DESKTOP';
        
        $api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        $api_url = add_query_arg(array(
            'url' => urlencode($url),
            'category' => 'PERFORMANCE',
            'strategy' => $strategy,
        ), $api_url);

        if (!empty($api_key)) {
            $api_url = add_query_arg('key', $api_key, $api_url);
        }

        $response = wp_remote_get($api_url, array(
            'timeout' => 90, // Increased timeout for PageSpeed Insights API
            'headers' => array(
                'User-Agent' => 'Site-Performance-Tracker/1.0',
            ),
        ));

        if (is_wp_error($response)) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('PageSpeed Insights API error', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
            // Fallback to alternative method
            return self::run_fallback_audit($url);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('PageSpeed Insights API returned error', array(
                'url' => $url,
                'code' => $response_code,
            ));
            return self::run_fallback_audit($url);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['lighthouseResult'])) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::warning('Invalid PageSpeed Insights response', array(
                'url' => $url,
                'has_data' => !empty($data),
            ));
            return self::run_fallback_audit($url);
        }

        return self::extract_psi_metrics($data);
    }

    /**
     * Extract metrics from PageSpeed Insights response
     *
     * @param array $data PSI API response
     * @return array Metrics
     */
    private static function extract_psi_metrics($data) {
        $lighthouse = $data['lighthouseResult'];
        $audits = $lighthouse['audits'];
        $categories = $lighthouse['categories'];

        $performance_score = isset($categories['performance']['score']) 
            ? round($categories['performance']['score'] * 100) 
            : null;

        $results = array(
            'performance_score' => $performance_score,
            'first_contentful_paint' => isset($audits['first-contentful-paint']['numericValue']) 
                ? $audits['first-contentful-paint']['numericValue'] 
                : null,
            'largest_contentful_paint' => isset($audits['largest-contentful-paint']['numericValue']) 
                ? $audits['largest-contentful-paint']['numericValue'] 
                : null,
            'total_blocking_time' => isset($audits['total-blocking-time']['numericValue']) 
                ? $audits['total-blocking-time']['numericValue'] 
                : null,
            'cumulative_layout_shift' => isset($audits['cumulative-layout-shift']['numericValue']) 
                ? $audits['cumulative-layout-shift']['numericValue'] 
                : null,
            'speed_index' => isset($audits['speed-index']['numericValue']) 
                ? $audits['speed-index']['numericValue'] 
                : null,
            'time_to_interactive' => isset($audits['interactive']['numericValue']) 
                ? $audits['interactive']['numericValue'] 
                : null,
            'lighthouse_json' => wp_json_encode($lighthouse),
        );

        return $results;
    }

    /**
     * Fallback audit method (simpler metrics)
     *
     * @param string $url URL to audit
     * @return array Basic metrics
     */
    private static function run_fallback_audit($url) {
        // Simple HTTP timing audit as fallback
        $start_time = microtime(true);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'redirection' => 5,
        ));

        $end_time = microtime(true);
        $response_time = ($end_time - $start_time) * 1000; // Convert to milliseconds

        if (is_wp_error($response)) {
            return new \WP_Error('audit_failed', 'Failed to fetch URL: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $content_length = strlen($body);
        $ttfb = wp_remote_retrieve_header($response, 'x-response-time') 
            ?: ($response_time * 0.3); // Estimate TTFB as 30% of total time

        // Calculate basic performance score based on response time
        $performance_score = max(0, min(100, 100 - ($response_time / 10)));

        return array(
            'performance_score' => round($performance_score),
            'time_to_first_byte' => $ttfb,
            'response_time' => $response_time,
            'content_length' => $content_length,
        );
    }

    /**
     * Start worker (schedule cron job)
     */
    public static function start() {
        if (!wp_next_scheduled('perfaudit_pro_process_audits')) {
            wp_schedule_event(time(), 'perfaudit_worker_interval', 'perfaudit_pro_process_audits');
        }
        
        // Add custom interval
        add_filter('cron_schedules', function($schedules) {
            $schedules['perfaudit_worker_interval'] = array(
                'interval' => 30, // Every 30 seconds
                'display' => 'Every 30 seconds'
            );
            return $schedules;
        });

        // Process immediately
        self::process_pending_audits();
    }

    /**
     * Stop worker
     */
    public static function stop() {
        $timestamp = wp_next_scheduled('perfaudit_pro_process_audits');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'perfaudit_pro_process_audits');
        }
    }

    /**
     * Check if worker is running
     */
    public static function is_running() {
        return wp_next_scheduled('perfaudit_pro_process_audits') !== false;
    }
}

