<?php
/**
 * Products Repository
 *
 * Handles all database operations for job/quote products.
 *
 * @since      3.6.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Products_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_products');
    }

    /**
     * Add a product to a quote/job
     *
     * @param int $diary_entry_id The diary entry ID
     * @param array $data Product data (description, size, sq_mtr_qty, price_per_sq_mtr, product_total)
     * @return int|false Product ID or false on failure
     */
    public function add_product($diary_entry_id, $data) {
        // Get the next display_order value
        $max_order = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT MAX(display_order) FROM {$this->table} WHERE diary_entry_id = %d",
            $diary_entry_id
        ));

        $next_order = $max_order !== null ? ($max_order + 1) : 0;

        $insert_data = array(
            'diary_entry_id' => $diary_entry_id,
            'product_description' => isset($data['product_description']) ? $data['product_description'] : '',
            'size' => isset($data['size']) ? $data['size'] : null,
            'sq_mtr_qty' => isset($data['sq_mtr_qty']) ? $data['sq_mtr_qty'] : null,
            'price_per_sq_mtr' => isset($data['price_per_sq_mtr']) ? $data['price_per_sq_mtr'] : null,
            'product_total' => isset($data['product_total']) ? $data['product_total'] : null,
            'display_order' => $next_order,
            'created_at' => current_time('mysql')
        );

        return $this->create($insert_data);
    }

    /**
     * Get all products for a diary entry
     *
     * @param int $diary_entry_id The diary entry ID
     * @return array Array of product records ordered by display_order
     */
    public function get_entry_products($diary_entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE diary_entry_id = %d ORDER BY display_order ASC, id ASC",
            $diary_entry_id
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Update a product
     *
     * @param int $product_id The product ID
     * @param array $data Product data to update
     * @return int|false Number of rows affected or false on failure
     */
    public function update_product($product_id, $data) {
        $update_data = array();

        if (isset($data['product_description'])) {
            $update_data['product_description'] = $data['product_description'];
        }
        if (isset($data['size'])) {
            $update_data['size'] = $data['size'];
        }
        if (isset($data['sq_mtr_qty'])) {
            $update_data['sq_mtr_qty'] = $data['sq_mtr_qty'];
        }
        if (isset($data['price_per_sq_mtr'])) {
            $update_data['price_per_sq_mtr'] = $data['price_per_sq_mtr'];
        }
        if (isset($data['product_total'])) {
            $update_data['product_total'] = $data['product_total'];
        }
        if (isset($data['display_order'])) {
            $update_data['display_order'] = $data['display_order'];
        }

        if (empty($update_data)) {
            return false;
        }

        return $this->update($product_id, $update_data);
    }

    /**
     * Delete a product
     *
     * @param int $product_id The product ID
     * @return int|false Number of rows affected or false on failure
     */
    public function delete_product($product_id) {
        return $this->delete($product_id);
    }

    /**
     * Calculate total for all products in an entry
     *
     * @param int $diary_entry_id The diary entry ID
     * @return float Total of all products
     */
    public function calculate_products_total($diary_entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT SUM(product_total) FROM {$this->table} WHERE diary_entry_id = %d",
            $diary_entry_id
        );

        $total = $this->wpdb->get_var($sql);
        return $total !== null ? (float) $total : 0.0;
    }

    /**
     * Update display order for products
     *
     * @param array $order_map Array of product_id => display_order
     * @return bool True on success
     */
    public function update_display_orders($order_map) {
        foreach ($order_map as $product_id => $display_order) {
            $this->wpdb->update(
                $this->table,
                array('display_order' => (int) $display_order),
                array('id' => (int) $product_id),
                array('%d'),
                array('%d')
            );
        }
        return true;
    }

    /**
     * Delete all products for a diary entry
     *
     * @param int $diary_entry_id The diary entry ID
     * @return int|false Number of rows deleted or false on failure
     */
    public function delete_entry_products($diary_entry_id) {
        return $this->wpdb->delete(
            $this->table,
            array('diary_entry_id' => $diary_entry_id),
            array('%d')
        );
    }
}
