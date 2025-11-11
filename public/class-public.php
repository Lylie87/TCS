<?php
/**
 * The public-facing functionality of the plugin
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side
     */
    public function enqueue_styles() {
        // Currently not needed for admin-only plugin
        // Can be used if public shortcodes are added later
    }

    /**
     * Register the JavaScript for the public-facing side
     */
    public function enqueue_scripts() {
        // Currently not needed for admin-only plugin
        // Can be used if public shortcodes are added later
    }
}
