<?php
/**
 * Handle plugin upgrades and database migrations
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Upgrade {

    /**
     * Check and run upgrades if needed
     */
    public static function check_upgrades() {
        $current_version = get_option('wp_staff_diary_version', '0.0.0');

        if (version_compare($current_version, WP_STAFF_DIARY_VERSION, '<')) {
            self::run_upgrades($current_version);
            update_option('wp_staff_diary_version', WP_STAFF_DIARY_VERSION);
        }
    }

    /**
     * Run necessary upgrades
     */
    private static function run_upgrades($from_version) {
        global $wpdb;

        // Add job_time column if it doesn't exist
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'job_time'");

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN job_time time DEFAULT NULL AFTER job_date");
        }

        // Create payments table if it doesn't exist
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_payments'");

        if ($table_exists != $table_payments) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql_payments = "CREATE TABLE $table_payments (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                diary_entry_id bigint(20) NOT NULL,
                amount decimal(10,2) NOT NULL,
                payment_method varchar(100) DEFAULT NULL,
                payment_type varchar(100) DEFAULT NULL,
                notes text DEFAULT NULL,
                recorded_by bigint(20) NOT NULL,
                recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY diary_entry_id (diary_entry_id),
                KEY recorded_by (recorded_by)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_payments);
        }
    }
}
