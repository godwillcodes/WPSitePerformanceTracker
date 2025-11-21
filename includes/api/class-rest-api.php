<?php
/**
 * REST API endpoints
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\API
 */

namespace PerfAuditPro\API;

if (!defined('ABSPATH')) {
    exit;
}

class Rest_API {

    /**
     * Initialize REST API
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        register_rest_route('perfaudit-pro/v1', '/synthetic-audit', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_synthetic_audit'),
            'permission_callback' => array(__CLASS__, 'check_token_auth'),
            'args' => array(
                'url' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array(__CLASS__, 'validate_url'),
                ),
                'audit_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'lighthouse',
                ),
            ),
        ));

        register_rest_route('perfaudit-pro/v1', '/rum-intake', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_rum_intake'),
            'permission_callback' => array(__CLASS__, 'check_nonce'),
            'args' => array(
                'url' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array(__CLASS__, 'validate_url'),
                ),
                'metrics' => array(
                    'required' => true,
                    'type' => 'object',
                ),
            ),
        ));

        register_rest_route('perfaudit-pro/v1', '/audit-results', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_audit_results'),
            'permission_callback' => array(__CLASS__, 'check_capability'),
        ));

        register_rest_route('perfaudit-pro/v1', '/rum-metrics', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_rum_metrics'),
            'permission_callback' => array(__CLASS__, 'check_capability'),
        ));
    }

    /**
     * Handle synthetic audit intake
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle_synthetic_audit($request) {
        if (!self::check_rate_limit('synthetic_audit')) {
            return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
        }

        $url = sanitize_url($request->get_param('url'));
        $audit_type = sanitize_text_field($request->get_param('audit_type'));

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $audit_id = $repository->create_synthetic_audit($url, $audit_type);

        if (is_wp_error($audit_id)) {
            return $audit_id;
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'audit_id' => $audit_id,
            'status' => 'pending',
        ), 201);
    }

    /**
     * Handle RUM intake
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle_rum_intake($request) {
        if (!self::check_rate_limit('rum_intake')) {
            return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
        }

        $url = sanitize_url($request->get_param('url'));
        $metrics = $request->get_param('metrics');

        if (!is_array($metrics)) {
            return new \WP_Error('invalid_metrics', 'Metrics must be an object', array('status' => 400));
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/security/class-sanitizer.php';
        $sanitized_metrics = \PerfAuditPro\Security\Sanitizer::sanitize_metrics($metrics);

        if (empty($sanitized_metrics)) {
            return new \WP_Error('invalid_metrics', 'No valid metrics provided', array('status' => 400));
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-rum-repository.php';
        $repository = new \PerfAuditPro\Database\RUM_Repository();

        $result = $repository->add_metric($url, $sanitized_metrics);

        if (is_wp_error($result)) {
            return $result;
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Metrics recorded',
        ), 200);
    }

    /**
     * Get audit results
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public static function get_audit_results($request) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $url = $request->get_param('url');
        if ($url) {
            $url = sanitize_url($url);
        }
        $limit = absint($request->get_param('limit')) ?: 10;
        $limit = min($limit, 100);

        $results = $repository->get_recent_audits($url, $limit);

        return new \WP_REST_Response($results, 200);
    }

    /**
     * Get RUM metrics
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public static function get_rum_metrics($request) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-rum-repository.php';
        $repository = new \PerfAuditPro\Database\RUM_Repository();

        $url = $request->get_param('url');
        if ($url) {
            $url = sanitize_url($url);
        }
        $days = absint($request->get_param('days')) ?: 30;
        $days = min($days, 365);

        $metrics = $repository->get_aggregated_metrics($url, $days);

        return new \WP_REST_Response($metrics, 200);
    }

    /**
     * Check token authentication
     *
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error
     */
    public static function check_token_auth($request) {
        $token = $request->get_header('X-PerfAudit-Token');
        $expected_token = get_option('perfaudit_pro_api_token');

        if (empty($expected_token)) {
            return new \WP_Error('token_not_configured', 'API token not configured', array('status' => 500));
        }

        if (!hash_equals($expected_token, $token)) {
            return new \WP_Error('invalid_token', 'Invalid authentication token', array('status' => 401));
        }

        return true;
    }

    /**
     * Check nonce validation
     *
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error
     */
    public static function check_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce') ?: $request->get_param('_wpnonce');

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
        }

        return true;
    }

    /**
     * Check user capability
     *
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public static function check_capability($request) {
        return current_user_can('manage_options');
    }

    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @return bool
     */
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check rate limit (stub)
     *
     * @param string $endpoint Endpoint identifier
     * @return bool
     */
    private static function check_rate_limit($endpoint) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/security/class-sanitizer.php';
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $ip = \PerfAuditPro\Security\Sanitizer::sanitize_ip($ip);
        $endpoint = sanitize_key($endpoint);
        
        $transient_key = 'perfaudit_rate_limit_' . $endpoint . '_' . $ip;
        $count = get_transient($transient_key);

        if ($count === false) {
            set_transient($transient_key, 1, 60);
            return true;
        }

        $limit = 100;
        if ($count >= $limit) {
            return false;
        }

        set_transient($transient_key, $count + 1, 60);
        return true;
    }
}

