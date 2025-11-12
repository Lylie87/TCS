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

        // AJAX handlers
        $this->loader->add_action('wp_ajax_save_diary_entry', $plugin_admin, 'save_diary_entry');
        $this->loader->add_action('wp_ajax_delete_diary_entry', $plugin_admin, 'delete_diary_entry');
        $this->loader->add_action('wp_ajax_upload_job_image', $plugin_admin, 'upload_job_image');
        $this->loader->add_action('wp_ajax_get_diary_entry', $plugin_admin, 'get_diary_entry');
        $this->loader->add_action('wp_ajax_delete_diary_image', $plugin_admin, 'delete_diary_image');
        $this->loader->add_action('wp_ajax_add_payment', $plugin_admin, 'add_payment');
        $this->loader->add_action('wp_ajax_delete_payment', $plugin_admin, 'delete_payment');
        $this->loader->add_action('wp_ajax_add_status', $plugin_admin, 'add_status');
        $this->loader->add_action('wp_ajax_delete_status', $plugin_admin, 'delete_status');
        $this->loader->add_action('wp_ajax_add_payment_method', $plugin_admin, 'add_payment_method');
        $this->loader->add_action('wp_ajax_delete_payment_method', $plugin_admin, 'delete_payment_method');
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
