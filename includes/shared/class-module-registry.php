<?php
/**
 * Module Registry
 *
 * Manages registration and initialization of all plugin modules.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Module_Registry {

    /**
     * Registered modules
     *
     * @var array
     */
    private $modules = array();

    /**
     * The loader instance
     *
     * @var WP_Staff_Diary_Loader
     */
    private $loader;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Loader $loader The loader instance
     */
    public function __construct($loader) {
        $this->loader = $loader;
    }

    /**
     * Register a module
     *
     * @param WP_Staff_Diary_Module_Interface $module The module to register
     */
    public function register($module) {
        $name = $module->get_name();
        $this->modules[$name] = $module;
    }

    /**
     * Initialize all registered modules
     */
    public function init_all() {
        foreach ($this->modules as $module) {
            $module->init();
            $module->register_hooks($this->loader);
        }
    }

    /**
     * Get a specific module by name
     *
     * @param string $name The module name
     * @return WP_Staff_Diary_Module_Interface|null The module or null if not found
     */
    public function get($name) {
        return isset($this->modules[$name]) ? $this->modules[$name] : null;
    }

    /**
     * Check if a module is registered
     *
     * @param string $name The module name
     * @return bool
     */
    public function has($name) {
        return isset($this->modules[$name]);
    }

    /**
     * Get all registered modules
     *
     * @return array
     */
    public function get_all() {
        return $this->modules;
    }
}
