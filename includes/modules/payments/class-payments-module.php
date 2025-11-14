<?php
/**
 * Payments Module
 *
 * Handles all payment-related functionality.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Payments_Module extends WP_Staff_Diary_Base_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'payments';

        // Initialize repository and controller
        $repository = new WP_Staff_Diary_Payments_Repository();
        $this->controller = new WP_Staff_Diary_Payments_Controller($repository);
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
        // Register AJAX actions for payments
        $this->register_ajax_action($loader, 'add_payment', 'add');
        $this->register_ajax_action($loader, 'delete_payment', 'delete');
        $this->register_ajax_action($loader, 'get_entry_payments', 'get_entry_payments');
    }
}
