<?php
/**
 * Notifications Controller
 *
 * Handles notification sending (Email and SMS).
 *
 * @since      2.2.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Notifications_Controller extends WP_Staff_Diary_Base_Controller {

    /**
     * The notifications repository
     *
     * @var WP_Staff_Diary_Notifications_Repository
     */
    private $repository;

    /**
     * Database instance for accessing jobs and customers
     *
     * @var WP_Staff_Diary_Database
     */
    private $db;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Notifications_Repository $repository
     */
    public function __construct($repository) {
        $this->repository = $repository;
        $this->db = new WP_Staff_Diary_Database();
    }

    /**
     * Send appointment reminder notification
     *
     * @param int $entry_id Job entry ID
     * @param string $method 'email', 'sms', or 'both'
     * @return bool Success
     */
    public function send_appointment_reminder($entry_id, $method = 'email') {
        $entry = $this->db->get_diary_entry($entry_id);

        if (!$entry) {
            return false;
        }

        $customer = $this->db->get_customer($entry->customer_id);

        if (!$customer) {
            return false;
        }

        $success = true;

        // Send email
        if (in_array($method, array('email', 'both')) && $customer->customer_email) {
            $email_sent = $this->send_reminder_email($entry, $customer);
            $success = $success && $email_sent;

            $this->repository->log_notification(array(
                'diary_entry_id' => $entry_id,
                'notification_type' => 'appointment_reminder',
                'recipient' => $customer->customer_email,
                'method' => 'email',
                'status' => $email_sent ? 'sent' : 'failed',
                'sent_at' => current_time('mysql')
            ));
        }

        // Send SMS
        if (in_array($method, array('sms', 'both')) && $customer->customer_phone) {
            $sms_sent = $this->send_reminder_sms($entry, $customer);
            $success = $success && $sms_sent;

            $this->repository->log_notification(array(
                'diary_entry_id' => $entry_id,
                'notification_type' => 'appointment_reminder',
                'recipient' => $customer->customer_phone,
                'method' => 'sms',
                'status' => $sms_sent ? 'sent' : 'failed',
                'sent_at' => current_time('mysql')
            ));
        }

        return $success;
    }

    /**
     * Send payment confirmation notification
     *
     * @param int $payment_id Payment ID
     * @param string $method 'email', 'sms', or 'both'
     * @return bool Success
     */
    public function send_payment_confirmation($payment_id, $method = 'email') {
        global $wpdb;

        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}staff_diary_payments WHERE id = %d",
            $payment_id
        ));

        if (!$payment) {
            return false;
        }

        $entry = $this->db->get_diary_entry($payment->diary_entry_id);
        $customer = $this->db->get_customer($entry->customer_id);

        if (!$customer) {
            return false;
        }

        $success = true;

        // Send email
        if (in_array($method, array('email', 'both')) && $customer->customer_email) {
            $email_sent = $this->send_payment_email($entry, $customer, $payment);
            $success = $success && $email_sent;

            $this->repository->log_notification(array(
                'diary_entry_id' => $entry->id,
                'notification_type' => 'payment_confirmation',
                'recipient' => $customer->customer_email,
                'method' => 'email',
                'status' => $email_sent ? 'sent' : 'failed',
                'sent_at' => current_time('mysql')
            ));
        }

        // Send SMS
        if (in_array($method, array('sms', 'both')) && $customer->customer_phone) {
            $sms_sent = $this->send_payment_sms($entry, $customer, $payment);
            $success = $success && $sms_sent;

            $this->repository->log_notification(array(
                'diary_entry_id' => $entry->id,
                'notification_type' => 'payment_confirmation',
                'recipient' => $customer->customer_phone,
                'method' => 'sms',
                'status' => $sms_sent ? 'sent' : 'failed',
                'sent_at' => current_time('mysql')
            ));
        }

        return $success;
    }

    /**
     * Send reminder email
     *
     * @param object $entry Job entry
     * @param object $customer Customer
     * @return bool Success
     */
    private function send_reminder_email($entry, $customer) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $fitting_date = date('l, F j, Y', strtotime($entry->fitting_date));
        $fitting_time = $entry->fitting_time_period ? $entry->fitting_time_period : 'TBC';

        $subject = "Appointment Reminder from $company_name";

        $message = "Dear {$customer->customer_name},\n\n";
        $message .= "This is a reminder of your upcoming appointment with {$company_name}.\n\n";
        $message .= "Date: {$fitting_date}\n";
        $message .= "Time: {$fitting_time}\n";
        $message .= "Job: {$entry->product_description}\n\n";

        if ($entry->fitting_address_different && $entry->fitting_address_line_1) {
            $message .= "Location:\n";
            $message .= "{$entry->fitting_address_line_1}\n";
            if ($entry->fitting_address_line_2) $message .= "{$entry->fitting_address_line_2}\n";
            if ($entry->fitting_address_line_3) $message .= "{$entry->fitting_address_line_3}\n";
            if ($entry->fitting_postcode) $message .= "{$entry->fitting_postcode}\n";
        }

        $message .= "\nIf you need to reschedule, please contact us as soon as possible.\n\n";
        $message .= "Thank you,\n{$company_name}";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        return wp_mail($customer->customer_email, $subject, $message, $headers);
    }

    /**
     * Send payment confirmation email
     *
     * @param object $entry Job entry
     * @param object $customer Customer
     * @param object $payment Payment
     * @return bool Success
     */
    private function send_payment_email($entry, $customer, $payment) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));

        $subject = "Payment Received - {$company_name}";

        $message = "Dear {$customer->customer_name},\n\n";
        $message .= "Thank you for your payment of £" . number_format($payment->amount, 2) . ".\n\n";
        $message .= "Payment Details:\n";
        $message .= "Order Number: {$entry->order_number}\n";
        $message .= "Amount: £" . number_format($payment->amount, 2) . "\n";
        $message .= "Payment Method: {$payment->payment_method}\n";
        $message .= "Payment Type: {$payment->payment_type}\n";
        $message .= "Date: " . date('F j, Y', strtotime($payment->recorded_at)) . "\n\n";

        // Calculate remaining balance
        $subtotal = $this->db->calculate_job_subtotal($entry->id);
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        $total = $subtotal;
        if ($vat_enabled == '1') {
            $total = $subtotal * (1 + ($vat_rate / 100));
        }

        $payments_total = $this->db->get_entry_total_payments($entry->id);
        $balance = $total - $payments_total;

        if ($balance > 0) {
            $message .= "Remaining Balance: £" . number_format($balance, 2) . "\n\n";
        } else {
            $message .= "Your account is now PAID IN FULL. Thank you!\n\n";
        }

        $message .= "Thank you for your business!\n\n";
        $message .= "{$company_name}";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        return wp_mail($customer->customer_email, $subject, $message, $headers);
    }

    /**
     * Send reminder SMS
     *
     * @param object $entry Job entry
     * @param object $customer Customer
     * @return bool Success
     */
    private function send_reminder_sms($entry, $customer) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $fitting_date = date('d/m/Y', strtotime($entry->fitting_date));
        $fitting_time = $entry->fitting_time_period ? $entry->fitting_time_period : 'TBC';

        $message = "Reminder: Your appointment with {$company_name} is scheduled for {$fitting_date} at {$fitting_time}.";

        return $this->send_sms($customer->customer_phone, $message);
    }

    /**
     * Send payment confirmation SMS
     *
     * @param object $entry Job entry
     * @param object $customer Customer
     * @param object $payment Payment
     * @return bool Success
     */
    private function send_payment_sms($entry, $customer, $payment) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $amount = number_format($payment->amount, 2);

        $message = "Payment received: £{$amount}. Thank you! - {$company_name}";

        return $this->send_sms($customer->customer_phone, $message);
    }

    /**
     * Send SMS via Twilio or other provider
     *
     * @param string $to Phone number
     * @param string $message Message content
     * @return bool Success
     */
    private function send_sms($to, $message) {
        // Check if SMS is enabled
        $sms_enabled = get_option('wp_staff_diary_sms_enabled', '0');
        if ($sms_enabled != '1') {
            return false;
        }

        // Get Twilio credentials
        $twilio_sid = get_option('wp_staff_diary_twilio_sid', '');
        $twilio_token = get_option('wp_staff_diary_twilio_token', '');
        $twilio_from = get_option('wp_staff_diary_twilio_from', '');

        if (empty($twilio_sid) || empty($twilio_token) || empty($twilio_from)) {
            return false;
        }

        try {
            // Twilio API endpoint
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilio_sid}/Messages.json";

            // Prepare data
            $data = array(
                'From' => $twilio_from,
                'To' => $to,
                'Body' => $message
            );

            // Send request
            $response = wp_remote_post($url, array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode("{$twilio_sid}:{$twilio_token}")
                ),
                'body' => $data,
                'timeout' => 15
            ));

            if (is_wp_error($response)) {
                error_log('Twilio SMS Error: ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            return ($response_code >= 200 && $response_code < 300);

        } catch (Exception $e) {
            error_log('SMS Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * AJAX: Send test notification
     */
    public function send_test_notification() {
        if (!$this->verify_request()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            $this->send_error('Permission denied');
            return;
        }

        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'email';
        $recipient = isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '';

        if (empty($recipient)) {
            $this->send_error('Recipient is required');
            return;
        }

        if ($method === 'email') {
            $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
            $subject = "Test Notification from {$company_name}";
            $message = "This is a test email notification from your Job Planner plugin.\n\nIf you received this, your email notifications are working correctly!";
            $headers = array('Content-Type: text/plain; charset=UTF-8');

            $sent = wp_mail($recipient, $subject, $message, $headers);
        } else {
            $sent = $this->send_sms($recipient, "Test SMS from Job Planner. Your SMS notifications are working!");
        }

        if ($sent) {
            $this->send_success(array('message' => 'Test notification sent successfully!'));
        } else {
            $this->send_error('Failed to send test notification. Check your settings and logs.');
        }
    }
}
