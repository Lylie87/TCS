<?php
/**
 * Plugin Name: Staff Daily Job Planner
 * Plugin URI: https://www.express-websites.co.uk/wp-staff-diary
 * Description: A daily job planning and management system for staff members with image uploads and detailed job tracking.
 * Version: 3.3.1
 * Author: Alex Lyle
 * Author URI: https://www.express-websites.co.uk
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-staff-diary
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WP_STAFF_DIARY_VERSION', '3.3.1');

// Plugin paths
define('WP_STAFF_DIARY_PATH', plugin_dir_path(__FILE__));
define('WP_STAFF_DIARY_URL', plugin_dir_url(__FILE__));

/**
 * Activation hook - creates database tables
 */
function activate_wp_staff_diary() {
    require_once WP_STAFF_DIARY_PATH . 'includes/class-activator.php';
    WP_Staff_Diary_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_wp_staff_diary');

/**
 * Deactivation hook
 */
function deactivate_wp_staff_diary() {
    require_once WP_STAFF_DIARY_PATH . 'includes/class-deactivator.php';
    WP_Staff_Diary_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_wp_staff_diary');

/**
 * The core plugin class
 */
require WP_STAFF_DIARY_PATH . 'includes/class-wp-staff-diary.php';

/**
 * Load database class early (needed by other classes)
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-database.php';

/**
 * Currency helper class
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-currency-helper.php';

/**
 * Email template processor
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-email-template-processor.php';

/**
 * Quote acceptance handler
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-quote-acceptance.php';
new WP_Staff_Diary_Quote_Acceptance();

/**
 * Auto discount scheduler
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-auto-discount-scheduler.php';
new WP_Staff_Diary_Auto_Discount_Scheduler();

/**
 * Check for upgrades
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-upgrade.php';
add_action('plugins_loaded', array('WP_Staff_Diary_Upgrade', 'check_upgrades'));

/**
 * Initialize GitHub Auto-Updater
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-github-updater.php';
if (is_admin()) {
    new WP_Staff_Diary_GitHub_Updater(
        __FILE__,
        'Lylie87',  // GitHub username
        'TCS',      // Repository name
        WP_STAFF_DIARY_VERSION  // Pass version to avoid early get_plugin_data() call
    );
}

/**
 * Begins execution of the plugin
 */
function run_wp_staff_diary() {
    $plugin = new WP_Staff_Diary();
    $plugin->run();
}
run_wp_staff_diary();
