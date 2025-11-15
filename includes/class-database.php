<?php
/**
 * Database operations for diary entries
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Database {

    private $table_diary;
    private $table_images;

    public function __construct() {
        global $wpdb;
        $this->table_diary = $wpdb->prefix . 'staff_diary_entries';
        $this->table_images = $wpdb->prefix . 'staff_diary_images';
    }

    /**
     * Get diary entries for a specific user
     * Excludes quotes (status = 'quotation') - quotes have their own page
     */
    public function get_user_entries($user_id, $start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table_diary} WHERE user_id = %d AND status != 'quotation'";
        $params = array($user_id);

        if ($start_date && $end_date) {
            $sql .= " AND job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY job_date DESC, created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get all diary entries (for overview)
     */
    public function get_all_entries($start_date = null, $end_date = null, $limit = 100) {
        global $wpdb;

        $sql = "SELECT d.*, u.display_name as staff_name
                FROM {$this->table_diary} d
                LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID";

        $params = array();

        if ($start_date && $end_date) {
            $sql .= " WHERE job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY job_date DESC, created_at DESC LIMIT %d";
        $params[] = $limit;

        if (empty($params)) {
            $params[] = $limit;
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        }

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get a single diary entry
     */
    public function get_entry($entry_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_diary} WHERE id = %d",
            $entry_id
        ));
    }

    /**
     * Create a new diary entry
     */
    public function create_entry($data) {
        global $wpdb;

        $wpdb->insert($this->table_diary, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a diary entry
     */
    public function update_entry($entry_id, $data) {
        global $wpdb;

        return $wpdb->update(
            $this->table_diary,
            $data,
            array('id' => $entry_id)
        );
    }

    /**
     * Delete a diary entry
     */
    public function delete_entry($entry_id) {
        global $wpdb;

        // Delete associated images first
        $wpdb->delete($this->table_images, array('diary_entry_id' => $entry_id));

        // Delete the entry
        return $wpdb->delete($this->table_diary, array('id' => $entry_id));
    }

    /**
     * Add image to diary entry
     */
    public function add_image($diary_entry_id, $image_url, $attachment_id = null, $caption = '') {
        global $wpdb;

        $wpdb->insert($this->table_images, array(
            'diary_entry_id' => $diary_entry_id,
            'image_url' => $image_url,
            'attachment_id' => $attachment_id,
            'image_caption' => $caption
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get images for a diary entry
     */
    public function get_entry_images($diary_entry_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_images} WHERE diary_entry_id = %d ORDER BY uploaded_at ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete an image
     */
    public function delete_image($image_id) {
        global $wpdb;

        return $wpdb->delete($this->table_images, array('id' => $image_id));
    }

    /**
     * Add a payment record
     */
    public function add_payment($diary_entry_id, $amount, $payment_method, $payment_type, $notes, $recorded_by) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        $wpdb->insert($table_payments, array(
            'diary_entry_id' => $diary_entry_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'notes' => $notes,
            'recorded_by' => $recorded_by
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get payments for a diary entry
     */
    public function get_entry_payments($diary_entry_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE diary_entry_id = %d ORDER BY recorded_at ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete a payment record
     */
    public function delete_payment($payment_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->delete($table_payments, array('id' => $payment_id));
    }

    /**
     * Get total payments for a diary entry
     */
    public function get_entry_total_payments($diary_entry_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_payments WHERE diary_entry_id = %d",
            $diary_entry_id
        ));
    }

    // ==================== CUSTOMER METHODS ====================

    /**
     * Get all customers
     */
    public function get_all_customers($search = '') {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        if (!empty($search)) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_customers
                WHERE customer_name LIKE %s
                OR customer_phone LIKE %s
                OR customer_email LIKE %s
                ORDER BY customer_name ASC",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            ));
        }

        return $wpdb->get_results("SELECT * FROM $table_customers ORDER BY customer_name ASC");
    }

    /**
     * Get a single customer
     */
    public function get_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_customers WHERE id = %d",
            $customer_id
        ));
    }

    /**
     * Create a new customer
     */
    public function create_customer($data) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        $wpdb->insert($table_customers, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a customer
     */
    public function update_customer($customer_id, $data) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->update($table_customers, $data, array('id' => $customer_id));
    }

    /**
     * Delete a customer
     */
    public function delete_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->delete($table_customers, array('id' => $customer_id));
    }

    /**
     * Get customer jobs count
     */
    public function get_customer_jobs_count($customer_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_diary} WHERE customer_id = %d",
            $customer_id
        ));
    }

    // ==================== ACCESSORY METHODS ====================

    /**
     * Get all accessories
     */
    public function get_all_accessories($active_only = false) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        $sql = "SELECT * FROM $table_accessories";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC, accessory_name ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * Get a single accessory
     */
    public function get_accessory($accessory_id) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_accessories WHERE id = %d",
            $accessory_id
        ));
    }

    /**
     * Create a new accessory
     */
    public function create_accessory($data) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        $wpdb->insert($table_accessories, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update an accessory
     */
    public function update_accessory($accessory_id, $data) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->update($table_accessories, $data, array('id' => $accessory_id));
    }

    /**
     * Delete an accessory
     */
    public function delete_accessory($accessory_id) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->delete($table_accessories, array('id' => $accessory_id));
    }

    // ==================== JOB ACCESSORY METHODS ====================

    /**
     * Add accessories to a job
     */
    public function add_job_accessory($diary_entry_id, $accessory_id, $accessory_name, $quantity, $price_per_unit) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        $total_price = $quantity * $price_per_unit;

        $wpdb->insert($table_job_accessories, array(
            'diary_entry_id' => $diary_entry_id,
            'accessory_id' => $accessory_id,
            'accessory_name' => $accessory_name,
            'quantity' => $quantity,
            'price_per_unit' => $price_per_unit,
            'total_price' => $total_price
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get all accessories for a job
     */
    public function get_job_accessories($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_job_accessories WHERE diary_entry_id = %d ORDER BY id ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete job accessory
     */
    public function delete_job_accessory($job_accessory_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->delete($table_job_accessories, array('id' => $job_accessory_id));
    }

    /**
     * Delete all accessories for a job
     */
    public function delete_all_job_accessories($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->delete($table_job_accessories, array('diary_entry_id' => $diary_entry_id));
    }

    /**
     * Get total accessories cost for a job
     */
    public function get_job_accessories_total($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_price), 0) FROM $table_job_accessories WHERE diary_entry_id = %d",
            $diary_entry_id
        ));
    }

    // ==================== ORDER NUMBER METHODS ====================

    /**
     * Generate next order number
     */
    public function generate_order_number() {
        $prefix = get_option('wp_staff_diary_order_prefix', '');
        $current = get_option('wp_staff_diary_order_current', '01100');

        // Increment the order number
        $next_number = str_pad((int)$current + 1, strlen($current), '0', STR_PAD_LEFT);

        // Update the current order number
        update_option('wp_staff_diary_order_current', $next_number);

        return $prefix . $next_number;
    }

    /**
     * Check if order number exists
     */
    public function order_number_exists($order_number) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_diary} WHERE order_number = %s",
            $order_number
        ));

        return $count > 0;
    }

    // ==================== CALCULATION METHODS ====================

    /**
     * Calculate job subtotal (product + accessories)
     */
    public function calculate_job_subtotal($diary_entry_id) {
        global $wpdb;

        $entry = $this->get_entry($diary_entry_id);

        // Calculate product total
        $product_total = 0;
        if ($entry && $entry->sq_mtr_qty && $entry->price_per_sq_mtr) {
            $product_total = $entry->sq_mtr_qty * $entry->price_per_sq_mtr;
        }

        // Add fitting cost
        $fitting_cost = 0;
        if ($entry && isset($entry->fitting_cost)) {
            $fitting_cost = floatval($entry->fitting_cost);
        }

        // Get accessories total
        $accessories_total = $this->get_job_accessories_total($diary_entry_id);

        return $product_total + $fitting_cost + $accessories_total;
    }

    /**
     * Calculate job balance (subtotal + VAT - payments)
     */
    public function calculate_job_balance($diary_entry_id) {
        $subtotal = $this->calculate_job_subtotal($diary_entry_id);

        // Add VAT if enabled
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        $total = $subtotal;
        if ($vat_enabled == '1') {
            $vat_amount = $subtotal * ($vat_rate / 100);
            $total = $subtotal + $vat_amount;
        }

        // Subtract payments
        $payments = $this->get_entry_total_payments($diary_entry_id);
        $balance = $total - $payments;

        return $balance;
    }

    /**
     * Mark entry as cancelled
     */
    public function cancel_entry($entry_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_diary,
            array(
                'is_cancelled' => 1,
                'status' => 'cancelled'
            ),
            array('id' => $entry_id)
        );
    }
}
