<?php
/**
 * Images Module
 *
 * Handles all image upload and management functionality.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Images_Module extends WP_Staff_Diary_Base_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'images';

        // Initialize repository and controller
        $repository = new WP_Staff_Diary_Images_Repository();
        $this->controller = new WP_Staff_Diary_Images_Controller($repository);
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
        // Register AJAX actions for images
        $this->register_ajax_action($loader, 'upload_job_image', 'upload');
        $this->register_ajax_action($loader, 'delete_diary_image', 'delete');
    }
}
