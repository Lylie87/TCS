<?php
/**
 * Payments Controller
 *
 * Handles HTTP/AJAX requests for payment operations.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Payments_Controller extends WP_Staff_Diary_Base_Controller {

    /**
     * The payments repository
     *
     * @var WP_Staff_Diary_Payments_Repository
     */
    private $repository;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Payments_Repository $repository The payments repository
     */
    public function __construct($repository) {
        $this->repository = $repository;
    }

    /**
     * Handle add payment request
     */
    public function add() {
        if (!$this->verify_request()) {
            return;
        }

        // Sanitize input
        $data = $this->sanitize_data($_POST, array(
            'entry_id' => 'int',
            'amount' => 'float',
            'payment_method' => 'text',
            'payment_type' => 'text',
            'notes' => 'textarea'
        ));

        // Validate required fields
        if (empty($data['entry_id']) || empty($data['amount'])) {
            $this->send_error('Missing required fields: entry_id and amount');
            return;
        }

        // Add payment
        $payment_id = $this->repository->add_payment(
            $data['entry_id'],
            $data['amount'],
            $data['payment_method'] ?? 'cash',
            $data['payment_type'] ?? 'full',
            $data['notes'] ?? '',
            get_current_user_id()
        );

        if ($payment_id) {
            $payment = $this->repository->get_with_user_info($payment_id);
            $this->send_success(array(
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ));
        } else {
            $this->send_error('Failed to record payment');
        }
    }

    /**
     * Handle delete payment request
     */
    public function delete() {
        if (!$this->verify_request()) {
            return;
        }

        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;

        if (empty($payment_id)) {
            $this->send_error('Missing required field: payment_id');
            return;
        }

        $result = $this->repository->delete($payment_id);

        if ($result) {
            $this->send_success(array('message' => 'Payment deleted successfully'));
        } else {
            $this->send_error('Failed to delete payment');
        }
    }

    /**
     * Get payments for an entry
     */
    public function get_entry_payments() {
        if (!$this->verify_request()) {
            return;
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        if (empty($entry_id)) {
            $this->send_error('Missing required field: entry_id');
            return;
        }

        $payments = $this->repository->get_entry_payments_with_user_info($entry_id);
        $total = $this->repository->get_entry_total($entry_id);

        $this->send_success(array(
            'payments' => $payments,
            'total' => $total,
            'total_formatted' => '£' . number_format($total, 2)
        ));
    }
}
