<?php
/**
 * The core plugin class
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $module_registry;

    public function __construct() {
        $this->version = WP_STAFF_DIARY_VERSION;
        $this->plugin_name = 'wp-staff-diary';

        $this->load_dependencies();
        $this->load_modules();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load plugin text domain for translations
     */
    private function set_locale() {
        $this->loader->add_action('init', $this, 'load_plugin_textdomain');
    }

    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-staff-diary',
            false,
            dirname(plugin_basename(WP_STAFF_DIARY_PATH)) . '/languages/'
        );
    }

    private function load_dependencies() {
        // Core dependencies
        require_once WP_STAFF_DIARY_PATH . 'includes/class-loader.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/class-database.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/class-pdf-generator.php';

        // Services
        require_once WP_STAFF_DIARY_PATH . 'includes/services/class-template-service.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/services/class-sms-service.php';

        // Legacy admin class (for backwards compatibility)
        require_once WP_STAFF_DIARY_PATH . 'admin/class-admin.php';
        require_once WP_STAFF_DIARY_PATH . 'public/class-public.php';

        // New modular architecture - Interfaces
        require_once WP_STAFF_DIARY_PATH . 'includes/interfaces/interface-module.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/interfaces/interface-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/interfaces/interface-repository.php';

        // New modular architecture - Base classes
        require_once WP_STAFF_DIARY_PATH . 'includes/shared/class-base-module.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/shared/class-base-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/shared/class-base-repository.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/shared/class-module-registry.php';

        $this->loader = new WP_Staff_Diary_Loader();
        $this->module_registry = new WP_Staff_Diary_Module_Registry($this->loader);
    }

    /**
     * Load and register all modules
     */
    private function load_modules() {
        // Load Payments module
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/payments/class-payments-repository.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/payments/class-payments-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/payments/class-payments-module.php';
        $payments_module = new WP_Staff_Diary_Payments_Module();
        $this->module_registry->register($payments_module);

        // Load Customers module
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/customers/class-customers-repository.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/customers/class-customers-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/customers/class-customers-module.php';
        $customers_module = new WP_Staff_Diary_Customers_Module();
        $this->module_registry->register($customers_module);

        // Load Jobs module
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/jobs/class-jobs-repository.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/jobs/class-jobs-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/jobs/class-jobs-module.php';
        $jobs_module = new WP_Staff_Diary_Jobs_Module();
        $this->module_registry->register($jobs_module);

        // Load Images module
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/images/class-images-repository.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/images/class-images-controller.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/modules/images/class-images-module.php';
        $images_module = new WP_Staff_Diary_Images_Module();
        $this->module_registry->register($images_module);

        // Initialize all modules
        $this->module_registry->init_all();
    }

    private function define_admin_hooks() {
        $plugin_admin = new WP_Staff_Diary_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'add_dashboard_widget');

        // AJAX handlers - Diary Entries
        // NOTE: Job/diary entry handlers now managed by Jobs module
        // Keeping these for backwards compatibility fallback
        // $this->loader->add_action('wp_ajax_save_diary_entry', $plugin_admin, 'save_diary_entry');
        // $this->loader->add_action('wp_ajax_delete_diary_entry', $plugin_admin, 'delete_diary_entry');
        // $this->loader->add_action('wp_ajax_cancel_diary_entry', $plugin_admin, 'cancel_diary_entry');
        // $this->loader->add_action('wp_ajax_get_diary_entry', $plugin_admin, 'get_diary_entry');

        // AJAX handlers - Images
        // NOTE: Image handlers now managed by Images module
        // Keeping these for backwards compatibility fallback
        // $this->loader->add_action('wp_ajax_upload_job_image', $plugin_admin, 'upload_job_image');
        // $this->loader->add_action('wp_ajax_delete_diary_image', $plugin_admin, 'delete_diary_image');

        // AJAX handlers - Payments
        // NOTE: Payment handlers now managed by Payments module
        // Keeping these for backwards compatibility fallback
        // $this->loader->add_action('wp_ajax_add_payment', $plugin_admin, 'add_payment');
        // $this->loader->add_action('wp_ajax_delete_payment', $plugin_admin, 'delete_payment');

        // AJAX handlers - Statuses
        $this->loader->add_action('wp_ajax_wp_staff_diary_add_status', $plugin_admin, 'add_status');
        $this->loader->add_action('wp_ajax_wp_staff_diary_delete_status', $plugin_admin, 'delete_status');

        // AJAX handlers - Payment Methods
        $this->loader->add_action('wp_ajax_wp_staff_diary_add_payment_method', $plugin_admin, 'add_payment_method');
        $this->loader->add_action('wp_ajax_wp_staff_diary_delete_payment_method', $plugin_admin, 'delete_payment_method');

        // AJAX handlers - Fitters
        $this->loader->add_action('wp_ajax_wp_staff_diary_add_fitter', $plugin_admin, 'add_fitter');
        $this->loader->add_action('wp_ajax_wp_staff_diary_delete_fitter', $plugin_admin, 'delete_fitter');

        // AJAX handlers - Accessories
        $this->loader->add_action('wp_ajax_add_accessory', $plugin_admin, 'add_accessory');
        $this->loader->add_action('wp_ajax_update_accessory', $plugin_admin, 'update_accessory');
        $this->loader->add_action('wp_ajax_delete_accessory', $plugin_admin, 'delete_accessory');

        // AJAX handlers - Quotes & Discounts
        $this->loader->add_action('wp_ajax_send_discount_email', $plugin_admin, 'send_discount_email');

        // AJAX handlers - WooCommerce Integration
        $this->loader->add_action('wp_ajax_search_woocommerce_products', $plugin_admin, 'search_woocommerce_products');

        // AJAX handlers - Database Management (Danger Zone)
        $this->loader->add_action('wp_ajax_wp_staff_diary_delete_all_jobs', $plugin_admin, 'delete_all_jobs');
        $this->loader->add_action('wp_ajax_wp_staff_diary_diagnostics', $plugin_admin, 'run_database_diagnostics');
        $this->loader->add_action('wp_ajax_wp_staff_diary_repair', $plugin_admin, 'repair_database');

        // AJAX handlers - Customers
        // NOTE: Customer handlers now managed by Customers module
        // Keeping these for backwards compatibility fallback
        // $this->loader->add_action('wp_ajax_search_customers', $plugin_admin, 'search_customers');
        // $this->loader->add_action('wp_ajax_add_customer', $plugin_admin, 'add_customer');
        // $this->loader->add_action('wp_ajax_get_customer', $plugin_admin, 'get_customer');
        // $this->loader->add_action('wp_ajax_update_customer', $plugin_admin, 'update_customer');
        // $this->loader->add_action('wp_ajax_delete_customer', $plugin_admin, 'delete_customer');

        // AJAX handlers - PDF Generation
        $this->loader->add_action('wp_ajax_generate_pdf', $plugin_admin, 'generate_pdf');
        $this->loader->add_action('wp_ajax_generate_quote_pdf', $plugin_admin, 'generate_quote_pdf');

        // Action for direct PDF download
        $this->loader->add_action('admin_post_wp_staff_diary_download_pdf', $plugin_admin, 'download_pdf');
        $this->loader->add_action('admin_post_wp_staff_diary_download_quote_pdf', $plugin_admin, 'download_quote_pdf');

        // AJAX handlers - Quotes
        $this->loader->add_action('wp_ajax_convert_quote_to_job', $plugin_admin, 'convert_quote_to_job');
        $this->loader->add_action('wp_ajax_get_fitter_availability', $plugin_admin, 'get_fitter_availability');
        $this->loader->add_action('wp_ajax_email_quote', $plugin_admin, 'email_quote');

        // AJAX handlers - Payment Reminders
        $this->loader->add_action('wp_ajax_send_payment_reminder', $plugin_admin, 'send_payment_reminder');

        // WP-Cron for payment reminders
        $this->loader->add_action('init', $plugin_admin, 'setup_payment_reminder_cron');
        $this->loader->add_action('wp_staff_diary_process_reminders', $plugin_admin, 'process_scheduled_reminders');

        // AJAX handlers - Job Templates
        $this->loader->add_action('wp_ajax_get_job_templates', $plugin_admin, 'get_job_templates');
        $this->loader->add_action('wp_ajax_get_job_template', $plugin_admin, 'get_job_template');
        $this->loader->add_action('wp_ajax_save_job_template', $plugin_admin, 'save_job_template');
        $this->loader->add_action('wp_ajax_delete_job_template', $plugin_admin, 'delete_job_template');

        // AJAX handlers - Activity Log
        $this->loader->add_action('wp_ajax_get_activity_log', $plugin_admin, 'get_activity_log');

        // AJAX handlers - Bulk Actions
        $this->loader->add_action('wp_ajax_bulk_update_status', $plugin_admin, 'bulk_update_status');
        $this->loader->add_action('wp_ajax_bulk_delete_jobs', $plugin_admin, 'bulk_delete_jobs');
        $this->loader->add_action('wp_ajax_bulk_export_jobs', $plugin_admin, 'bulk_export_jobs');

        // AJAX handlers - Customer History
        $this->loader->add_action('wp_ajax_get_customer_jobs', $plugin_admin, 'get_customer_jobs');
    }

    private function define_public_hooks() {
        $plugin_public = new WP_Staff_Diary_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    public function get_module_registry() {
        return $this->module_registry;
    }
}
