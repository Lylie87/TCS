<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop the custom tables
$table_diary = $wpdb->prefix . 'staff_diary_entries';
$table_images = $wpdb->prefix . 'staff_diary_images';

$wpdb->query("DROP TABLE IF EXISTS $table_images");
$wpdb->query("DROP TABLE IF EXISTS $table_diary");

// Delete plugin options
delete_option('wp_staff_diary_version');
delete_option('wp_staff_diary_date_format');

// Note: This will permanently delete all diary data
// Consider adding a backup option before uninstalling
