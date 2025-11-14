<?php
/**
 * Customers Repository
 *
 * Handles all database operations for customers.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Customers_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_customers');
    }

    /**
     * Get all customers with optional search
     *
     * @param string $search Optional search term
     * @return array Array of customer records
     */
    public function get_all_customers($search = '') {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($search)) {
            $sql .= $this->wpdb->prepare(
                " WHERE customer_name LIKE %s
                  OR address_line_1 LIKE %s
                  OR postcode LIKE %s
                  OR customer_phone LIKE %s
                  OR customer_email LIKE %s",
                '%' . $this->wpdb->esc_like($search) . '%',
                '%' . $this->wpdb->esc_like($search) . '%',
                '%' . $this->wpdb->esc_like($search) . '%',
                '%' . $this->wpdb->esc_like($search) . '%',
                '%' . $this->wpdb->esc_like($search) . '%'
            );
        }

        $sql .= " ORDER BY customer_name ASC";

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get customer by ID
     *
     * @param int $customer_id The customer ID
     * @return object|null Customer record
     */
    public function get_customer($customer_id) {
        return $this->find_by_id($customer_id);
    }

    /**
     * Create a new customer
     *
     * @param array $data Customer data
     * @return int|false Customer ID or false on failure
     */
    public function create_customer($data) {
        $data['created_at'] = current_time('mysql');
        return $this->create($data);
    }

    /**
     * Update an existing customer
     *
     * @param int $customer_id The customer ID
     * @param array $data Customer data
     * @return bool True on success, false on failure
     */
    public function update_customer($customer_id, $data) {
        return $this->update($customer_id, $data);
    }

    /**
     * Delete a customer
     *
     * @param int $customer_id The customer ID
     * @return bool True on success, false on failure
     */
    public function delete_customer($customer_id) {
        return $this->delete($customer_id);
    }

    /**
     * Get count of jobs for a customer
     *
     * @param int $customer_id The customer ID
     * @return int Job count
     */
    public function get_jobs_count($customer_id) {
        $table_diary = $this->wpdb->prefix . 'staff_diary_entries';

        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_diary} WHERE customer_id = %d",
            $customer_id
        );

        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Get customer with job count
     *
     * @param int $customer_id The customer ID
     * @return object|null Customer record with job_count property
     */
    public function get_with_job_count($customer_id) {
        $customer = $this->get_customer($customer_id);

        if ($customer) {
            $customer->job_count = $this->get_jobs_count($customer_id);
        }

        return $customer;
    }
}
