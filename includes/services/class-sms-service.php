<?php
/**
 * SMS Service - Twilio Integration
 *
 * Handles SMS sending via Twilio with test mode support
 *
 * @since      2.7.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_SMS_Service {

    /**
     * Send SMS message
     *
     * @param string $to Phone number in E.164 format (e.g., +441234567890)
     * @param string $message Message text
     * @param int|null $diary_entry_id Optional diary entry ID for logging
     * @param int|null $customer_id Optional customer ID for logging
     * @return array Result with 'success' (bool), 'message' (string), 'sid' (string|null), 'cost' (float)
     */
    public static function send_sms($to, $message, $diary_entry_id = null, $customer_id = null) {
        // Check if SMS is enabled
        $sms_enabled = get_option('wp_staff_diary_sms_enabled', '0');
        if ($sms_enabled !== '1') {
            return array(
                'success' => false,
                'message' => 'SMS sending is disabled in settings',
                'sid' => null,
                'cost' => 0.0
            );
        }

        // Get settings
        $test_mode = get_option('wp_staff_diary_sms_test_mode', '1');
        $account_sid = get_option('wp_staff_diary_twilio_account_sid', '');
        $auth_token = get_option('wp_staff_diary_twilio_auth_token', '');
        $from_number = get_option('wp_staff_diary_twilio_phone_number', '');
        $cost_per_message = floatval(get_option('wp_staff_diary_sms_cost_per_message', '0.04'));

        // Validate configuration
        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            self::log_sms($diary_entry_id, $customer_id, $to, $message, 'failed', null, 0.0, 'Twilio credentials not configured');
            return array(
                'success' => false,
                'message' => 'Twilio credentials not configured. Please check SMS settings.',
                'sid' => null,
                'cost' => 0.0
            );
        }

        // Validate phone number format (basic check)
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $to)) {
            self::log_sms($diary_entry_id, $customer_id, $to, $message, 'failed', null, 0.0, 'Invalid phone number format. Must be E.164 format (e.g., +441234567890)');
            return array(
                'success' => false,
                'message' => 'Invalid phone number format. Must be E.164 format (e.g., +441234567890)',
                'sid' => null,
                'cost' => 0.0
            );
        }

        // Test mode - simulate sending
        if ($test_mode === '1') {
            $test_sid = 'TEST_' . uniqid();
            self::log_sms($diary_entry_id, $customer_id, $to, $message, 'test', $test_sid, 0.0, null);

            error_log("WP Staff Diary SMS Test Mode: Would send to $to: $message");

            return array(
                'success' => true,
                'message' => 'SMS sent in test mode (not actually delivered)',
                'sid' => $test_sid,
                'cost' => 0.0
            );
        }

        // Real mode - send via Twilio
        try {
            $result = self::send_via_twilio($account_sid, $auth_token, $from_number, $to, $message);

            if ($result['success']) {
                self::log_sms($diary_entry_id, $customer_id, $to, $message, 'sent', $result['sid'], $cost_per_message, null);
                return array(
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'sid' => $result['sid'],
                    'cost' => $cost_per_message
                );
            } else {
                self::log_sms($diary_entry_id, $customer_id, $to, $message, 'failed', null, 0.0, $result['error']);
                return array(
                    'success' => false,
                    'message' => 'Failed to send SMS: ' . $result['error'],
                    'sid' => null,
                    'cost' => 0.0
                );
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            self::log_sms($diary_entry_id, $customer_id, $to, $message, 'failed', null, 0.0, $error_message);

            error_log("WP Staff Diary SMS Error: " . $error_message);

            return array(
                'success' => false,
                'message' => 'Exception while sending SMS: ' . $error_message,
                'sid' => null,
                'cost' => 0.0
            );
        }
    }

    /**
     * Send SMS via Twilio API
     *
     * @param string $account_sid Twilio Account SID
     * @param string $auth_token Twilio Auth Token
     * @param string $from Phone number to send from
     * @param string $to Phone number to send to
     * @param string $message Message text
     * @return array Result with 'success' (bool), 'sid' (string|null), 'error' (string|null)
     */
    private static function send_via_twilio($account_sid, $auth_token, $from, $to, $message) {
        // Twilio API endpoint
        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";

        // Prepare request data
        $data = array(
            'From' => $from,
            'To' => $to,
            'Body' => $message
        );

        // Make HTTP request using WordPress HTTP API
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("$account_sid:$auth_token"),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $data,
            'timeout' => 30
        ));

        // Check for HTTP errors
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'sid' => null,
                'error' => $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);

        // Check HTTP status code
        if ($status_code >= 200 && $status_code < 300) {
            // Success - extract message SID
            $sid = isset($response_data['sid']) ? $response_data['sid'] : null;
            return array(
                'success' => true,
                'sid' => $sid,
                'error' => null
            );
        } else {
            // Error - extract error message
            $error = 'Unknown error';
            if (isset($response_data['message'])) {
                $error = $response_data['message'];
            } elseif (isset($response_data['error_message'])) {
                $error = $response_data['error_message'];
            }

            return array(
                'success' => false,
                'sid' => null,
                'error' => "HTTP $status_code: $error"
            );
        }
    }

    /**
     * Log SMS message to database
     *
     * @param int|null $diary_entry_id Diary entry ID
     * @param int|null $customer_id Customer ID
     * @param string $phone_number Phone number
     * @param string $message Message text
     * @param string $status Status (sent, failed, test, pending)
     * @param string|null $twilio_sid Twilio message SID
     * @param float $cost Cost of message
     * @param string|null $error_message Error message if failed
     * @return int|false Log ID or false on failure
     */
    private static function log_sms($diary_entry_id, $customer_id, $phone_number, $message, $status, $twilio_sid, $cost, $error_message) {
        $db = new WP_Staff_Diary_Database();

        return $db->log_sms(array(
            'diary_entry_id' => $diary_entry_id,
            'customer_id' => $customer_id,
            'phone_number' => $phone_number,
            'message' => $message,
            'status' => $status,
            'twilio_sid' => $twilio_sid,
            'cost' => $cost,
            'error_message' => $error_message
        ));
    }

    /**
     * Send SMS using template with variable replacement
     *
     * @param string $to Phone number
     * @param string $message_template Message template with variables
     * @param array $data Data for variable replacement
     * @param int|null $diary_entry_id Optional diary entry ID
     * @param int|null $customer_id Optional customer ID
     * @return array Result from send_sms()
     */
    public static function send_templated_sms($to, $message_template, $data, $diary_entry_id = null, $customer_id = null) {
        // Replace variables in message
        $message = WP_Staff_Diary_Template_Service::replace_variables($message_template, $data);

        // Trim and truncate if needed (SMS has 160 char limit for single message, 1600 for concatenated)
        $message = trim($message);

        // Send SMS
        return self::send_sms($to, $message, $diary_entry_id, $customer_id);
    }

    /**
     * Check if customer has opted in to SMS
     *
     * @param int $customer_id Customer ID
     * @return bool True if opted in, false otherwise
     */
    public static function is_customer_opted_in($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT sms_opt_in FROM $table_customers WHERE id = %d",
            $customer_id
        ));

        return $result === '1' || $result === 1;
    }

    /**
     * Opt customer in to SMS
     *
     * @param int $customer_id Customer ID
     * @return bool Success
     */
    public static function opt_in_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->update(
            $table_customers,
            array(
                'sms_opt_in' => 1,
                'sms_opt_in_date' => current_time('mysql'),
                'sms_opt_out_date' => null
            ),
            array('id' => $customer_id)
        ) !== false;
    }

    /**
     * Opt customer out of SMS
     *
     * @param int $customer_id Customer ID
     * @return bool Success
     */
    public static function opt_out_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->update(
            $table_customers,
            array(
                'sms_opt_in' => 0,
                'sms_opt_out_date' => current_time('mysql')
            ),
            array('id' => $customer_id)
        ) !== false;
    }

    /**
     * Get SMS statistics
     *
     * @param string|null $start_date Optional start date (Y-m-d)
     * @param string|null $end_date Optional end date (Y-m-d)
     * @return array Statistics with counts and costs
     */
    public static function get_sms_stats($start_date = null, $end_date = null) {
        global $wpdb;
        $table_sms_log = $wpdb->prefix . 'staff_diary_sms_log';

        $where = "1=1";
        $params = array();

        if ($start_date && $end_date) {
            $where .= " AND sent_at BETWEEN %s AND %s";
            $params[] = $start_date . ' 00:00:00';
            $params[] = $end_date . ' 23:59:59';
        }

        $sql_base = "SELECT COUNT(*) as count, SUM(cost) as total_cost FROM $table_sms_log WHERE $where";

        // Total sent
        $sql_sent = $sql_base . " AND status = 'sent'";
        $sent_stats = $wpdb->get_row(!empty($params) ? $wpdb->prepare($sql_sent, $params) : $sql_sent);

        // Total failed
        $sql_failed = $sql_base . " AND status = 'failed'";
        $failed_stats = $wpdb->get_row(!empty($params) ? $wpdb->prepare($sql_failed, $params) : $sql_failed);

        // Total test
        $sql_test = $sql_base . " AND status = 'test'";
        $test_stats = $wpdb->get_row(!empty($params) ? $wpdb->prepare($sql_test, $params) : $sql_test);

        // Total all
        $sql_all = $sql_base;
        $all_stats = $wpdb->get_row(!empty($params) ? $wpdb->prepare($sql_all, $params) : $sql_all);

        return array(
            'sent' => array(
                'count' => intval($sent_stats->count ?? 0),
                'cost' => floatval($sent_stats->total_cost ?? 0.0)
            ),
            'failed' => array(
                'count' => intval($failed_stats->count ?? 0),
                'cost' => floatval($failed_stats->total_cost ?? 0.0)
            ),
            'test' => array(
                'count' => intval($test_stats->count ?? 0),
                'cost' => floatval($test_stats->total_cost ?? 0.0)
            ),
            'total' => array(
                'count' => intval($all_stats->count ?? 0),
                'cost' => floatval($all_stats->total_cost ?? 0.0)
            )
        );
    }

    /**
     * Format phone number to E.164 format
     *
     * @param string $phone Phone number in various formats
     * @param string $default_country_code Default country code (e.g., '44' for UK)
     * @return string|false Phone number in E.164 format, or false if invalid
     */
    public static function format_phone_number($phone, $default_country_code = '44') {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // Already in E.164 format (starts with +)
        if (strpos($phone, '+') === 0) {
            return $phone;
        }

        // Remove leading zeros
        $digits = ltrim($digits, '0');

        // If starts with country code, add +
        if (strlen($digits) > 10) {
            return '+' . $digits;
        }

        // Add default country code
        return '+' . $default_country_code . $digits;
    }

    /**
     * Validate phone number
     *
     * @param string $phone Phone number
     * @return bool True if valid E.164 format
     */
    public static function is_valid_phone_number($phone) {
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone) === 1;
    }
}
