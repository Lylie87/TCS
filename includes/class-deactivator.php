<?php
/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Deactivator {

    public static function deactivate() {
        // Clear any scheduled events or temporary data
        // Note: We don't drop tables on deactivation, only on uninstall
    }
}
