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
     * Force run upgrades (for manual migration)
     */
    public static function force_upgrade() {
        $current_version = get_option('wp_staff_diary_version', '0.0.0');
        self::run_upgrades($current_version);
        update_option('wp_staff_diary_version', WP_STAFF_DIARY_VERSION);
    }

    /**
     * Run necessary upgrades
     */
    private static function run_upgrades($from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // First, ensure base tables exist - if not, run activator
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_diary'");

        if (!$table_exists) {
            // Base tables don't exist, run activator
            require_once WP_STAFF_DIARY_PATH . 'includes/class-activator.php';
            WP_Staff_Diary_Activator::activate();
            return; // Activator creates everything we need
        }

        // Upgrade to v2.0.0 - Create all new tables
        // Also run for 2.0.0 -> 2.0.2 to ensure migration completed
        if (version_compare($from_version, '2.0.2', '<')) {
            self::upgrade_to_2_0_0();
        }

        // Upgrade to v2.0.3 - UK address fields and fitters
        if (version_compare($from_version, '2.0.3', '<')) {
            self::upgrade_to_2_0_3();
        }

        // Upgrade to v2.0.23 - Add fitting_cost field
        if (version_compare($from_version, '2.0.23', '<')) {
            self::upgrade_to_2_0_23();
        }

        // Upgrade to v2.2.0 - Add WooCommerce integration fields
        if (version_compare($from_version, '2.2.0', '<')) {
            self::upgrade_to_2_2_0();
        }

        // Legacy upgrades for older versions
        // Add job_time column if it doesn't exist (only if table exists)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'job_time'");

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN job_time time DEFAULT NULL AFTER job_date");
        }
    }

    /**
     * Upgrade to version 2.0.0
     * Creates all new tables and updates existing ones
     */
    private static function upgrade_to_2_0_0() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create customers table
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
        dbDelta($sql_customers);

        // Create accessories table
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
        dbDelta($sql_accessories);

        // Create job accessories table
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
        dbDelta($sql_job_accessories);

        // Create payments table
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
        dbDelta($sql_payments);

        // Update diary entries table with new columns
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Handle order_number column specially (needs to populate existing rows)
        $order_number_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'order_number'");
        if (empty($order_number_exists)) {
            // Add column as nullable first
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN order_number varchar(50) DEFAULT NULL AFTER id");

            // Get current order number from settings
            $current_order = get_option('wp_staff_diary_order_current', '01100');
            $order_prefix = get_option('wp_staff_diary_order_prefix', '');

            // Populate existing entries with unique order numbers
            $existing_entries = $wpdb->get_results("SELECT id FROM $table_diary WHERE order_number IS NULL ORDER BY id ASC");
            foreach ($existing_entries as $entry) {
                $order_num = $order_prefix . str_pad($current_order, 5, '0', STR_PAD_LEFT);
                $wpdb->update(
                    $table_diary,
                    array('order_number' => $order_num),
                    array('id' => $entry->id)
                );
                $current_order++;
            }

            // Update the current order number
            update_option('wp_staff_diary_order_current', $current_order);

            // Now make it NOT NULL and add UNIQUE constraint
            $wpdb->query("ALTER TABLE $table_diary MODIFY order_number varchar(50) NOT NULL");
            $unique_check = $wpdb->get_results("SHOW INDEX FROM $table_diary WHERE Key_name = 'order_number'");
            if (empty($unique_check)) {
                $wpdb->query("ALTER TABLE $table_diary ADD UNIQUE KEY order_number (order_number)");
            }
        }

        // Add other new columns if they don't exist
        $columns_to_add = array(
            'customer_id' => "ALTER TABLE $table_diary ADD COLUMN customer_id bigint(20) DEFAULT NULL AFTER user_id",
            'fitting_date' => "ALTER TABLE $table_diary ADD COLUMN fitting_date date DEFAULT NULL AFTER job_time",
            'fitting_time_period' => "ALTER TABLE $table_diary ADD COLUMN fitting_time_period varchar(10) DEFAULT NULL AFTER fitting_date",
            'area' => "ALTER TABLE $table_diary ADD COLUMN area varchar(255) DEFAULT NULL AFTER fitting_time_period",
            'size' => "ALTER TABLE $table_diary ADD COLUMN size varchar(255) DEFAULT NULL AFTER area",
            'product_description' => "ALTER TABLE $table_diary ADD COLUMN product_description text DEFAULT NULL AFTER size",
            'sq_mtr_qty' => "ALTER TABLE $table_diary ADD COLUMN sq_mtr_qty decimal(10,2) DEFAULT NULL AFTER product_description",
            'price_per_sq_mtr' => "ALTER TABLE $table_diary ADD COLUMN price_per_sq_mtr decimal(10,2) DEFAULT NULL AFTER sq_mtr_qty",
            'is_cancelled' => "ALTER TABLE $table_diary ADD COLUMN is_cancelled tinyint(1) DEFAULT 0 AFTER status"
        );

        foreach ($columns_to_add as $column_name => $sql) {
            $column_check = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE '$column_name'");
            if (empty($column_check)) {
                $wpdb->query($sql);
            }
        }

        // Add index for customer_id if it doesn't exist
        $index_check = $wpdb->get_results("SHOW INDEX FROM $table_diary WHERE Key_name = 'customer_id'");
        if (empty($index_check)) {
            $wpdb->query("ALTER TABLE $table_diary ADD KEY customer_id (customer_id)");
        }

        // Update image_category column in images table
        $table_images = $wpdb->prefix . 'staff_diary_images';
        $column_check = $wpdb->get_results("SHOW COLUMNS FROM $table_images LIKE 'image_category'");
        if (empty($column_check)) {
            $wpdb->query("ALTER TABLE $table_images ADD COLUMN image_category varchar(50) DEFAULT 'general' AFTER image_caption");
            $wpdb->query("ALTER TABLE $table_images ADD KEY image_category (image_category)");
        }

        // Insert default accessories if table is empty
        $accessories_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_accessories");
        if ($accessories_count == 0) {
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

        // Add v2.0.0 options if they don't exist
        if (get_option('wp_staff_diary_order_start') === false) {
            add_option('wp_staff_diary_order_start', '01100');
        }
        if (get_option('wp_staff_diary_order_prefix') === false) {
            add_option('wp_staff_diary_order_prefix', '');
        }
        if (get_option('wp_staff_diary_order_current') === false) {
            add_option('wp_staff_diary_order_current', '01100');
        }
        if (get_option('wp_staff_diary_vat_enabled') === false) {
            add_option('wp_staff_diary_vat_enabled', '1');
        }
        if (get_option('wp_staff_diary_vat_rate') === false) {
            add_option('wp_staff_diary_vat_rate', '20');
        }
        if (get_option('wp_staff_diary_company_name') === false) {
            add_option('wp_staff_diary_company_name', '');
        }
        if (get_option('wp_staff_diary_company_address') === false) {
            add_option('wp_staff_diary_company_address', '');
        }
        if (get_option('wp_staff_diary_company_phone') === false) {
            add_option('wp_staff_diary_company_phone', '');
        }
        if (get_option('wp_staff_diary_company_email') === false) {
            add_option('wp_staff_diary_company_email', '');
        }
        if (get_option('wp_staff_diary_company_vat_number') === false) {
            add_option('wp_staff_diary_company_vat_number', '');
        }
        if (get_option('wp_staff_diary_company_reg_number') === false) {
            add_option('wp_staff_diary_company_reg_number', '');
        }
        if (get_option('wp_staff_diary_company_bank_details') === false) {
            add_option('wp_staff_diary_company_bank_details', '');
        }
        if (get_option('wp_staff_diary_company_logo') === false) {
            add_option('wp_staff_diary_company_logo', '');
        }
        if (get_option('wp_staff_diary_terms_conditions') === false) {
            add_option('wp_staff_diary_terms_conditions', '');
        }
        if (get_option('wp_staff_diary_payment_methods') === false) {
            add_option('wp_staff_diary_payment_methods', array(
                'cash' => 'Cash',
                'bank-transfer' => 'Bank Transfer',
                'card-payment' => 'Card Payment'
            ));
        }

        // v2.0.2 options - Job time settings
        if (get_option('wp_staff_diary_job_time_type') === false) {
            add_option('wp_staff_diary_job_time_type', 'ampm');
        }
        if (get_option('wp_staff_diary_fitting_time_length') === false) {
            add_option('wp_staff_diary_fitting_time_length', '0');
        }
    }

    /**
     * Upgrade to version 2.0.3
     * Convert customer address to UK format and add fitters support
     */
    private static function upgrade_to_2_0_3() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_customers = $wpdb->prefix . 'staff_diary_customers';
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Check if customer_address column exists (old format)
        $old_address_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_customers LIKE 'customer_address'");

        if (!empty($old_address_exists)) {
            // Add new UK address columns
            $wpdb->query("ALTER TABLE $table_customers
                ADD COLUMN address_line_1 varchar(255) DEFAULT NULL AFTER customer_name,
                ADD COLUMN address_line_2 varchar(255) DEFAULT NULL AFTER address_line_1,
                ADD COLUMN address_line_3 varchar(255) DEFAULT NULL AFTER address_line_2,
                ADD COLUMN postcode varchar(20) DEFAULT NULL AFTER address_line_3");

            // Migrate existing data: put old address into address_line_1
            $wpdb->query("UPDATE $table_customers SET address_line_1 = customer_address WHERE customer_address IS NOT NULL");

            // Drop old column
            $wpdb->query("ALTER TABLE $table_customers DROP COLUMN customer_address");
        }

        // Add fitter_id column to diary entries
        $fitter_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'fitter_id'");
        if (empty($fitter_column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN fitter_id bigint(20) DEFAULT NULL AFTER user_id");
            $wpdb->query("ALTER TABLE $table_diary ADD KEY fitter_id (fitter_id)");
        }

        // Add fitters option if it doesn't exist
        if (get_option('wp_staff_diary_fitters') === false) {
            add_option('wp_staff_diary_fitters', array());
        }
    }

    /**
     * Upgrade to version 2.0.23
     * Add fitting_cost field for customer fitting charges
     */
    private static function upgrade_to_2_0_23() {
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Add fitting_cost column
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'fitting_cost'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN fitting_cost decimal(10,2) DEFAULT 0.00 AFTER price_per_sq_mtr");
        }
    }

    /**
     * Upgrade to version 2.2.0
     * Add WooCommerce integration fields
     */
    private static function upgrade_to_2_2_0() {
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Add product_source column (manual or woocommerce)
        $source_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'product_source'");
        if (empty($source_column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN product_source varchar(20) DEFAULT 'manual' AFTER product_description");
        }

        // Add woocommerce_product_id column
        $wc_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_diary LIKE 'woocommerce_product_id'");
        if (empty($wc_id_column_exists)) {
            $wpdb->query("ALTER TABLE $table_diary ADD COLUMN woocommerce_product_id bigint(20) DEFAULT NULL AFTER product_source");
            $wpdb->query("ALTER TABLE $table_diary ADD KEY woocommerce_product_id (woocommerce_product_id)");
        }
    }
}
