<?php
/**
 * Customers Module
 *
 * Handles all customer-related functionality.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Customers_Module extends WP_Staff_Diary_Base_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'customers';

        // Initialize repository and controller
        $repository = new WP_Staff_Diary_Customers_Repository();
        $this->controller = new WP_Staff_Diary_Customers_Controller($repository);
    }

    /**
     * Initialize the module
     */
    public function init() {
        // Any initialization logic can go here
    }

    /**
     * Register hooks and filters
     *
     * @param WP_Staff_Diary_Loader $loader The loader instance
     */
    public function register_hooks($loader) {
        // Register AJAX actions for customers
        $this->register_ajax_action($loader, 'search_customers', 'search');
        $this->register_ajax_action($loader, 'add_customer', 'add');
        $this->register_ajax_action($loader, 'get_customer', 'get');
        $this->register_ajax_action($loader, 'update_customer', 'update');
        $this->register_ajax_action($loader, 'delete_customer', 'delete');
    }
}
