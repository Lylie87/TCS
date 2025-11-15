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

        // Include WooCommerce customers if WooCommerce is active
        if (class_exists('WooCommerce') && !empty($search)) {
            $wc_customers = $this->search_woocommerce_customers($search);

            // Merge WooCommerce customers with plugin customers
            // Add a flag to distinguish WooCommerce customers
            foreach ($wc_customers as $wc_customer) {
                $wc_customer->is_woocommerce = true;
                $customers[] = $wc_customer;
            }
        }

        $this->send_success(array('customers' => $customers));
    }

    /**
     * Search WooCommerce customers
     *
     * @param string $search Search term
     * @return array Array of customer objects
     */
    private function search_woocommerce_customers($search) {
        $wc_customers = array();

        // Search WooCommerce customers by name or email
        $customer_query = new WP_User_Query(array(
            'role' => 'customer',
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 20
        ));

        $users = $customer_query->get_results();

        foreach ($users as $user) {
            $customer = new stdClass();
            $customer->id = 'wc_' . $user->ID; // Prefix to distinguish from plugin customers
            $customer->customer_name = $user->display_name ? $user->display_name : $user->user_login;
            $customer->customer_email = $user->user_email;

            // Get billing address from WooCommerce user meta
            $customer->customer_phone = get_user_meta($user->ID, 'billing_phone', true);
            $customer->address_line_1 = get_user_meta($user->ID, 'billing_address_1', true);
            $customer->address_line_2 = get_user_meta($user->ID, 'billing_address_2', true);
            $customer->address_line_3 = get_user_meta($user->ID, 'billing_city', true);
            $customer->postcode = get_user_meta($user->ID, 'billing_postcode', true);

            $wc_customers[] = $customer;
        }

        return $wc_customers;
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

        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : '';

        if (empty($customer_id)) {
            $this->send_error('Missing required field: customer_id');
            return;
        }

        // Check if this is a WooCommerce customer
        if (is_string($customer_id) && strpos($customer_id, 'wc_') === 0) {
            $wc_user_id = intval(substr($customer_id, 3)); // Remove 'wc_' prefix
            $customer = $this->get_woocommerce_customer($wc_user_id);

            if ($customer) {
                $this->send_success(array('customer' => $customer));
            } else {
                $this->send_error('WooCommerce customer not found');
            }
            return;
        }

        // Regular plugin customer
        $customer_id = intval($customer_id);
        $customer = $this->repository->get_with_job_count($customer_id);

        if ($customer) {
            $this->send_success(array('customer' => $customer));
        } else {
            $this->send_error('Customer not found');
        }
    }

    /**
     * Get WooCommerce customer by user ID
     *
     * @param int $user_id WordPress user ID
     * @return object|null Customer object or null if not found
     */
    private function get_woocommerce_customer($user_id) {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $user = get_user_by('ID', $user_id);

        if (!$user || !in_array('customer', $user->roles)) {
            return null;
        }

        $customer = new stdClass();
        $customer->id = 'wc_' . $user->ID;
        $customer->customer_name = $user->display_name ? $user->display_name : $user->user_login;
        $customer->customer_email = $user->user_email;
        $customer->customer_phone = get_user_meta($user->ID, 'billing_phone', true);
        $customer->address_line_1 = get_user_meta($user->ID, 'billing_address_1', true);
        $customer->address_line_2 = get_user_meta($user->ID, 'billing_address_2', true);
        $customer->address_line_3 = get_user_meta($user->ID, 'billing_city', true);
        $customer->postcode = get_user_meta($user->ID, 'billing_postcode', true);
        $customer->is_woocommerce = true;

        return $customer;
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
