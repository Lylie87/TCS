<?php
/**
 * Jobs Controller
 *
 * Handles HTTP/AJAX requests for job/diary entry operations.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Jobs_Controller extends WP_Staff_Diary_Base_Controller {

    /**
     * The jobs repository
     *
     * @var WP_Staff_Diary_Jobs_Repository
     */
    private $repository;

    /**
     * The database instance for accessories
     *
     * @var WP_Staff_Diary_Database
     */
    private $db;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Jobs_Repository $repository The jobs repository
     */
    public function __construct($repository) {
        $this->repository = $repository;
        $this->db = new WP_Staff_Diary_Database(); // For accessories and order number generation
    }

    /**
     * Handle save job entry request (create or update)
     */
    public function save() {
        // Start output buffering to catch any stray output
        ob_start();

        if (!$this->verify_request()) {
            ob_end_clean();
            return;
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $user_id = get_current_user_id();
        $status = sanitize_text_field($_POST['status']);

        // If status is 'cancelled' and this is an existing entry, delete it
        if ($status === 'cancelled' && $entry_id > 0) {
            $result = $this->repository->delete_entry($entry_id);
            ob_end_clean();
            if ($result) {
                $this->send_success(array('message' => 'Job cancelled and removed from diary'));
            } else {
                $this->send_error('Failed to cancel job');
            }
            return;
        }

        // Prevent creating new entries with cancelled status
        if ($status === 'cancelled' && $entry_id === 0) {
            ob_end_clean();
            $this->send_error('Cannot create a new job with cancelled status');
            return;
        }

        // Handle WooCommerce customer - convert to plugin customer
        $customer_id = null;
        if (!empty($_POST['customer_id'])) {
            $posted_customer_id = $_POST['customer_id'];

            // Check if this is a WooCommerce customer
            if (is_string($posted_customer_id) && strpos($posted_customer_id, 'wc_') === 0) {
                $wc_user_id = intval(substr($posted_customer_id, 3));
                $customer_id = $this->convert_wc_customer_to_plugin($wc_user_id);
            } else {
                $customer_id = intval($posted_customer_id);
            }
        }

        // Prepare data for main entry
        $data = array(
            'user_id' => $user_id,
            'customer_id' => $customer_id,
            'fitter_id' => isset($_POST['fitter_id']) && $_POST['fitter_id'] !== '' ? intval($_POST['fitter_id']) : null,
            'job_date' => !empty($_POST['job_date']) ? sanitize_text_field($_POST['job_date']) : null,
            'job_time' => !empty($_POST['job_time']) ? sanitize_text_field($_POST['job_time']) : null,
            'fitting_date' => !empty($_POST['fitting_date']) ? sanitize_text_field($_POST['fitting_date']) : null,
            'fitting_date_unknown' => !empty($_POST['fitting_date_unknown']) ? 1 : 0,
            'fitting_time_period' => !empty($_POST['fitting_time_period']) ? sanitize_text_field($_POST['fitting_time_period']) : null,
            'billing_address_line_1' => !empty($_POST['billing_address_line_1']) ? sanitize_text_field($_POST['billing_address_line_1']) : null,
            'billing_address_line_2' => !empty($_POST['billing_address_line_2']) ? sanitize_text_field($_POST['billing_address_line_2']) : null,
            'billing_address_line_3' => !empty($_POST['billing_address_line_3']) ? sanitize_text_field($_POST['billing_address_line_3']) : null,
            'billing_postcode' => !empty($_POST['billing_postcode']) ? sanitize_text_field($_POST['billing_postcode']) : null,
            'fitting_address_different' => !empty($_POST['fitting_address_different']) ? 1 : 0,
            'fitting_address_line_1' => !empty($_POST['fitting_address_line_1']) ? sanitize_text_field($_POST['fitting_address_line_1']) : null,
            'fitting_address_line_2' => !empty($_POST['fitting_address_line_2']) ? sanitize_text_field($_POST['fitting_address_line_2']) : null,
            'fitting_address_line_3' => !empty($_POST['fitting_address_line_3']) ? sanitize_text_field($_POST['fitting_address_line_3']) : null,
            'fitting_postcode' => !empty($_POST['fitting_postcode']) ? sanitize_text_field($_POST['fitting_postcode']) : null,
            'area' => !empty($_POST['area']) ? sanitize_text_field($_POST['area']) : null,
            'size' => !empty($_POST['size']) ? sanitize_text_field($_POST['size']) : null,
            'product_description' => !empty($_POST['product_description']) ? sanitize_textarea_field($_POST['product_description']) : null,
            'product_source' => !empty($_POST['product_source']) ? sanitize_text_field($_POST['product_source']) : 'manual',
            'woocommerce_product_id' => !empty($_POST['woocommerce_product_id']) ? intval($_POST['woocommerce_product_id']) : null,
            'sq_mtr_qty' => !empty($_POST['sq_mtr_qty']) ? floatval($_POST['sq_mtr_qty']) : null,
            'price_per_sq_mtr' => !empty($_POST['price_per_sq_mtr']) ? floatval($_POST['price_per_sq_mtr']) : null,
            'fitting_cost' => !empty($_POST['fitting_cost']) ? floatval($_POST['fitting_cost']) : 0,
            'notes' => !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : null,
            'status' => $status
        );

        if ($entry_id > 0) {
            // Update existing entry
            $result = $this->repository->update_entry($entry_id, $data);

            if ($result !== false) {
                // Update job accessories if provided
                if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
                    $this->db->delete_all_job_accessories($entry_id);

                    foreach ($_POST['accessories'] as $accessory) {
                        if (!empty($accessory['accessory_id'])) {
                            $this->db->add_job_accessory(
                                $entry_id,
                                intval($accessory['accessory_id']),
                                sanitize_text_field($accessory['accessory_name']),
                                floatval($accessory['quantity']),
                                floatval($accessory['price_per_unit'])
                            );
                        }
                    }
                }

                ob_end_clean();
                $this->send_success(array(
                    'entry_id' => $entry_id,
                    'message' => 'Entry updated successfully'
                ));
            } else {
                ob_end_clean();
                $this->send_error('Failed to update entry');
            }
        } else {
            // Create new entry - use provided order number or generate new one
            if (!empty($_POST['order_number'])) {
                $order_number = sanitize_text_field($_POST['order_number']);
            } else {
                $order_number = $this->db->generate_order_number();
            }
            $data['order_number'] = $order_number;

            $new_id = $this->repository->create_entry($data);

            if ($new_id) {
                // Add job accessories if provided
                if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
                    foreach ($_POST['accessories'] as $accessory) {
                        if (!empty($accessory['accessory_id'])) {
                            $this->db->add_job_accessory(
                                $new_id,
                                intval($accessory['accessory_id']),
                                sanitize_text_field($accessory['accessory_name']),
                                floatval($accessory['quantity']),
                                floatval($accessory['price_per_unit'])
                            );
                        }
                    }
                }

                ob_end_clean();
                $this->send_success(array(
                    'entry_id' => $new_id,
                    'order_number' => $order_number,
                    'message' => 'Entry created successfully'
                ));
            } else {
                ob_end_clean();
                $this->send_error('Failed to create entry');
            }
        }
    }

    /**
     * Handle delete job entry request
     */
    public function delete() {
        ob_start();

        if (!$this->verify_request()) {
            ob_end_clean();
            return;
        }

        $entry_id = intval($_POST['entry_id']);

        if (empty($entry_id)) {
            ob_end_clean();
            $this->send_error('Missing required field: entry_id');
            return;
        }

        $result = $this->repository->delete_entry($entry_id);

        ob_end_clean();

        if ($result) {
            $this->send_success(array('message' => 'Entry deleted successfully'));
        } else {
            $this->send_error('Failed to delete entry');
        }
    }

    /**
     * Handle cancel job entry request
     */
    public function cancel() {
        ob_start();

        if (!$this->verify_request()) {
            ob_end_clean();
            return;
        }

        $entry_id = intval($_POST['entry_id']);

        if (empty($entry_id)) {
            ob_end_clean();
            $this->send_error('Missing required field: entry_id');
            return;
        }

        $result = $this->repository->cancel_entry($entry_id);

        ob_end_clean();

        if ($result) {
            $this->send_success(array('message' => 'Entry cancelled successfully'));
        } else {
            $this->send_error('Failed to cancel entry');
        }
    }

    /**
     * Handle get job entry request
     */
    public function get() {
        ob_start();

        if (!$this->verify_request()) {
            ob_end_clean();
            return;
        }

        $entry_id = intval($_POST['entry_id']);

        if (empty($entry_id)) {
            ob_end_clean();
            $this->send_error('Missing required field: entry_id');
            return;
        }

        $entry = $this->repository->get_entry_with_relations($entry_id);

        ob_end_clean();

        if ($entry) {
            $this->send_success(array('entry' => $entry));
        } else {
            $this->send_error('Entry not found');
        }
    }

    /**
     * Convert a WooCommerce customer to a plugin customer
     * Creates a new customer record in the plugin database from WooCommerce user data
     *
     * @param int $wc_user_id WooCommerce user ID
     * @return int|null The new customer ID or null if failed
     */
    private function convert_wc_customer_to_plugin($wc_user_id) {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $user = get_user_by('ID', $wc_user_id);

        if (!$user) {
            return null;
        }

        // Prepare customer data from WooCommerce user
        $customer_data = array(
            'customer_name' => $user->display_name ? $user->display_name : $user->user_login,
            'customer_email' => $user->user_email,
            'customer_phone' => get_user_meta($wc_user_id, 'billing_phone', true),
            'address_line_1' => get_user_meta($wc_user_id, 'billing_address_1', true),
            'address_line_2' => get_user_meta($wc_user_id, 'billing_address_2', true),
            'address_line_3' => get_user_meta($wc_user_id, 'billing_city', true),
            'postcode' => get_user_meta($wc_user_id, 'billing_postcode', true),
            'notes' => 'Imported from WooCommerce (User ID: ' . $wc_user_id . ')'
        );

        // Check if this WooCommerce customer already exists in our database
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_customers';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE customer_email = %s AND notes LIKE %s LIMIT 1",
            $customer_data['customer_email'],
            '%User ID: ' . $wc_user_id . '%'
        ));

        if ($existing) {
            return intval($existing);
        }

        // Create new customer record
        $customers_repo = new WP_Staff_Diary_Customers_Repository();
        return $customers_repo->create_customer($customer_data);
    }
}
