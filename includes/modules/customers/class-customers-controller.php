<?php
/**
 * Customers Controller
 *
 * Handles HTTP/AJAX requests for customer operations.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Customers_Controller extends WP_Staff_Diary_Base_Controller {

    /**
     * The customers repository
     *
     * @var WP_Staff_Diary_Customers_Repository
     */
    private $repository;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Customers_Repository $repository The customers repository
     */
    public function __construct($repository) {
        $this->repository = $repository;
    }

    /**
     * Handle search customers request
     */
    public function search() {
        if (!$this->verify_request()) {
            return;
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $customers = $this->repository->get_all_customers($search);

        $this->send_success(array('customers' => $customers));
    }

    /**
     * Handle add customer request
     */
    public function add() {
        if (!$this->verify_request()) {
            return;
        }

        // Sanitize input
        $data = $this->sanitize_data($_POST, array(
            'customer_name' => 'text',
            'address_line_1' => 'text',
            'address_line_2' => 'text',
            'address_line_3' => 'text',
            'postcode' => 'text',
            'customer_phone' => 'text',
            'customer_email' => 'email',
            'notes' => 'textarea'
        ));

        // Validate required fields
        if (empty($data['customer_name'])) {
            $this->send_error('Customer name is required');
            return;
        }

        // Create customer
        $customer_id = $this->repository->create_customer($data);

        if ($customer_id) {
            $customer = $this->repository->get_customer($customer_id);
            $this->send_success(array(
                'message' => 'Customer added successfully',
                'customer' => $customer
            ));
        } else {
            $this->send_error('Failed to add customer');
        }
    }

    /**
     * Handle get customer request
     */
    public function get() {
        if (!$this->verify_request()) {
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (empty($customer_id)) {
            $this->send_error('Missing required field: customer_id');
            return;
        }

        $customer = $this->repository->get_with_job_count($customer_id);

        if ($customer) {
            $this->send_success(array('customer' => $customer));
        } else {
            $this->send_error('Customer not found');
        }
    }

    /**
     * Handle update customer request
     */
    public function update() {
        if (!$this->verify_request()) {
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (empty($customer_id)) {
            $this->send_error('Missing required field: customer_id');
            return;
        }

        // Sanitize input
        $data = $this->sanitize_data($_POST, array(
            'customer_name' => 'text',
            'address_line_1' => 'text',
            'address_line_2' => 'text',
            'address_line_3' => 'text',
            'postcode' => 'text',
            'customer_phone' => 'text',
            'customer_email' => 'email',
            'notes' => 'textarea'
        ));

        // Validate required fields
        if (empty($data['customer_name'])) {
            $this->send_error('Customer name is required');
            return;
        }

        // Update customer
        $result = $this->repository->update_customer($customer_id, $data);

        if ($result) {
            $customer = $this->repository->get_customer($customer_id);
            $this->send_success(array(
                'message' => 'Customer updated successfully',
                'customer' => $customer
            ));
        } else {
            $this->send_error('Failed to update customer');
        }
    }

    /**
     * Handle delete customer request
     */
    public function delete() {
        if (!$this->verify_request()) {
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (empty($customer_id)) {
            $this->send_error('Missing required field: customer_id');
            return;
        }

        // Check if customer has associated jobs
        $job_count = $this->repository->get_jobs_count($customer_id);

        if ($job_count > 0) {
            $this->send_error("Cannot delete customer. They have {$job_count} associated job(s).");
            return;
        }

        $result = $this->repository->delete_customer($customer_id);

        if ($result) {
            $this->send_success(array('message' => 'Customer deleted successfully'));
        } else {
            $this->send_error('Failed to delete customer');
        }
    }
}
