<?php
/**
 * Plugin Name: Site Performance Tracker
 * Plugin URI: https://github.com/godwillcodes/PerfAuditPro
 * Description: Track and monitor your website's performance with automated synthetic audits powered by Google PageSpeed Insights API and Real User Monitoring (RUM) metrics. Get comprehensive performance insights, Core Web Vitals tracking, and actionable recommendations to improve your site's speed and user experience.
 * Version: 1.0.0
 * Author: Godwill Barasa
 * Author URI: https://github.com/godwillcodes/PerfAuditPro
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

// Load core classes required for hooks (before autoloader can handle them)
require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-activator.php';
require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-deactivator.php';
require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/core/class-plugin.php';

register_activation_hook(__FILE__, array('PerfAuditPro\Core\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PerfAuditPro\Core\Deactivator', 'deactivate'));

// Initialize plugin after WordPress is loaded
add_action('plugins_loaded', function() {
    if (class_exists('PerfAuditPro\Core\Plugin')) {
        PerfAuditPro\Core\Plugin::get_instance()->init();
    }
}, 1);

