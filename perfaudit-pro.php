<?php
/**
 * Plugin Name: PerfAudit Pro
 * Plugin URI: https://github.com/godwillcodes/PerfAuditPro
 * Description: Automated website performance audits using synthetic lab audits and Real User Monitoring
 * Version: 1.0.0
 * Author: PerfAudit Pro Team
 * Author URI: https://github.com/godwillcodes/PerfAuditPro
 * License: Proprietary
 * Text Domain: perfaudit-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Namespace: PerfAuditPro
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PERFAUDIT_PRO_VERSION', '1.0.0');
define('PERFAUDIT_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PERFAUDIT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERFAUDIT_PRO_PLUGIN_FILE', __FILE__);

require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/class-autoloader.php';

PerfAuditPro\Autoloader::init();

register_activation_hook(__FILE__, array('PerfAuditPro\Core\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PerfAuditPro\Core\Deactivator', 'deactivate'));

PerfAuditPro\Core\Plugin::get_instance()->init();

