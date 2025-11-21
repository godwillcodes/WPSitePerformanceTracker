<?php
/**
 * Security sanitization utilities
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Security
 */

namespace PerfAuditPro\Security;

if (!defined('ABSPATH')) {
    exit;
}

class Sanitizer {

    /**
     * Sanitize metrics array
     *
     * @param array $metrics Raw metrics
     * @return array Sanitized metrics
     */
    public static function sanitize_metrics($metrics) {
        if (!is_array($metrics)) {
            return array();
        }

        $sanitized = array();
        $allowed_keys = array('lcp', 'fid', 'cls', 'fcp', 'ttfb');

        foreach ($allowed_keys as $key) {
            if (isset($metrics[$key])) {
                $sanitized[$key] = floatval($metrics[$key]);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize JSON string
     *
     * @param string $json JSON string
     * @return string|false Sanitized JSON or false on failure
     */
    public static function sanitize_json($json) {
        if (!is_string($json)) {
            return false;
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return wp_json_encode($decoded);
    }

    /**
     * Sanitize IP address
     *
     * @param string $ip IP address
     * @return string Sanitized IP
     */
    public static function sanitize_ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    /**
     * Escape output for HTML
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public static function escape_html($value) {
        return esc_html($value);
    }

    /**
     * Escape output for JavaScript
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public static function escape_js($value) {
        return esc_js($value);
    }

    /**
     * Escape output for attributes
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public static function escape_attr($value) {
        return esc_attr($value);
    }

    /**
     * Escape output for URL
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public static function escape_url($value) {
        return esc_url_raw($value);
    }
}

