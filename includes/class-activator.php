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

        // Table for customers
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        $sql_customers = "CREATE TABLE $table_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_name varchar(255) NOT NULL,
            customer_address text DEFAULT NULL,
            customer_phone varchar(50) DEFAULT NULL,
            customer_email varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_name (customer_name)
        ) $charset_collate;";

        // Table for diary entries (jobs/orders)
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        $sql_diary = "CREATE TABLE $table_diary (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL UNIQUE,
            user_id bigint(20) NOT NULL,
            customer_id bigint(20) DEFAULT NULL,
            job_date date DEFAULT NULL,
            job_time time DEFAULT NULL,
            fitting_date date DEFAULT NULL,
            fitting_time_period varchar(10) DEFAULT NULL,
            area varchar(255) DEFAULT NULL,
            size varchar(255) DEFAULT NULL,
            product_description text DEFAULT NULL,
            sq_mtr_qty decimal(10,2) DEFAULT NULL,
            price_per_sq_mtr decimal(10,2) DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            is_cancelled tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY customer_id (customer_id),
            KEY job_date (job_date),
            KEY status (status)
        ) $charset_collate;";

        // Table for job images
        $table_images = $wpdb->prefix . 'staff_diary_images';

        $sql_images = "CREATE TABLE $table_images (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            image_url varchar(500) NOT NULL,
            attachment_id bigint(20) DEFAULT NULL,
            image_caption text DEFAULT NULL,
            image_category varchar(50) DEFAULT 'general',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY image_category (image_category)
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

        // Table for accessories (master list)
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        $sql_accessories = "CREATE TABLE $table_accessories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            accessory_name varchar(255) NOT NULL,
            price decimal(10,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY accessory_name (accessory_name),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Table for job accessories (items selected for each job)
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        $sql_job_accessories = "CREATE TABLE $table_job_accessories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            accessory_id bigint(20) NOT NULL,
            accessory_name varchar(255) NOT NULL,
            quantity decimal(10,2) DEFAULT 1,
            price_per_unit decimal(10,2) DEFAULT 0.00,
            total_price decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY accessory_id (accessory_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_customers);
        dbDelta($sql_diary);
        dbDelta($sql_images);
        dbDelta($sql_payments);
        dbDelta($sql_accessories);
        dbDelta($sql_job_accessories);

        // Set default options
        add_option('wp_staff_diary_version', WP_STAFF_DIARY_VERSION);
        add_option('wp_staff_diary_date_format', 'd/m/Y');
        add_option('wp_staff_diary_time_format', 'H:i');
        add_option('wp_staff_diary_week_start', 'monday');
        add_option('wp_staff_diary_default_status', 'pending');

        // Order settings
        add_option('wp_staff_diary_order_start', '01100');
        add_option('wp_staff_diary_order_prefix', '');
        add_option('wp_staff_diary_order_current', '01100');

        // VAT settings
        add_option('wp_staff_diary_vat_enabled', '1');
        add_option('wp_staff_diary_vat_rate', '20');

        // Company details
        add_option('wp_staff_diary_company_name', '');
        add_option('wp_staff_diary_company_address', '');
        add_option('wp_staff_diary_company_phone', '');
        add_option('wp_staff_diary_company_email', '');
        add_option('wp_staff_diary_company_vat_number', '');
        add_option('wp_staff_diary_company_reg_number', '');
        add_option('wp_staff_diary_company_bank_details', '');
        add_option('wp_staff_diary_company_logo', '');

        // Terms and conditions
        add_option('wp_staff_diary_terms_conditions', '');

        // Job statuses
        add_option('wp_staff_diary_statuses', array(
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ));

        // Payment methods
        add_option('wp_staff_diary_payment_methods', array(
            'cash' => 'Cash',
            'bank-transfer' => 'Bank Transfer',
            'card-payment' => 'Card Payment'
        ));

        // Insert default accessories (only if table is empty)
        $accessory_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_accessories");

        if ($accessory_count == 0) {
            $default_accessories = array(
                array('name' => 'U/Lay', 'price' => 0.00),
                array('name' => 'S/Edge', 'price' => 0.00),
                array('name' => 'Plates', 'price' => 0.00),
                array('name' => 'Adhesive', 'price' => 4.99),
                array('name' => 'Screed', 'price' => 0.00),
                array('name' => 'Plyboard', 'price' => 0.00),
            );

            foreach ($default_accessories as $index => $accessory) {
                $wpdb->insert(
                    $table_accessories,
                    array(
                        'accessory_name' => $accessory['name'],
                        'price' => $accessory['price'],
                        'is_active' => 1,
                        'display_order' => $index
                    )
                );
            }
        }
    }
}
