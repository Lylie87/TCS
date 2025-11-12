<?php
/**
 * Plugin Name: Staff Daily Job Planner
 * Plugin URI: https://www.express-websites.co.uk/wp-staff-diary
 * Description: A daily job planning and management system for staff members with image uploads and detailed job tracking.
 * Version: 2.0.3
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
define('WP_STAFF_DIARY_VERSION', '2.0.3');

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
 * Check for upgrades
 */
require_once WP_STAFF_DIARY_PATH . 'includes/class-upgrade.php';
add_action('plugins_loaded', array('WP_Staff_Diary_Upgrade', 'check_upgrades'));

/**
 * Begins execution of the plugin
 */
function run_wp_staff_diary() {
    $plugin = new WP_Staff_Diary();
    $plugin->run();
}
run_wp_staff_diary();
