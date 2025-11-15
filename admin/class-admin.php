<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Admin {

    private $plugin_name;
    private $version;
    private $db;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new WP_Staff_Diary_Database();
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_STAFF_DIARY_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            $this->plugin_name . '-v2',
            WP_STAFF_DIARY_URL . 'assets/css/admin-v2-additions.css',
            array($this->plugin_name),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WP_STAFF_DIARY_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Enqueue quotes.js for quotes page
        wp_enqueue_script(
            $this->plugin_name . '-quotes',
            WP_STAFF_DIARY_URL . 'assets/js/quotes.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Get statuses and payment methods from settings
        $statuses = get_option('wp_staff_diary_statuses', array(
            'quotation' => 'Quotation',
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ));

        $payment_methods = get_option('wp_staff_diary_payment_methods', array(
            'cash' => 'Cash',
            'bank-transfer' => 'Bank Transfer',
            'card-payment' => 'Card Payment'
        ));

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'wpStaffDiary', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_staff_diary_nonce'),
            'statuses' => $statuses,
            'paymentMethods' => $payment_methods
        ));

        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wp_staff_diary_dashboard_widget',
            'My Jobs This Week',
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $current_user = wp_get_current_user();
        $db = new WP_Staff_Diary_Database();

        // Get current week
        $today = new DateTime();
        $week_start = clone $today;
        $week_start->modify('monday this week');

        // Get all entries for the current week
        $start_date = $week_start->format('Y-m-d');
        $end_date = clone $week_start;
        $end_date->modify('+6 days');
        $end_date_str = $end_date->format('Y-m-d');

        $entries = $db->get_user_entries($current_user->ID, $start_date, $end_date_str);

        // Organize entries by date
        $entries_by_date = array();
        foreach ($entries as $entry) {
            $date_key = $entry->job_date;
            if (!isset($entries_by_date[$date_key])) {
                $entries_by_date[$date_key] = array();
            }
            $entries_by_date[$date_key][] = $entry;
        }

        // Sort entries by time within each day
        foreach ($entries_by_date as $date => $day_entries) {
            usort($day_entries, function($a, $b) {
                if ($a->job_time === null) return 1;
                if ($b->job_time === null) return -1;
                return strcmp($a->job_time, $b->job_time);
            });
            $entries_by_date[$date] = $day_entries;
        }

        // Include the dashboard widget view
        include WP_STAFF_DIARY_PATH . 'admin/views/dashboard-widget.php';
    }

    /**
     * Add admin menu pages
     */
    public function add_plugin_admin_menu() {
        // Main menu - Staff Diary
        add_menu_page(
            'Staff Diary',
            'Staff Diary',
            'edit_posts',
            'wp-staff-diary',
            array($this, 'display_my_diary_page'),
            'dashicons-calendar-alt',
            2
        );

        // Submenu - Dashboard (previously "My Jobs")
        add_submenu_page(
            'wp-staff-diary',
            'Dashboard',
            'Dashboard',
            'edit_posts',
            'wp-staff-diary',
            array($this, 'display_my_diary_page')
        );

        // Submenu - Quotes
        add_submenu_page(
            'wp-staff-diary',
            'Quotes',
            'Quotes',
            'edit_posts',
            'wp-staff-diary-quotes',
            array($this, 'display_quotes_page')
        );

        // Submenu - All Staff Jobs (only for managers/admins)
        add_submenu_page(
            'wp-staff-diary',
            'All Staff Jobs',
            'All Staff Jobs',
            'edit_users',
            'wp-staff-diary-overview',
            array($this, 'display_overview_page')
        );

        // Submenu - Customers (only for staff and above)
        add_submenu_page(
            'wp-staff-diary',
            'Customers',
            'Customers',
            'edit_posts',
            'wp-staff-diary-customers',
            array($this, 'display_customers_page')
        );

        // Submenu - Settings (only for admins)
        add_submenu_page(
            'wp-staff-diary',
            'Settings',
            'Settings',
            'manage_options',
            'wp-staff-diary-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display My Diary page
     */
    public function display_my_diary_page() {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'calendar';

        if ($view === 'list') {
            require_once WP_STAFF_DIARY_PATH . 'admin/views/my-diary.php';
        } else {
            require_once WP_STAFF_DIARY_PATH . 'admin/views/calendar-view.php';
        }
    }

    /**
     * Display Staff Overview page
     */
    public function display_overview_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/staff-overview.php';
    }

    /**
     * Display Customers page
     */
    public function display_customers_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/customers.php';
    }

    /**
     * Display Settings page
     */
    public function display_settings_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/settings.php';
    }

    /**
     * Display Quotes page
     */
    public function display_quotes_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/quotes.php';
    }

    /**
     * AJAX: Save diary entry
     */
    public function save_diary_entry() {
        // Start output buffering to catch any stray output
        ob_start();

        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $user_id = get_current_user_id();
        $status = sanitize_text_field($_POST['status']);

        // If status is 'cancelled' and this is an existing entry, delete it
        if ($status === 'cancelled' && $entry_id > 0) {
            $result = $this->db->delete_entry($entry_id);
            ob_end_clean();
            if ($result) {
                wp_send_json_success(array('message' => 'Job cancelled and removed from diary'));
            } else {
                wp_send_json_error(array('message' => 'Failed to cancel job'));
            }
            return;
        }

        // Prevent creating new entries with cancelled status
        if ($status === 'cancelled' && $entry_id === 0) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Cannot create a new job with cancelled status'));
            return;
        }

        // Prepare data for main entry
        $data = array(
            'user_id' => $user_id,
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'fitter_id' => isset($_POST['fitter_id']) && $_POST['fitter_id'] !== '' ? intval($_POST['fitter_id']) : null,
            'job_date' => !empty($_POST['job_date']) ? sanitize_text_field($_POST['job_date']) : null,
            'job_time' => !empty($_POST['job_time']) ? sanitize_text_field($_POST['job_time']) : null,
            'fitting_date' => !empty($_POST['fitting_date']) ? sanitize_text_field($_POST['fitting_date']) : null,
            'fitting_time_period' => !empty($_POST['fitting_time_period']) ? sanitize_text_field($_POST['fitting_time_period']) : null,
            'fitting_date_unknown' => !empty($_POST['fitting_date_unknown']) ? 1 : 0,
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
            'sq_mtr_qty' => !empty($_POST['sq_mtr_qty']) ? floatval($_POST['sq_mtr_qty']) : null,
            'price_per_sq_mtr' => !empty($_POST['price_per_sq_mtr']) ? floatval($_POST['price_per_sq_mtr']) : null,
            'fitting_cost' => !empty($_POST['fitting_cost']) ? floatval($_POST['fitting_cost']) : 0,
            'notes' => !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : null,
            'status' => $status
        );

        if ($entry_id > 0) {
            // Update existing entry (don't change order number)
            $result = $this->db->update_entry($entry_id, $data);

            if ($result !== false) {
                // Update job accessories if provided
                if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
                    // Delete existing accessories for this job
                    $this->db->delete_all_job_accessories($entry_id);

                    // Add new accessories
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
                wp_send_json_success(array(
                    'entry_id' => $entry_id,
                    'message' => 'Entry updated successfully'
                ));
            } else {
                global $wpdb;
                error_log('WP Staff Diary Update Error: ' . $wpdb->last_error);
                error_log('WP Staff Diary Update Query: ' . $wpdb->last_query);
                ob_end_clean();
                wp_send_json_error(array('message' => 'Failed to update entry: ' . $wpdb->last_error));
            }
        } else {
            // Create new entry - generate order number
            $order_number = $this->db->generate_order_number();
            $data['order_number'] = $order_number;

            $new_id = $this->db->create_entry($data);

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
                wp_send_json_success(array(
                    'entry_id' => $new_id,
                    'order_number' => $order_number,
                    'message' => 'Entry created successfully'
                ));
            } else {
                ob_end_clean();
                wp_send_json_error(array('message' => 'Failed to create entry'));
            }
        }
    }

    /**
     * AJAX: Delete diary entry
     */
    public function delete_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();

        // Verify ownership or admin
        $entry = $this->db->get_entry($entry_id);
        if (!$entry || ($entry->user_id != $user_id && !current_user_can('delete_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $result = $this->db->delete_entry($entry_id);
        if ($result) {
            wp_send_json_success(array('message' => 'Entry deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete entry'));
        }
    }

    /**
     * AJAX: Cancel diary entry (soft delete)
     */
    public function cancel_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();

        // Verify ownership or admin
        $entry = $this->db->get_entry($entry_id);
        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $result = $this->db->cancel_entry($entry_id);
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Entry cancelled successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to cancel entry'));
        }
    }

    /**
     * AJAX: Upload job image
     */
    public function upload_job_image() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['image'];
        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // Create attachment
            $attachment = array(
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name($movefile['file']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Save to database
            $image_id = $this->db->add_image($entry_id, $movefile['url'], $attach_id, '');

            wp_send_json_success(array(
                'image_id' => $image_id,
                'url' => $movefile['url'],
                'attachment_id' => $attach_id
            ));
        } else {
            wp_send_json_error(array('message' => $movefile['error']));
        }
    }

    /**
     * AJAX: Get diary entry
     */
    public function get_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $entry = $this->db->get_entry($entry_id);

        if ($entry) {
            // Get customer information if linked
            if ($entry->customer_id) {
                $customer = $this->db->get_customer($entry->customer_id);
                if ($customer) {
                    // Add formatted customer address
                    $address_parts = array_filter([
                        $customer->address_line_1,
                        $customer->address_line_2,
                        $customer->address_line_3,
                        $customer->postcode
                    ]);
                    $customer->customer_address = implode("\n", $address_parts);
                }
                $entry->customer = $customer;
            }

            // Get images for this entry
            $images = $this->db->get_entry_images($entry_id);
            $entry->images = $images;

            // Get job accessories
            $accessories = $this->db->get_job_accessories($entry_id);
            $entry->accessories = $accessories;

            // Get payments for this entry
            $payments = $this->db->get_entry_payments($entry_id);

            // Add user info and formatted dates to payments
            foreach ($payments as $payment) {
                $user = get_userdata($payment->recorded_by);
                $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';

                $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
                $time_format = get_option('wp_staff_diary_time_format', 'H:i');
                $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
            }

            $entry->payments = $payments;

            // Calculate financial totals
            $subtotal = $this->db->calculate_job_subtotal($entry_id);
            $entry->subtotal = $subtotal;

            // Calculate VAT
            $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
            $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

            $vat_amount = 0;
            $total = $subtotal;

            if ($vat_enabled == '1') {
                $vat_amount = $subtotal * ($vat_rate / 100);
                $total = $subtotal + $vat_amount;
            }

            $entry->vat_rate = $vat_rate;
            $entry->vat_amount = $vat_amount;
            $entry->total = $total;

            // Get total payments and calculate balance
            $total_payments = $this->db->get_entry_total_payments($entry_id);
            $entry->total_payments = $total_payments;
            $entry->balance = $total - $total_payments;

            // Format dates according to settings
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

            if ($entry->job_date) {
                $entry->job_date_formatted = date($date_format, strtotime($entry->job_date));
            }

            if ($entry->fitting_date) {
                $entry->fitting_date_formatted = date($date_format, strtotime($entry->fitting_date));
            }

            // Format time according to settings
            $time_format = get_option('wp_staff_diary_time_format', 'H:i');
            if ($entry->job_time) {
                $entry->job_time_formatted = date($time_format, strtotime($entry->job_time));
            }

            wp_send_json_success($entry);
        } else {
            wp_send_json_error(array('message' => 'Entry not found'));
        }
    }

    /**
     * AJAX: Delete diary image
     */
    public function delete_diary_image() {
        try {
            check_ajax_referer('wp_staff_diary_nonce', 'nonce');

            if (!isset($_POST['image_id'])) {
                wp_send_json_error(array('message' => 'No image ID provided'));
                return;
            }

            $image_id = intval($_POST['image_id']);

            if ($image_id <= 0) {
                wp_send_json_error(array('message' => 'Invalid image ID'));
                return;
            }

            // Get image details to delete attachment
            global $wpdb;
            $table = $wpdb->prefix . 'staff_diary_images';
            $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $image_id));

            if (!$image) {
                wp_send_json_error(array('message' => 'Image not found in database'));
                return;
            }

            // Delete WordPress attachment if exists
            if ($image->attachment_id) {
                wp_delete_attachment($image->attachment_id, true);
            }

            // Delete from database
            $result = $this->db->delete_image($image_id);

            if ($result !== false) {
                wp_send_json_success(array('message' => 'Image deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Database deletion failed'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Add payment
     */
    public function add_payment() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $payment_type = sanitize_text_field($_POST['payment_type']);
        $notes = sanitize_textarea_field($_POST['notes']);
        $recorded_by = get_current_user_id();

        $payment_id = $this->db->add_payment($entry_id, $amount, $payment_method, $payment_type, $notes, $recorded_by);

        if ($payment_id) {
            // Get the payment with user info
            $payment = $this->get_payment_with_user_info($payment_id);
            wp_send_json_success(array(
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to record payment'));
        }
    }

    /**
     * AJAX: Delete payment
     */
    public function delete_payment() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $payment_id = intval($_POST['payment_id']);

        $result = $this->db->delete_payment($payment_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Payment deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete payment'));
        }
    }

    /**
     * Get payment with user info
     */
    private function get_payment_with_user_info($payment_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE id = %d",
            $payment_id
        ));

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
     * AJAX: Add custom status
     */
    public function add_status() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $status_label = sanitize_text_field($_POST['label']);

        if (empty($status_label)) {
            wp_send_json_error(array('message' => 'Status name is required'));
        }

        // Get current statuses
        $statuses = get_option('wp_staff_diary_statuses', array(
            'quotation' => 'Quotation',
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ));

        // Create key from label
        $status_key = sanitize_title($status_label);

        // Check if status already exists
        if (isset($statuses[$status_key])) {
            wp_send_json_error(array('message' => 'Status already exists'));
        }

        // Add new status
        $statuses[$status_key] = $status_label;
        update_option('wp_staff_diary_statuses', $statuses);

        wp_send_json_success(array(
            'message' => 'Status added successfully',
            'status_key' => $status_key,
            'status_label' => $status_label
        ));
    }

    /**
     * AJAX: Delete custom status
     */
    public function delete_status() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $status_key = sanitize_text_field($_POST['status_key']);

        // Prevent deletion of default statuses
        $default_statuses = array('quotation', 'pending', 'in-progress', 'completed', 'cancelled');
        if (in_array($status_key, $default_statuses)) {
            wp_send_json_error(array('message' => 'Cannot delete default statuses'));
        }

        // Check if any jobs are using this status (excluding completed and cancelled)
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_diary WHERE status = %s AND status NOT IN ('completed', 'cancelled')",
            $status_key
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this status. There are $count active job(s) using it. Please change the status of these jobs first or complete/cancel them."
            ));
        }

        // Get current statuses
        $statuses = get_option('wp_staff_diary_statuses', array());

        // Remove the status
        if (isset($statuses[$status_key])) {
            unset($statuses[$status_key]);
            update_option('wp_staff_diary_statuses', $statuses);
            wp_send_json_success(array('message' => 'Status deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Status not found'));
        }
    }

    /**
     * AJAX: Add payment method
     */
    public function add_payment_method() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $method_label = sanitize_text_field($_POST['label']);

        if (empty($method_label)) {
            wp_send_json_error(array('message' => 'Payment method name is required'));
        }

        // Get current payment methods
        $payment_methods = get_option('wp_staff_diary_payment_methods', array(
            'cash' => 'Cash',
            'bank-transfer' => 'Bank Transfer',
            'card-payment' => 'Card Payment'
        ));

        // Create key from label
        $method_key = sanitize_title($method_label);

        // Check if method already exists
        if (isset($payment_methods[$method_key])) {
            wp_send_json_error(array('message' => 'Payment method already exists'));
        }

        // Add new payment method
        $payment_methods[$method_key] = $method_label;
        update_option('wp_staff_diary_payment_methods', $payment_methods);

        wp_send_json_success(array(
            'message' => 'Payment method added successfully',
            'method_key' => $method_key,
            'method_label' => $method_label
        ));
    }

    /**
     * AJAX: Delete payment method
     */
    public function delete_payment_method() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $method_key = sanitize_text_field($_POST['method_key']);

        // Check if any payments are using this method
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_payments WHERE payment_method = %s",
            $method_key
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this payment method. There are $count payment(s) using it."
            ));
        }

        // Get current payment methods
        $payment_methods = get_option('wp_staff_diary_payment_methods', array());

        // Remove the payment method
        if (isset($payment_methods[$method_key])) {
            unset($payment_methods[$method_key]);
            update_option('wp_staff_diary_payment_methods', $payment_methods);
            wp_send_json_success(array('message' => 'Payment method deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Payment method not found'));
        }
    }

    // ==================== FITTER AJAX HANDLERS ====================

    /**
     * AJAX: Add fitter
     */
    public function add_fitter() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $name = sanitize_text_field($_POST['name']);
        $color = sanitize_hex_color($_POST['color']);

        if (empty($name)) {
            wp_send_json_error('Fitter name is required');
        }

        if (empty($color)) {
            $color = '#3498db'; // Default color
        }

        // Get current fitters
        $fitters = get_option('wp_staff_diary_fitters', array());

        // Add new fitter
        $fitters[] = array(
            'name' => $name,
            'color' => $color
        );

        update_option('wp_staff_diary_fitters', $fitters);
        wp_send_json_success('Fitter added successfully');
    }

    /**
     * AJAX: Delete fitter
     */
    public function delete_fitter() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $index = intval($_POST['index']);

        // Get current fitters
        $fitters = get_option('wp_staff_diary_fitters', array());

        // Check if index exists
        if (!isset($fitters[$index])) {
            wp_send_json_error('Fitter not found');
        }

        // Check if any jobs are assigned to this fitter
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_diary WHERE fitter_id = %d",
            $index
        ));

        if ($count > 0) {
            wp_send_json_error("Cannot delete this fitter. There are $count job(s) assigned to them.");
        }

        // Remove the fitter
        unset($fitters[$index]);
        $fitters = array_values($fitters); // Re-index array
        update_option('wp_staff_diary_fitters', $fitters);
        wp_send_json_success('Fitter deleted successfully');
    }

    // ==================== ACCESSORY AJAX HANDLERS ====================

    /**
     * AJAX: Add accessory
     */
    public function add_accessory() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_name = sanitize_text_field($_POST['accessory_name']);
        $price = floatval($_POST['price']);

        if (empty($accessory_name)) {
            wp_send_json_error(array('message' => 'Accessory name is required'));
        }

        $data = array(
            'accessory_name' => $accessory_name,
            'price' => $price,
            'is_active' => 1,
            'display_order' => 0
        );

        $accessory_id = $this->db->create_accessory($data);

        if ($accessory_id) {
            $accessory = $this->db->get_accessory($accessory_id);
            wp_send_json_success(array(
                'message' => 'Accessory added successfully',
                'accessory' => $accessory
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to add accessory'));
        }
    }

    /**
     * AJAX: Update accessory
     */
    public function update_accessory() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_id = intval($_POST['accessory_id']);
        $accessory_name = sanitize_text_field($_POST['accessory_name']);
        $price = floatval($_POST['price']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($accessory_name)) {
            wp_send_json_error(array('message' => 'Accessory name is required'));
        }

        $data = array(
            'accessory_name' => $accessory_name,
            'price' => $price,
            'is_active' => $is_active
        );

        $result = $this->db->update_accessory($accessory_id, $data);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Accessory updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update accessory'));
        }
    }

    /**
     * AJAX: Delete accessory
     */
    public function delete_accessory() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_id = intval($_POST['accessory_id']);

        // Check if any jobs are using this accessory
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_job_accessories WHERE accessory_id = %d",
            $accessory_id
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this accessory. It's being used in $count job(s)."
            ));
        }

        $result = $this->db->delete_accessory($accessory_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Accessory deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete accessory'));
        }
    }

    // ==================== PDF GENERATION AJAX HANDLER ====================

    /**
     * AJAX: Generate PDF job sheet
     */
    public function generate_pdf() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);

        // Verify permissions
        $entry = $this->db->get_entry($entry_id);
        $user_id = get_current_user_id();

        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_send_json_error(array(
                'message' => 'PDF generation not available. TCPDF library not installed. Please see libs/README.md for installation instructions.'
            ));
        }

        // Generate and save PDF
        $result = $pdf_generator->generate_job_sheet($entry_id, 'F');

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'PDF generated successfully',
                'url' => $result['url']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Download PDF (direct output)
     */
    public function download_pdf() {
        if (!isset($_GET['entry_id']) || !isset($_GET['nonce'])) {
            wp_die('Invalid request');
        }

        if (!wp_verify_nonce($_GET['nonce'], 'wp_staff_diary_nonce')) {
            wp_die('Invalid nonce');
        }

        $entry_id = intval($_GET['entry_id']);
        $entry = $this->db->get_entry($entry_id);
        $user_id = get_current_user_id();

        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_die('Permission denied');
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_die('PDF generation not available. Please install TCPDF library.');
        }

        // Generate and output PDF (D = download)
        $pdf_generator->generate_job_sheet($entry_id, 'D');
    }

    /**
     * AJAX: Generate Quote PDF
     */
    public function generate_quote_pdf() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $quote_id = intval($_POST['quote_id']);

        // Verify permissions
        $quote = $this->db->get_entry($quote_id);
        $user_id = get_current_user_id();

        if (!$quote || ($quote->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Verify it's a quotation
        if ($quote->status !== 'quotation') {
            wp_send_json_error(array('message' => 'This entry is not a quotation'));
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_send_json_error(array(
                'message' => 'PDF generation not available. TCPDF library not installed. Please see libs/README.md for installation instructions.'
            ));
        }

        // Generate and save PDF
        $result = $pdf_generator->generate_quote_pdf($quote_id, 'F');

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Quote PDF generated successfully',
                'url' => $result['url']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Download Quote PDF (direct output)
     */
    public function download_quote_pdf() {
        if (!isset($_GET['quote_id']) || !isset($_GET['nonce'])) {
            wp_die('Invalid request');
        }

        if (!wp_verify_nonce($_GET['nonce'], 'wp_staff_diary_nonce')) {
            wp_die('Invalid nonce');
        }

        $quote_id = intval($_GET['quote_id']);
        $quote = $this->db->get_entry($quote_id);
        $user_id = get_current_user_id();

        if (!$quote || ($quote->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_die('Permission denied');
        }

        if ($quote->status !== 'quotation') {
            wp_die('This entry is not a quotation');
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_die('PDF generation not available. Please install TCPDF library.');
        }

        // Generate and output PDF (D = download)
        $pdf_generator->generate_quote_pdf($quote_id, 'D');
    }

    // ==================== CUSTOMER AJAX HANDLERS ====================

    /**
     * AJAX: Search customers
     */
    public function search_customers() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $customers = $this->db->get_all_customers($search);

        wp_send_json_success(array('customers' => $customers));
    }

    /**
     * AJAX: Add customer
     */
    public function add_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_name = sanitize_text_field($_POST['customer_name']);
        $address_line_1 = isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '';
        $address_line_2 = isset($_POST['address_line_2']) ? sanitize_text_field($_POST['address_line_2']) : '';
        $address_line_3 = isset($_POST['address_line_3']) ? sanitize_text_field($_POST['address_line_3']) : '';
        $postcode = isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($customer_name)) {
            wp_send_json_error(array('message' => 'Customer name is required'));
        }

        $data = array(
            'customer_name' => $customer_name,
            'address_line_1' => $address_line_1,
            'address_line_2' => $address_line_2,
            'address_line_3' => $address_line_3,
            'postcode' => $postcode,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes
        );

        $customer_id = $this->db->create_customer($data);

        if ($customer_id) {
            $customer = $this->db->get_customer($customer_id);
            wp_send_json_success(array(
                'message' => 'Customer added successfully',
                'customer' => $customer
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to add customer'));
        }
    }

    /**
     * AJAX: Get customer
     */
    public function get_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_id = intval($_POST['customer_id']);
        $customer = $this->db->get_customer($customer_id);

        if ($customer) {
            // Get job count for this customer
            $job_count = $this->db->get_customer_jobs_count($customer_id);
            $customer->job_count = $job_count;

            wp_send_json_success(array('customer' => $customer));
        } else {
            wp_send_json_error(array('message' => 'Customer not found'));
        }
    }

    /**
     * AJAX: Update customer
     */
    public function update_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_id = intval($_POST['customer_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $address_line_1 = isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '';
        $address_line_2 = isset($_POST['address_line_2']) ? sanitize_text_field($_POST['address_line_2']) : '';
        $address_line_3 = isset($_POST['address_line_3']) ? sanitize_text_field($_POST['address_line_3']) : '';
        $postcode = isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($customer_name)) {
            wp_send_json_error(array('message' => 'Customer name is required'));
        }

        $data = array(
            'customer_name' => $customer_name,
            'address_line_1' => $address_line_1,
            'address_line_2' => $address_line_2,
            'address_line_3' => $address_line_3,
            'postcode' => $postcode,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes
        );

        $result = $this->db->update_customer($customer_id, $data);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Customer updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update customer'));
        }
    }

    /**
     * AJAX: Delete customer
     */
    public function delete_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $customer_id = intval($_POST['customer_id']);

        // Check if customer has any jobs
        $job_count = $this->db->get_customer_jobs_count($customer_id);

        if ($job_count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this customer. They have $job_count job(s) associated with them."
            ));
        }

        $result = $this->db->delete_customer($customer_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Customer deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete customer'));
        }
    }

    // ==================== WOOCOMMERCE AJAX HANDLERS ====================

    /**
     * AJAX: Search WooCommerce products
     */
    public function search_woocommerce_products() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => 'WooCommerce is not active'));
            return;
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (empty($search)) {
            wp_send_json_success(array('products' => array()));
            return;
        }

        // Query WooCommerce products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            's' => $search,
            'orderby' => 'relevance',
        );

        $query = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'sku' => $product->get_sku(),
                        'price' => $product->get_regular_price(),
                        'description' => wp_trim_words($product->get_description(), 20),
                        'short_description' => $product->get_short_description(),
                    );
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array('products' => $products));
    }

    /**
     * AJAX: Delete all jobs (DANGER ZONE - Testing Only)
     */
    public function delete_all_jobs() {
        // Verify nonce
        check_ajax_referer('wp_staff_diary_delete_all_jobs', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        global $wpdb;

        // Count records before deletion
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $table_images = $wpdb->prefix . 'staff_diary_images';
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        $jobs_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_diary");
        $payments_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_payments");
        $images_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_images");
        $accessories_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_job_accessories");

        // Delete all data from job-related tables
        $wpdb->query("TRUNCATE TABLE $table_diary");
        $wpdb->query("TRUNCATE TABLE $table_payments");
        $wpdb->query("TRUNCATE TABLE $table_images");
        $wpdb->query("TRUNCATE TABLE $table_job_accessories");

        // Reset order number to start
        $order_start = get_option('wp_staff_diary_order_start', '01100');
        update_option('wp_staff_diary_order_current', $order_start);

        wp_send_json_success(array(
            'message' => 'All jobs deleted successfully!',
            'deleted' => array(
                'jobs' => $jobs_count,
                'payments' => $payments_count,
                'images' => $images_count,
                'accessories' => $accessories_count
            ),
            'new_order_start' => $order_start
        ));
    }

    /**
     * AJAX: Run database diagnostics
     * Scans the database and identifies sync issues, orphaned records, etc.
     */
    public function run_database_diagnostics() {
        // Verify nonce
        check_ajax_referer('wp_staff_diary_diagnostics', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $table_images = $wpdb->prefix . 'staff_diary_images';
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        // Run diagnostics
        $diagnostics = array();

        // 1. Count total jobs in database
        $total_jobs = $wpdb->get_var("SELECT COUNT(*) FROM $table_diary");
        $diagnostics['total_jobs'] = $total_jobs;

        // 2. Count jobs by user
        $jobs_by_user = $wpdb->get_results(
            "SELECT user_id, COUNT(*) as count FROM $table_diary GROUP BY user_id"
        );
        $diagnostics['jobs_by_user'] = $jobs_by_user;

        // 3. Current user's jobs
        $current_user_jobs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_diary WHERE user_id = %d",
            $current_user_id
        ));
        $diagnostics['current_user_jobs'] = $current_user_jobs;

        // 4. Check for cancelled jobs (is_cancelled = 1)
        $cancelled_jobs = $wpdb->get_var("SELECT COUNT(*) FROM $table_diary WHERE is_cancelled = 1");
        $diagnostics['cancelled_jobs'] = $cancelled_jobs;

        // 5. Check for jobs with unknown fitting dates
        $unknown_fitting_dates = $wpdb->get_var("SELECT COUNT(*) FROM $table_diary WHERE fitting_date_unknown = 1");
        $diagnostics['unknown_fitting_dates'] = $unknown_fitting_dates;

        // 6. Orphaned payments (payments with no matching job)
        $orphaned_payments = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_payments p
             LEFT JOIN $table_diary d ON p.diary_entry_id = d.id
             WHERE d.id IS NULL"
        );
        $diagnostics['orphaned_payments'] = $orphaned_payments;

        // 7. Orphaned images
        $orphaned_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_images i
             LEFT JOIN $table_diary d ON i.diary_entry_id = d.id
             WHERE d.id IS NULL"
        );
        $diagnostics['orphaned_images'] = $orphaned_images;

        // 8. Orphaned job accessories
        $orphaned_accessories = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_job_accessories a
             LEFT JOIN $table_diary d ON a.diary_entry_id = d.id
             WHERE d.id IS NULL"
        );
        $diagnostics['orphaned_accessories'] = $orphaned_accessories;

        // 9. Jobs with invalid customer IDs
        $invalid_customers = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_diary d
             LEFT JOIN $table_customers c ON d.customer_id = c.id
             WHERE d.customer_id IS NOT NULL AND c.id IS NULL"
        );
        $diagnostics['invalid_customers'] = $invalid_customers;

        // 10. Get list of all WordPress users (to identify if user_id issues exist)
        $wp_users = get_users(array('fields' => array('ID', 'user_login', 'display_name')));
        $diagnostics['wp_users'] = array_map(function($user) {
            return array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name
            );
        }, $wp_users);

        // 11. Check order number sequence
        $order_current = get_option('wp_staff_diary_order_current', '01100');
        $order_start = get_option('wp_staff_diary_order_start', '01100');
        $highest_order = $wpdb->get_var("SELECT MAX(CAST(order_number AS UNSIGNED)) FROM $table_diary");
        $diagnostics['order_numbers'] = array(
            'current' => $order_current,
            'start' => $order_start,
            'highest_in_db' => $highest_order
        );

        wp_send_json_success(array(
            'diagnostics' => $diagnostics,
            'issues_found' => (
                $orphaned_payments > 0 ||
                $orphaned_images > 0 ||
                $orphaned_accessories > 0 ||
                $invalid_customers > 0
            )
        ));
    }

    /**
     * AJAX: Repair database issues
     * Fixes orphaned records and reassigns jobs to current user if needed
     */
    public function repair_database() {
        // Verify nonce
        check_ajax_referer('wp_staff_diary_diagnostics', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $repair_action = isset($_POST['repair_action']) ? sanitize_text_field($_POST['repair_action']) : '';

        if (empty($repair_action)) {
            wp_send_json_error('No repair action specified');
            return;
        }

        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $table_images = $wpdb->prefix . 'staff_diary_images';
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';
        $repaired = array();

        switch ($repair_action) {
            case 'reassign_to_current_user':
                // Reassign all jobs to current user
                $current_user_id = get_current_user_id();
                $affected = $wpdb->query($wpdb->prepare(
                    "UPDATE $table_diary SET user_id = %d",
                    $current_user_id
                ));
                $repaired['jobs_reassigned'] = $affected;
                break;

            case 'clean_orphaned_records':
                // Delete orphaned payments
                $deleted_payments = $wpdb->query(
                    "DELETE p FROM $table_payments p
                     LEFT JOIN $table_diary d ON p.diary_entry_id = d.id
                     WHERE d.id IS NULL"
                );
                $repaired['orphaned_payments_deleted'] = $deleted_payments;

                // Delete orphaned images
                $deleted_images = $wpdb->query(
                    "DELETE i FROM $table_images i
                     LEFT JOIN $table_diary d ON i.diary_entry_id = d.id
                     WHERE d.id IS NULL"
                );
                $repaired['orphaned_images_deleted'] = $deleted_images;

                // Delete orphaned accessories
                $deleted_accessories = $wpdb->query(
                    "DELETE a FROM $table_job_accessories a
                     LEFT JOIN $table_diary d ON a.diary_entry_id = d.id
                     WHERE d.id IS NULL"
                );
                $repaired['orphaned_accessories_deleted'] = $deleted_accessories;
                break;

            case 'clear_invalid_customers':
                // Set invalid customer_id to NULL
                $affected = $wpdb->query(
                    "UPDATE $table_diary d
                     LEFT JOIN {$wpdb->prefix}staff_diary_customers c ON d.customer_id = c.id
                     SET d.customer_id = NULL
                     WHERE d.customer_id IS NOT NULL AND c.id IS NULL"
                );
                $repaired['invalid_customers_cleared'] = $affected;
                break;

            default:
                wp_send_json_error('Invalid repair action');
                return;
        }

        wp_send_json_success(array(
            'message' => 'Database repaired successfully!',
            'repaired' => $repaired
        ));
    }

    /**
     * AJAX: Get customer jobs
     * Returns all jobs for a specific customer with financial details
     */
    public function get_customer_jobs() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (empty($customer_id)) {
            wp_send_json_error(array('message' => 'Customer ID is required'));
            return;
        }

        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Get all jobs for this customer (including cancelled for history)
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_diary
             WHERE customer_id = %d
             ORDER BY job_date DESC, created_at DESC",
            $customer_id
        ));

        // Calculate totals for each job
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        foreach ($jobs as $job) {
            $subtotal = $this->db->calculate_job_subtotal($job->id);
            $total = $subtotal;
            if ($vat_enabled == '1') {
                $total = $subtotal * (1 + ($vat_rate / 100));
            }
            $job->total = $total;
            $job->subtotal = $subtotal;
        }

        wp_send_json_success(array(
            'jobs' => $jobs,
            'customer_id' => $customer_id
        ));
    }

    /**
     * AJAX: Get fitter availability
     * Returns availability for a specific fitter over a date range
     */
    public function get_fitter_availability() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $fitter_id = isset($_POST['fitter_id']) && $_POST['fitter_id'] !== '' ? intval($_POST['fitter_id']) : null;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $days = isset($_POST['days']) ? intval($_POST['days']) : 14; // Default 2 weeks

        if ($fitter_id === null) {
            wp_send_json_error(array('message' => 'Fitter ID is required'));
            return;
        }

        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Calculate date range
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $days . ' days'));

        // Get all jobs for this fitter in the date range (excluding cancelled and quotations)
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT fitting_date, fitting_time_period, order_number, status
             FROM $table_diary
             WHERE fitter_id = %d
             AND is_cancelled = 0
             AND status != 'quotation'
             AND fitting_date_unknown = 0
             AND fitting_date BETWEEN %s AND %s
             ORDER BY fitting_date ASC",
            $fitter_id,
            $start_date,
            $end_date
        ));

        // Organize jobs by date
        $availability = array();
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($current <= $end) {
            $date_str = $current->format('Y-m-d');
            $day_of_week = $current->format('N'); // 1=Monday, 7=Sunday

            // Skip Sundays by default (can be configured later)
            if ($day_of_week == 7) {
                $current->modify('+1 day');
                continue;
            }

            $availability[$date_str] = array(
                'date' => $date_str,
                'day_name' => $current->format('l'),
                'jobs' => array(),
                'am_available' => true,
                'pm_available' => true,
                'all_day_booked' => false
            );

            $current->modify('+1 day');
        }

        // Mark booked slots
        foreach ($jobs as $job) {
            if (isset($availability[$job->fitting_date])) {
                $availability[$job->fitting_date]['jobs'][] = array(
                    'order_number' => $job->order_number,
                    'time_period' => $job->fitting_time_period,
                    'status' => $job->status
                );

                // Update availability based on time period
                $time_period = strtolower($job->fitting_time_period);
                if ($time_period === 'am') {
                    $availability[$job->fitting_date]['am_available'] = false;
                } elseif ($time_period === 'pm') {
                    $availability[$job->fitting_date]['pm_available'] = false;
                } elseif ($time_period === 'all-day') {
                    $availability[$job->fitting_date]['am_available'] = false;
                    $availability[$job->fitting_date]['pm_available'] = false;
                    $availability[$job->fitting_date]['all_day_booked'] = true;
                }
            }
        }

        wp_send_json_success(array(
            'availability' => array_values($availability),
            'fitter_id' => $fitter_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }

    /**
     * AJAX: Convert quote to job
     * Updates the quote entry with fitting details and changes status to pending
     */
    public function convert_quote_to_job() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $quote_id = intval($_POST['quote_id']);
        $fitting_date = isset($_POST['fitting_date']) ? sanitize_text_field($_POST['fitting_date']) : null;
        $fitting_time_period = isset($_POST['fitting_time_period']) ? sanitize_text_field($_POST['fitting_time_period']) : null;
        $fitter_id = isset($_POST['fitter_id']) && $_POST['fitter_id'] !== '' ? intval($_POST['fitter_id']) : null;
        $fitting_date_unknown = isset($_POST['fitting_date_unknown']) ? intval($_POST['fitting_date_unknown']) : 0;

        if (empty($quote_id)) {
            wp_send_json_error(array('message' => 'Quote ID is required'));
            return;
        }

        // Verify the entry exists and is a quotation
        $entry = $this->db->get_entry($quote_id);
        if (!$entry) {
            wp_send_json_error(array('message' => 'Quote not found'));
            return;
        }

        if ($entry->status !== 'quotation') {
            wp_send_json_error(array('message' => 'This entry is not a quotation'));
            return;
        }

        // Verify ownership or admin
        $user_id = get_current_user_id();
        if ($entry->user_id != $user_id && !current_user_can('edit_users')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        // Update the entry with fitting details and change status to pending
        $update_data = array(
            'status' => 'pending',
            'fitter_id' => $fitter_id,
            'fitting_date' => $fitting_date,
            'fitting_time_period' => $fitting_time_period,
            'fitting_date_unknown' => $fitting_date_unknown
        );

        $result = $this->db->update_entry($quote_id, $update_data);

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Quote successfully converted to job',
                'entry_id' => $quote_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to convert quote to job'));
        }
    }
}
