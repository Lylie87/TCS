<?php
/**
 * Controller Interface
 *
 * Controllers handle HTTP/AJAX requests and responses.
 * They coordinate between the view layer and the business logic.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

interface WP_Staff_Diary_Controller_Interface {

    /**
     * Verify the current request has proper nonce and permissions
     *
     * @param string $nonce_action The nonce action to verify
     * @param string $capability The required capability
     * @return bool True if authorized, false otherwise
     */
    public function verify_request($nonce_action, $capability);

    /**
     * Send a JSON success response
     *
     * @param mixed $data The data to send
     */
    public function send_success($data);

    /**
     * Send a JSON error response
     *
     * @param string $message The error message
     * @param int $code Optional HTTP status code
     */
    public function send_error($message, $code = 400);
}
