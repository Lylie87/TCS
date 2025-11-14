<?php
/**
 * Module Interface
 *
 * Defines the contract that all modules must follow.
 * Each module is responsible for a specific feature area.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

interface WP_Staff_Diary_Module_Interface {

    /**
     * Register hooks and filters for this module
     *
     * @param WP_Staff_Diary_Loader $loader The loader instance to register hooks with
     */
    public function register_hooks($loader);

    /**
     * Get the module name
     *
     * @return string The unique identifier for this module
     */
    public function get_name();

    /**
     * Initialize the module
     * Called when the module is first loaded
     */
    public function init();
}
