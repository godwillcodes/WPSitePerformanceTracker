<?php
/**
 * Main plugin class
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Core
 */

namespace PerfAuditPro\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {

    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-activator.php';
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-deactivator.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        \PerfAuditPro\API\Rest_API::init();
        \PerfAuditPro\Cron\Scheduler::init();
        \PerfAuditPro\Frontend\RUM_Enqueue::init();
        \PerfAuditPro\Admin\Admin_Page::init();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'perfaudit-pro',
            false,
            dirname(plugin_basename(PERFAUDIT_PRO_PLUGIN_FILE)) . '/languages'
        );
    }
}

