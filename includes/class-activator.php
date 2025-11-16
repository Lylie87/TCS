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
            address_line_1 varchar(255) DEFAULT NULL,
            address_line_2 varchar(255) DEFAULT NULL,
            address_line_3 varchar(255) DEFAULT NULL,
            postcode varchar(20) DEFAULT NULL,
            customer_phone varchar(50) DEFAULT NULL,
            customer_email varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_name (customer_name),
            KEY postcode (postcode)
        ) $charset_collate;";

        // Table for diary entries (jobs/orders)
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        $sql_diary = "CREATE TABLE $table_diary (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            customer_id bigint(20) DEFAULT NULL,
            fitter_id int(11) DEFAULT NULL,
            job_date date DEFAULT NULL,
            job_time time DEFAULT NULL,
            fitting_date date DEFAULT NULL,
            fitting_date_unknown tinyint(1) DEFAULT 0,
            fitting_time_period varchar(10) DEFAULT NULL,
            billing_address_line_1 varchar(255) DEFAULT NULL,
            billing_address_line_2 varchar(255) DEFAULT NULL,
            billing_address_line_3 varchar(255) DEFAULT NULL,
            billing_postcode varchar(20) DEFAULT NULL,
            fitting_address_different tinyint(1) DEFAULT 0,
            fitting_address_line_1 varchar(255) DEFAULT NULL,
            fitting_address_line_2 varchar(255) DEFAULT NULL,
            fitting_address_line_3 varchar(255) DEFAULT NULL,
            fitting_postcode varchar(20) DEFAULT NULL,
            area varchar(255) DEFAULT NULL,
            size varchar(255) DEFAULT NULL,
            product_description text DEFAULT NULL,
            product_source varchar(20) DEFAULT 'manual',
            woocommerce_product_id bigint(20) DEFAULT NULL,
            sq_mtr_qty decimal(10,2) DEFAULT NULL,
            price_per_sq_mtr decimal(10,2) DEFAULT NULL,
            fitting_cost decimal(10,2) DEFAULT 0.00,
            notes text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            job_type varchar(20) DEFAULT 'residential',
            is_cancelled tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY customer_id (customer_id),
            KEY fitter_id (fitter_id),
            KEY job_date (job_date),
            KEY fitting_date (fitting_date),
            KEY fitting_date_unknown (fitting_date_unknown),
            KEY status (status),
            KEY job_type (job_type),
            KEY woocommerce_product_id (woocommerce_product_id)
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

        // Table for notification logs
        $table_notification_logs = $wpdb->prefix . 'staff_diary_notification_logs';

        $sql_notification_logs = "CREATE TABLE $table_notification_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) DEFAULT NULL,
            notification_type varchar(50) NOT NULL,
            recipient varchar(255) NOT NULL,
            method varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            error_message text DEFAULT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY notification_type (notification_type),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        // Table for payment reminder schedules
        $table_reminder_schedule = $wpdb->prefix . 'staff_diary_reminder_schedule';

        $sql_reminder_schedule = "CREATE TABLE $table_reminder_schedule (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            reminder_type varchar(50) NOT NULL,
            scheduled_for datetime NOT NULL,
            sent_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY scheduled_for (scheduled_for),
            KEY status (status),
            KEY reminder_type (reminder_type)
        ) $charset_collate;";

        // Table for job templates
        $table_job_templates = $wpdb->prefix . 'staff_diary_job_templates';

        $sql_job_templates = "CREATE TABLE $table_job_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_description text DEFAULT NULL,
            product_description text DEFAULT NULL,
            sq_mtr_qty decimal(10,2) DEFAULT NULL,
            price_per_sq_mtr decimal(10,2) DEFAULT NULL,
            fitting_cost decimal(10,2) DEFAULT 0.00,
            accessories_json text DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            is_global tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY template_name (template_name),
            KEY created_by (created_by),
            KEY is_global (is_global)
        ) $charset_collate;";

        // Table for job activity log (status timeline/history)
        $table_activity_log = $wpdb->prefix . 'staff_diary_activity_log';

        $sql_activity_log = "CREATE TABLE $table_activity_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_description text NOT NULL,
            old_value varchar(255) DEFAULT NULL,
            new_value varchar(255) DEFAULT NULL,
            metadata text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY activity_type (activity_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_customers);
        dbDelta($sql_diary);
        dbDelta($sql_images);
        dbDelta($sql_payments);
        dbDelta($sql_accessories);
        dbDelta($sql_job_accessories);
        dbDelta($sql_notification_logs);
        dbDelta($sql_reminder_schedule);
        dbDelta($sql_job_templates);
        dbDelta($sql_activity_log);

        // Add job_type column to existing installations
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'job_type'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN job_type varchar(20) DEFAULT 'residential' AFTER status");
            $wpdb->query("ALTER TABLE $table_diary ADD INDEX job_type (job_type)");
        }

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

        // Payment reminder settings
        add_option('wp_staff_diary_payment_reminders_enabled', '1');
        add_option('wp_staff_diary_payment_reminder_1_days', '7');  // First reminder after 7 days
        add_option('wp_staff_diary_payment_reminder_2_days', '14'); // Second reminder after 14 days
        add_option('wp_staff_diary_payment_reminder_3_days', '21'); // Final reminder after 21 days
        add_option('wp_staff_diary_payment_reminder_subject', 'Payment Reminder - Invoice {order_number}');
        add_option('wp_staff_diary_payment_reminder_message', "Dear {customer_name},\n\nThis is a friendly reminder that payment is still outstanding for the following job:\n\nInvoice Number: {order_number}\nJob Date: {job_date}\nTotal Amount: {total_amount}\nAmount Outstanding: {balance}\n\nIf you have already made this payment, please disregard this reminder.\n\nThank you for your business.");

        // Payment terms settings
        add_option('wp_staff_diary_payment_terms_number', '30');  // Default 30 days
        add_option('wp_staff_diary_payment_terms_unit', 'days');   // days, weeks, months, years
        add_option('wp_staff_diary_payment_policy', 'both');       // both, commercial, residential, none
        add_option('wp_staff_diary_overdue_notification_email', get_option('admin_email')); // Default to WordPress admin email

        // Bank details
        add_option('wp_staff_diary_bank_name', '');
        add_option('wp_staff_diary_bank_account_name', '');
        add_option('wp_staff_diary_bank_account_number', '');
        add_option('wp_staff_diary_bank_sort_code', '');

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
