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

    public function __construct() {
        $this->version = WP_STAFF_DIARY_VERSION;
        $this->plugin_name = 'wp-staff-diary';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WP_STAFF_DIARY_PATH . 'includes/class-loader.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/class-database.php';
        require_once WP_STAFF_DIARY_PATH . 'includes/class-pdf-generator.php';
        require_once WP_STAFF_DIARY_PATH . 'admin/class-admin.php';
        require_once WP_STAFF_DIARY_PATH . 'public/class-public.php';

        $this->loader = new WP_Staff_Diary_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new WP_Staff_Diary_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'add_dashboard_widget');

        // AJAX handlers - Diary Entries
        $this->loader->add_action('wp_ajax_save_diary_entry', $plugin_admin, 'save_diary_entry');
        $this->loader->add_action('wp_ajax_delete_diary_entry', $plugin_admin, 'delete_diary_entry');
        $this->loader->add_action('wp_ajax_cancel_diary_entry', $plugin_admin, 'cancel_diary_entry');
        $this->loader->add_action('wp_ajax_get_diary_entry', $plugin_admin, 'get_diary_entry');

        // AJAX handlers - Images
        $this->loader->add_action('wp_ajax_upload_job_image', $plugin_admin, 'upload_job_image');
        $this->loader->add_action('wp_ajax_delete_diary_image', $plugin_admin, 'delete_diary_image');

        // AJAX handlers - Payments
        $this->loader->add_action('wp_ajax_add_payment', $plugin_admin, 'add_payment');
        $this->loader->add_action('wp_ajax_delete_payment', $plugin_admin, 'delete_payment');

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

        // AJAX handlers - Customers
        $this->loader->add_action('wp_ajax_search_customers', $plugin_admin, 'search_customers');
        $this->loader->add_action('wp_ajax_add_customer', $plugin_admin, 'add_customer');
        $this->loader->add_action('wp_ajax_get_customer', $plugin_admin, 'get_customer');
        $this->loader->add_action('wp_ajax_update_customer', $plugin_admin, 'update_customer');
        $this->loader->add_action('wp_ajax_delete_customer', $plugin_admin, 'delete_customer');

        // AJAX handlers - PDF Generation
        $this->loader->add_action('wp_ajax_generate_pdf', $plugin_admin, 'generate_pdf');

        // Action for direct PDF download
        $this->loader->add_action('admin_post_wp_staff_diary_download_pdf', $plugin_admin, 'download_pdf');
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
}
