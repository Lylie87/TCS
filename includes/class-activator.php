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
            sms_opt_in tinyint(1) DEFAULT 1,
            sms_opt_in_date datetime DEFAULT NULL,
            sms_opt_out_date datetime DEFAULT NULL,
            customer_email varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_name (customer_name),
            KEY postcode (postcode),
            KEY sms_opt_in (sms_opt_in)
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
            quote_date date DEFAULT NULL,
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
            discount_type varchar(20) DEFAULT NULL,
            discount_value decimal(10,2) DEFAULT NULL,
            discount_applied_date datetime DEFAULT NULL,
            acceptance_token varchar(64) DEFAULT NULL,
            accepted_date datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            is_cancelled tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY customer_id (customer_id),
            KEY fitter_id (fitter_id),
            KEY job_date (job_date),
            KEY quote_date (quote_date),
            KEY fitting_date (fitting_date),
            KEY fitting_date_unknown (fitting_date_unknown),
            KEY status (status),
            KEY woocommerce_product_id (woocommerce_product_id),
            KEY acceptance_token (acceptance_token)
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

        // Table for discount offers history
        $table_discount_offers = $wpdb->prefix . 'staff_diary_discount_offers';

        $sql_discount_offers = "CREATE TABLE $table_discount_offers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            discount_type varchar(20) NOT NULL,
            discount_value decimal(10,2) NOT NULL,
            email_sent_date datetime DEFAULT NULL,
            sent_by bigint(20) DEFAULT NULL,
            accepted_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'sent',
            email_content text DEFAULT NULL,
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY status (status),
            KEY email_sent_date (email_sent_date)
        ) $charset_collate;";

        // Table for email templates
        $table_email_templates = $wpdb->prefix . 'staff_diary_email_templates';

        $sql_email_templates = "CREATE TABLE $table_email_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_slug varchar(100) NOT NULL,
            subject varchar(500) NOT NULL,
            body longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_slug (template_slug),
            KEY is_active (is_active),
            KEY is_default (is_default)
        ) $charset_collate;";

        // Table for SMS log
        $table_sms_log = $wpdb->prefix . 'staff_diary_sms_log';

        $sql_sms_log = "CREATE TABLE $table_sms_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) DEFAULT NULL,
            customer_id bigint(20) DEFAULT NULL,
            phone_number varchar(20) NOT NULL,
            message text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            twilio_sid varchar(100) DEFAULT NULL,
            cost decimal(10,4) DEFAULT 0.0000,
            error_message text DEFAULT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        // Table for comments (timestamped notes/comments on jobs/quotes/measures)
        $table_comments = $wpdb->prefix . 'staff_diary_comments';

        $sql_comments = "CREATE TABLE $table_comments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            diary_entry_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            comment_text text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY diary_entry_id (diary_entry_id),
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
        dbDelta($sql_discount_offers);
        dbDelta($sql_email_templates);
        dbDelta($sql_sms_log);
        dbDelta($sql_comments);

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

        // Insert default email templates
        $template_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_email_templates");

        if ($template_count == 0) {
            $default_templates = array(
                array(
                    'template_name' => 'Payment Reminder',
                    'template_slug' => 'payment_reminder',
                    'subject' => 'Payment Reminder - Invoice {{job_number}}',
                    'body' => "Dear {{customer_name}},\n\nThis is a friendly reminder that you have an outstanding balance of £{{balance_due}} for job {{job_number}}.\n\nJob Details:\n- Date: {{job_date}}\n- Description: {{job_description}}\n- Total: £{{total_amount}}\n- Paid: £{{paid_amount}}\n- Balance: £{{balance_due}}\n\nPlease arrange payment at your earliest convenience.\n\nBank Details:\n{{bank_name}}\nAccount Name: {{bank_account_name}}\nSort Code: {{bank_sort_code}}\nAccount Number: {{bank_account_number}}\n\nThank you,\n{{company_name}}",
                    'is_default' => 1
                ),
                array(
                    'template_name' => 'Quote Approved',
                    'template_slug' => 'quote_approved',
                    'subject' => 'Your Quote {{quote_number}} Has Been Approved',
                    'body' => "Dear {{customer_name}},\n\nGreat news! Your quote {{quote_number}} has been approved and we're ready to start work.\n\nQuote Details:\n- Quote Number: {{quote_number}}\n- Total Amount: £{{total_amount}}\n- Scheduled Date: {{job_date}}\n- Description: {{job_description}}\n\nWe'll be in touch shortly to confirm the details.\n\nBest regards,\n{{company_name}}",
                    'is_default' => 1
                ),
                array(
                    'template_name' => 'Job Complete',
                    'template_slug' => 'job_complete',
                    'subject' => 'Job {{job_number}} Completed',
                    'body' => "Dear {{customer_name}},\n\nWe're pleased to confirm that job {{job_number}} has been completed successfully.\n\nJob Details:\n- Job Number: {{job_number}}\n- Date Completed: {{current_date}}\n- Description: {{job_description}}\n\nThank you for choosing {{company_name}}. We appreciate your business!\n\nIf you have any questions or concerns, please don't hesitate to contact us.\n\nBest regards,\n{{company_name}}\n{{company_phone}}\n{{company_email}}",
                    'is_default' => 1
                )
            );

            foreach ($default_templates as $template) {
                $wpdb->insert(
                    $table_email_templates,
                    array(
                        'template_name' => $template['template_name'],
                        'template_slug' => $template['template_slug'],
                        'subject' => $template['subject'],
                        'body' => $template['body'],
                        'is_active' => 1,
                        'is_default' => $template['is_default']
                    )
                );
            }
        }

        // SMS Settings (default disabled until configured)
        add_option('wp_staff_diary_sms_enabled', '0');
        add_option('wp_staff_diary_twilio_account_sid', '');
        add_option('wp_staff_diary_twilio_auth_token', '');
        add_option('wp_staff_diary_twilio_phone_number', '');
        add_option('wp_staff_diary_sms_cost_per_message', '0.04'); // Default £0.04
        add_option('wp_staff_diary_sms_test_mode', '1'); // Test mode enabled by default

        // Flag to flush rewrite rules for quote acceptance URLs
        update_option('wp_staff_diary_flush_rewrite_rules', '1');
    }
}
