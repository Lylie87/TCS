<?php
/**
 * Jobs Repository
 *
 * Handles all database operations for job/diary entries.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Jobs_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_entries');
    }

    /**
     * Get entries for a specific user
     *
     * @param int $user_id User ID
     * @param string $start_date Optional start date
     * @param string $end_date Optional end date
     * @return array Array of job entries
     */
    public function get_user_entries($user_id, $start_date = null, $end_date = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = %d";
        $params = array($user_id);

        if ($start_date && $end_date) {
            $sql .= " AND job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY job_date DESC, created_at DESC";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }

    /**
     * Get all entries (for overview)
     *
     * @param string $start_date Optional start date
     * @param string $end_date Optional end date
     * @param int $limit Results limit
     * @return array Array of job entries with staff names
     */
    public function get_all_entries($start_date = null, $end_date = null, $limit = 100) {
        $sql = "SELECT d.*, u.display_name as staff_name
                FROM {$this->table} d
                LEFT JOIN {$this->wpdb->users} u ON d.user_id = u.ID";

        $params = array();

        if ($start_date && $end_date) {
            $sql .= " WHERE d.job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY d.job_date DESC, d.created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }

    /**
     * Get a single entry by ID
     *
     * @param int $entry_id The entry ID
     * @return object|null Entry record
     */
    public function get_entry($entry_id) {
        return $this->find_by_id($entry_id);
    }

    /**
     * Create a new entry
     *
     * @param array $data Entry data
     * @return int|false Entry ID or false on failure
     */
    public function create_entry($data) {
        $data['created_at'] = current_time('mysql');
        return $this->create($data);
    }

    /**
     * Update an existing entry
     *
     * @param int $entry_id The entry ID
     * @param array $data Entry data
     * @return bool True on success, false on failure
     */
    public function update_entry($entry_id, $data) {
        return $this->update($entry_id, $data);
    }

    /**
     * Delete an entry
     *
     * @param int $entry_id The entry ID
     * @return bool True on success, false on failure
     */
    public function delete_entry($entry_id) {
        // Delete associated records first
        $this->delete_associated_data($entry_id);

        return $this->delete($entry_id);
    }

    /**
     * Cancel an entry (soft delete)
     *
     * @param int $entry_id The entry ID
     * @return bool True on success, false on failure
     */
    public function cancel_entry($entry_id) {
        return $this->update($entry_id, array('status' => 'cancelled'));
    }

    /**
     * Delete all associated data for an entry
     *
     * @param int $entry_id The entry ID
     */
    private function delete_associated_data($entry_id) {
        $table_images = $this->wpdb->prefix . 'staff_diary_images';
        $table_payments = $this->wpdb->prefix . 'staff_diary_payments';
        $table_job_accessories = $this->wpdb->prefix . 'staff_diary_job_accessories';

        // Delete images
        $this->wpdb->delete($table_images, array('diary_entry_id' => $entry_id), array('%d'));

        // Delete payments
        $this->wpdb->delete($table_payments, array('diary_entry_id' => $entry_id), array('%d'));

        // Delete job accessories
        $this->wpdb->delete($table_job_accessories, array('diary_entry_id' => $entry_id), array('%d'));
    }

    /**
     * Get entry with all related data
     *
     * @param int $entry_id The entry ID
     * @return object|null Entry with images, payments, accessories, customer
     */
    public function get_entry_with_relations($entry_id) {
        $entry = $this->get_entry($entry_id);

        if (!$entry) {
            return null;
        }

        // Get images
        $table_images = $this->wpdb->prefix . 'staff_diary_images';
        $entry->images = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$table_images} WHERE diary_entry_id = %d ORDER BY id ASC",
            $entry_id
        ));

        // Get payments
        $table_payments = $this->wpdb->prefix . 'staff_diary_payments';
        $entry->payments = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$table_payments} WHERE diary_entry_id = %d ORDER BY recorded_at DESC",
            $entry_id
        ));

        // Format payment data
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
        $time_format = get_option('wp_staff_diary_time_format', 'H:i');

        foreach ($entry->payments as $payment) {
            $user = get_userdata($payment->recorded_by);
            $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';
            $payment->amount_formatted = '£' . number_format($payment->amount, 2);
            $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
        }

        // Get job accessories
        $table_job_accessories = $this->wpdb->prefix . 'staff_diary_job_accessories';
        $entry->accessories = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$table_job_accessories} WHERE diary_entry_id = %d ORDER BY id ASC",
            $entry_id
        ));

        // Get customer if linked
        if ($entry->customer_id) {
            $table_customers = $this->wpdb->prefix . 'staff_diary_customers';
            $entry->customer = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$table_customers} WHERE id = %d",
                $entry->customer_id
            ));
        }

        // Calculate totals
        $entry->total_payments = $this->get_total_payments($entry_id);
        $entry->accessories_total = $this->get_accessories_total($entry_id);
        $entry->subtotal = $this->calculate_subtotal($entry);
        $entry->balance = $entry->subtotal - $entry->total_payments;

        return $entry;
    }

    /**
     * Get total payments for an entry
     *
     * @param int $entry_id The entry ID
     * @return float Total payment amount
     */
    private function get_total_payments($entry_id) {
        $table_payments = $this->wpdb->prefix . 'staff_diary_payments';
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$table_payments} WHERE diary_entry_id = %d",
            $entry_id
        ));
        return $total ? floatval($total) : 0.00;
    }

    /**
     * Get total accessories cost for an entry
     *
     * @param int $entry_id The entry ID
     * @return float Total accessories amount
     */
    private function get_accessories_total($entry_id) {
        $table_job_accessories = $this->wpdb->prefix . 'staff_diary_job_accessories';
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(quantity * price_per_unit) FROM {$table_job_accessories} WHERE diary_entry_id = %d",
            $entry_id
        ));
        return $total ? floatval($total) : 0.00;
    }

    /**
     * Calculate job subtotal including VAT
     *
     * @param object $entry The entry object
     * @return float Subtotal with VAT
     */
    private function calculate_subtotal($entry) {
        $product_total = floatval($entry->quantity) * floatval($entry->price_per_sqmtr);
        $fitting_cost = floatval($entry->fitting_cost);
        $accessories_total = $entry->accessories_total;

        $subtotal_before_vat = $product_total + $fitting_cost + $accessories_total;

        $vat_enabled = get_option('wp_staff_diary_vat_enabled', 1);
        $vat_rate = get_option('wp_staff_diary_vat_rate', 20);

        if ($vat_enabled) {
            $vat_amount = ($subtotal_before_vat * $vat_rate) / 100;
            return $subtotal_before_vat + $vat_amount;
        }

        return $subtotal_before_vat;
    }
}
