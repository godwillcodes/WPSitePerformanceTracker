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
     *
     * Loads dependencies and initializes all plugin components.
     *
     * @return void
     */
    public function init(): void {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     *
     * @return void
     */
    private function load_dependencies(): void {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-activator.php';
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-deactivator.php';
    }

    /**
     * Initialize WordPress hooks
     *
     * Registers all plugin components and their hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        \PerfAuditPro\API\Rest_API::init();
        \PerfAuditPro\Cron\Scheduler::init();
        \PerfAuditPro\Frontend\RUM_Enqueue::init();
        \PerfAuditPro\Admin\Admin_Page::init();
        \PerfAuditPro\Admin\Worker_Manager::init();
        \PerfAuditPro\Admin\Settings_Page::init();
        \PerfAuditPro\Admin\Rules_Page::init();
        \PerfAuditPro\Admin\Scheduled_Audits::init();
        \PerfAuditPro\Admin\Export::init();
        \PerfAuditPro\Admin\Notifications::init();
        \PerfAuditPro\Admin\Audit_Details::init();
        \PerfAuditPro\Admin\Performance_Budgets::init();
        \PerfAuditPro\Worker\PHP_Worker::init();
        \PerfAuditPro\Utils\Data_Cleanup::init();
    }

    /**
     * Load plugin textdomain for translations
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'perfaudit-pro',
            false,
            dirname(plugin_basename(PERFAUDIT_PRO_PLUGIN_FILE)) . '/languages'
        );
    }
}

