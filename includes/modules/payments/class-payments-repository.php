<?php
/**
 * Payments Repository
 *
 * Handles all database operations for payments.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Payments_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_payments');
    }

    /**
     * Get all payments for a specific diary entry
     *
     * @param int $entry_id The diary entry ID
     * @return array Array of payment records
     */
    public function get_entry_payments($entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE diary_entry_id = %d ORDER BY recorded_at DESC",
            $entry_id
        );
        return $this->wpdb->get_results($sql);
    }

    /**
     * Get total payments for a diary entry
     *
     * @param int $entry_id The diary entry ID
     * @return float Total payment amount
     */
    public function get_entry_total($entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->table} WHERE diary_entry_id = %d",
            $entry_id
        );
        $total = $this->wpdb->get_var($sql);
        return $total ? floatval($total) : 0.00;
    }

    /**
     * Add a new payment
     *
     * @param int $entry_id The diary entry ID
     * @param float $amount The payment amount
     * @param string $payment_method The payment method
     * @param string $payment_type The payment type
     * @param string $notes Optional notes
     * @param int $recorded_by User ID who recorded the payment
     * @return int|false The payment ID or false on failure
     */
    public function add_payment($entry_id, $amount, $payment_method, $payment_type, $notes = '', $recorded_by = 0) {
        $data = array(
            'diary_entry_id' => $entry_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'notes' => $notes,
            'recorded_by' => $recorded_by,
            'recorded_at' => current_time('mysql')
        );

        return $this->create($data);
    }

    /**
     * Get payment with formatted user information
     *
     * @param int $payment_id The payment ID
     * @return object|null Payment record with user info
     */
    public function get_with_user_info($payment_id) {
        $payment = $this->find_by_id($payment_id);

        if ($payment) {
            $user = get_userdata($payment->recorded_by);
            $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';

            // Format date
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
            $time_format = get_option('wp_staff_diary_time_format', 'H:i');
            $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
        }

        return $payment;
    }

    /**
     * Get all payments with user information
     *
     * @param int $entry_id The diary entry ID
     * @return array Array of payment records with user info
     */
    public function get_entry_payments_with_user_info($entry_id) {
        $payments = $this->get_entry_payments($entry_id);

        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
        $time_format = get_option('wp_staff_diary_time_format', 'H:i');

        foreach ($payments as $payment) {
            $user = get_userdata($payment->recorded_by);
            $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';
            $payment->amount_formatted = '£' . number_format($payment->amount, 2);
            $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
        }

        return $payments;
    }
}
