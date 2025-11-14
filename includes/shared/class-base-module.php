<?php
/**
 * Base Module
 *
 * Provides common functionality for all modules.
 * Handles initialization and hook registration.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

abstract class WP_Staff_Diary_Base_Module implements WP_Staff_Diary_Module_Interface {

    /**
     * The module name
     *
     * @var string
     */
    protected $name;

    /**
     * The controller instance
     *
     * @var WP_Staff_Diary_Controller_Interface
     */
    protected $controller;

    /**
     * Initialize the module
     */
    public function init() {
        // Override in child classes if needed
    }

    /**
     * Get the module name
     *
     * @return string The unique identifier for this module
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Register an AJAX action
     *
     * @param WP_Staff_Diary_Loader $loader The loader instance
     * @param string $action The AJAX action name
     * @param callable $callback The callback function
     * @param bool $nopriv Whether to allow non-logged-in users
     */
    protected function register_ajax_action($loader, $action, $callback, $nopriv = false) {
        $loader->add_action('wp_ajax_' . $action, $this->controller, $callback);

        if ($nopriv) {
            $loader->add_action('wp_ajax_nopriv_' . $action, $this->controller, $callback);
        }
    }
}
