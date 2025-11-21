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

        register_rest_route('perfaudit-pro/v1', '/submit-audit-results', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_submit_audit_results'),
            'permission_callback' => array(__CLASS__, 'check_token_auth'),
            'args' => array(
                'audit_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'results' => array(
                    'required' => true,
                    'type' => 'object',
                ),
            ),
        ));

        register_rest_route('perfaudit-pro/v1', '/create-audit', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_create_audit'),
            'permission_callback' => array(__CLASS__, 'check_capability'),
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

        register_rest_route('perfaudit-pro/v1', '/pending-audits', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_pending_audits'),
            'permission_callback' => array(__CLASS__, 'check_token_auth'),
        ));

        register_rest_route('perfaudit-pro/v1', '/mark-processing', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_mark_processing'),
            'permission_callback' => array(__CLASS__, 'check_token_auth'),
            'args' => array(
                'audit_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'worker_id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route('perfaudit-pro/v1', '/delete-audits', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_delete_audits'),
            'permission_callback' => array(__CLASS__, 'check_capability'),
            'args' => array(
                'audit_ids' => array(
                    'required' => true,
                    'type' => 'array',
                ),
            ),
        ));
    }

    /**
     * Handle synthetic audit intake
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object or error
     */
    public static function handle_synthetic_audit(\WP_REST_Request $request) {
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
     * Processes Real User Monitoring metrics from frontend.
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object or error
     */
    public static function handle_rum_intake(\WP_REST_Request $request) {
        if (!self::check_rate_limit('rum_intake')) {
            return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $url = \PerfAuditPro\Utils\Validator::validate_url($request->get_param('url'));
        if ($url === null) {
            return new \WP_Error('invalid_url', 'Invalid URL provided', array('status' => 400));
        }

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
        $limit = min($limit, 1000);

        $filters = array(
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
        );

        $results = $repository->get_recent_audits($url, $limit, $filters);

        return new \WP_REST_Response($results, 200);
    }

    /**
     * Get RUM metrics
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response with RUM metrics
     */
    public static function get_rum_metrics(\WP_REST_Request $request): \WP_REST_Response {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-rum-repository.php';
        $repository = new \PerfAuditPro\Database\RUM_Repository();

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $url = $request->get_param('url');
        $validated_url = null;
        if ($url !== null && $url !== '') {
            $validated_url = \PerfAuditPro\Utils\Validator::validate_url($url);
        }
        
        $days = \PerfAuditPro\Utils\Validator::validate_positive_int($request->get_param('days'), 30, 1, 365);

        $metrics = $repository->get_aggregated_metrics($validated_url, $days);

        return new \WP_REST_Response($metrics, 200);
    }

    /**
     * Handle submit audit results from external worker
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object or error
     */
    public static function handle_submit_audit_results(\WP_REST_Request $request) {
        if (!self::check_rate_limit('submit_results')) {
            return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-validator.php';
        
        $audit_id_param = $request->get_param('audit_id');
        if (!\PerfAuditPro\Utils\Validator::is_positive_int($audit_id_param)) {
            return new \WP_Error('invalid_audit_id', 'Invalid audit ID', array('status' => 400));
        }
        $audit_id = (int) $audit_id_param;
        
        $results = $request->get_param('results');
        if (!is_array($results)) {
            return new \WP_Error('invalid_results', 'Results must be an object', array('status' => 400));
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $result = $repository->update_audit_results($audit_id, $results);

        if (is_wp_error($result)) {
            return $result;
        }

        // Trigger audit completed action
        do_action('perfaudit_pro_audit_completed', $audit_id, $results);

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Audit results updated',
        ), 200);
    }

    /**
     * Handle create audit from admin dashboard
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle_create_audit($request) {
        $url = sanitize_url($request->get_param('url'));
        $audit_type = sanitize_text_field($request->get_param('audit_type')) ?: 'lighthouse';
        $device = sanitize_text_field($request->get_param('device')) ?: 'desktop';
        
        if (!in_array($device, array('desktop', 'mobile'))) {
            $device = 'desktop';
        }

        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $audit_id = $repository->create_synthetic_audit($url, $audit_type, $device);

        if (is_wp_error($audit_id)) {
            return $audit_id;
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'audit_id' => $audit_id,
            'status' => 'pending',
            'message' => 'Audit created. The built-in PHP worker will process it automatically when running.',
        ), 201);
    }

    /**
     * Get pending audits for worker
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public static function get_pending_audits($request) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';
        $limit = absint($request->get_param('limit')) ?: 10;

        $query = $wpdb->prepare(
            "SELECT id, url, audit_type, created_at FROM $table_name WHERE status = %s ORDER BY created_at ASC LIMIT %d",
            'pending',
            $limit
        );

        $audits = $wpdb->get_results($query, ARRAY_A);

        return new \WP_REST_Response($audits, 200);
    }

    /**
     * Mark audit as processing
     *
     * Atomically marks an audit as processing to prevent race conditions.
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object or error
     */
    public static function handle_mark_processing(\WP_REST_Request $request) {
        global $wpdb;

        $audit_id = absint($request->get_param('audit_id'));
        $worker_id = sanitize_text_field($request->get_param('worker_id'));

        $table_name = $wpdb->prefix . 'perfaudit_synthetic_audits';

        // Try to update only if status is still pending (prevent race conditions)
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'processing',
                'worker_id' => $worker_id,
            ),
            array(
                'id' => $audit_id,
                'status' => 'pending',
            ),
            array('%s', '%s'),
            array('%d', '%s')
        );

        if ($result === false) {
            require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
            \PerfAuditPro\Utils\Logger::error('Failed to mark audit as processing', array(
                'audit_id' => $audit_id,
                'error' => $wpdb->last_error,
            ));
            return new \WP_Error('db_error', 'Failed to update audit status', array('status' => 500));
        }

        if ($result === 0) {
            return new \WP_Error('already_processing', 'Audit is already being processed by another worker', array('status' => 409));
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Audit marked as processing',
        ), 200);
    }

    /**
     * Delete audits
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object or error
     */
    public static function handle_delete_audits(\WP_REST_Request $request) {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $audit_ids = $request->get_param('audit_ids');
        if (!is_array($audit_ids)) {
            return new \WP_Error('invalid_ids', 'audit_ids must be an array', array('status' => 400));
        }

        $deleted = $repository->delete_audits($audit_ids);

        return new \WP_REST_Response(array(
            'success' => true,
            'deleted' => $deleted,
            'message' => "Deleted {$deleted} audit(s)",
        ), 200);
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

