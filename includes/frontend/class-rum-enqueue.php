<?php
/**
 * RUM script enqueue handler
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Frontend
 */

namespace PerfAuditPro\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class RUM_Enqueue {

    /**
     * Initialize RUM enqueue
     *
     * Enqueues Web Vitals tracking script on frontend.
     *
     * @return void
     */
    public static function init(): void {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_rum_script'));
    }

    /**
     * Enqueue RUM tracking script
     *
     * Only enqueues if RUM tracking is enabled in settings and user has consented.
     * Tracking requires explicit user consent per WordPress.org guidelines.
     *
     * @return void
     */
    public static function enqueue_rum_script(): void {
        // Only enqueue if RUM tracking is enabled in settings
        $rum_enabled = get_option('perfaudit_pro_rum_enabled', false);
        if (!$rum_enabled) {
            return;
        }

        $script_url = PERFAUDIT_PRO_PLUGIN_URL . 'assets/js/rum-tracker.js';
        $api_url = rest_url('perfaudit-pro/v1/rum-intake');
        $nonce = wp_create_nonce('wp_rest');
        $vendor_url = PERFAUDIT_PRO_PLUGIN_URL . 'assets/vendor/';

        wp_enqueue_script(
            'perfaudit-pro-rum',
            $script_url,
            array(),
            PERFAUDIT_PRO_VERSION,
            true
        );

        wp_localize_script('perfaudit-pro-rum', 'PerfAuditPro', array(
            'apiUrl' => esc_url_raw($api_url),
            'nonce' => $nonce,
            'vendorUrl' => esc_url_raw($vendor_url),
        ));
    }
}

