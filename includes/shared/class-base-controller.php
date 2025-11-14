<?php
/**
 * Base Controller
 *
 * Provides common functionality for all controllers.
 * Handles request verification, responses, and error handling.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

abstract class WP_Staff_Diary_Base_Controller implements WP_Staff_Diary_Controller_Interface {

    /**
     * Verify the current request has proper nonce and permissions
     *
     * @param string $nonce_action The nonce action to verify
     * @param string $capability The required capability
     * @return bool True if authorized, false otherwise
     */
    public function verify_request($nonce_action = 'wp_staff_diary_nonce', $capability = 'edit_posts') {
        // Check nonce
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            $this->send_error('Invalid security token.');
            return false;
        }

        // Check capability
        if (!current_user_can($capability)) {
            $this->send_error('You do not have permission to perform this action.');
            return false;
        }

        return true;
    }

    /**
     * Send a JSON success response
     *
     * @param mixed $data The data to send
     */
    public function send_success($data) {
        wp_send_json_success($data);
    }

    /**
     * Send a JSON error response
     *
     * @param string $message The error message
     * @param int $code Optional HTTP status code
     */
    public function send_error($message, $code = 400) {
        wp_send_json_error(array('message' => $message), $code);
    }

    /**
     * Sanitize input data
     *
     * @param array $data The data to sanitize
     * @param array $fields Field definitions (name => type)
     * @return array Sanitized data
     */
    protected function sanitize_data($data, $fields) {
        $sanitized = array();

        foreach ($fields as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            switch ($type) {
                case 'text':
                    $sanitized[$field] = sanitize_text_field($data[$field]);
                    break;
                case 'textarea':
                    $sanitized[$field] = sanitize_textarea_field($data[$field]);
                    break;
                case 'email':
                    $sanitized[$field] = sanitize_email($data[$field]);
                    break;
                case 'int':
                    $sanitized[$field] = intval($data[$field]);
                    break;
                case 'float':
                    $sanitized[$field] = floatval($data[$field]);
                    break;
                case 'bool':
                    $sanitized[$field] = (bool) $data[$field];
                    break;
                case 'array':
                    $sanitized[$field] = is_array($data[$field]) ? $data[$field] : array();
                    break;
                default:
                    $sanitized[$field] = sanitize_text_field($data[$field]);
            }
        }

        return $sanitized;
    }
}
