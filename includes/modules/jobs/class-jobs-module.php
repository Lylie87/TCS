<?php
/**
 * Jobs Module
 *
 * Handles all job/diary entry functionality.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Jobs_Module extends WP_Staff_Diary_Base_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'jobs';

        // Initialize repository and controller
        $repository = new WP_Staff_Diary_Jobs_Repository();
        $this->controller = new WP_Staff_Diary_Jobs_Controller($repository);
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
        // Register AJAX actions for jobs
        $this->register_ajax_action($loader, 'save_diary_entry', 'save');
        $this->register_ajax_action($loader, 'get_diary_entry', 'get');
        $this->register_ajax_action($loader, 'delete_diary_entry', 'delete');
        $this->register_ajax_action($loader, 'cancel_diary_entry', 'cancel');
    }
}
