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
     */
    public function get_user_entries($user_id, $start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table_diary} WHERE user_id = %d";
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
}
