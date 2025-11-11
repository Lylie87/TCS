<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Activator {

    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for diary entries
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        $sql_diary = "CREATE TABLE $table_diary (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            job_date date NOT NULL,
            job_time time DEFAULT NULL,
            client_name varchar(255) DEFAULT NULL,
            client_address text DEFAULT NULL,
            client_phone varchar(50) DEFAULT NULL,
            job_description text DEFAULT NULL,
            plans text DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY job_date (job_date)
        ) $charset_collate;";

        // Table for job images
        $table_images = $wpdb->prefix . 'staff_diary_images';

        $sql_images = "CREATE TABLE $table_images (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            image_url varchar(500) NOT NULL,
            attachment_id bigint(20) DEFAULT NULL,
            image_caption text DEFAULT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id)
        ) $charset_collate;";

        // Table for payments
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

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
        dbDelta($sql_diary);
        dbDelta($sql_images);
        dbDelta($sql_payments);

        // Set default options
        add_option('wp_staff_diary_version', WP_STAFF_DIARY_VERSION);
        add_option('wp_staff_diary_date_format', 'Y-m-d');
    }
}
