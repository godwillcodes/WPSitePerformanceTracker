<?php
/**
 * Plugin activation handler
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Core
 */

namespace PerfAuditPro\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-schema.php';
        \PerfAuditPro\Database\Schema::create_tables();
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        if (!get_option('perfaudit_pro_version')) {
            add_option('perfaudit_pro_version', PERFAUDIT_PRO_VERSION);
        }

        // Auto-generate API token if not exists
        if (!get_option('perfaudit_pro_api_token')) {
            $token = bin2hex(random_bytes(32));
            add_option('perfaudit_pro_api_token', $token);
        }

        // Auto-configure worker
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/admin/class-worker-manager.php';
        \PerfAuditPro\Admin\Worker_Manager::auto_configure();
    }
}

