<?php
/**
 * Quote Acceptance Handler
 *
 * Handles public quote acceptance page and processing
 *
 * @since      2.3.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Quote_Acceptance {

    private $db;

    public function __construct() {
        $this->db = new WP_Staff_Diary_Database();

        // Add rewrite rule
        add_action('init', array($this, 'add_rewrite_rules'));

        // Handle quote acceptance page
        add_action('template_redirect', array($this, 'handle_quote_acceptance_page'));

        // Handle quote acceptance form submission
        add_action('wp_ajax_nopriv_accept_quote', array($this, 'process_quote_acceptance'));
    }

    /**
     * Add rewrite rules for quote acceptance URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^quote-accept/([a-f0-9]{64})/?$',
            'index.php?quote_acceptance_token=$matches[1]',
            'top'
        );

        // Register query var
        add_filter('query_vars', function($vars) {
            $vars[] = 'quote_acceptance_token';
            return $vars;
        });

        // Flush rewrite rules if needed (only on plugin activation)
        if (get_option('wp_staff_diary_flush_rewrite_rules') === '1') {
            flush_rewrite_rules();
            delete_option('wp_staff_diary_flush_rewrite_rules');
        }
    }

    /**
     * Handle quote acceptance page display
     */
    public function handle_quote_acceptance_page() {
        $token = get_query_var('quote_acceptance_token');

        if (empty($token)) {
            return;
        }

        // Get entry by token
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_diary WHERE acceptance_token = %s",
            $token
        ));

        if (!$entry) {
            $this->display_error_page('Invalid or expired quote link');
            exit;
        }

        // Check if already accepted
        if (!empty($entry->accepted_date)) {
            $this->display_already_accepted_page($entry);
            exit;
        }

        // Check if quote has expired
        $quote_validity_days = get_option('wp_staff_diary_quote_validity_days', '30');
        $quote_date = $entry->quote_date ? $entry->quote_date : $entry->job_date;
        $expiry_timestamp = strtotime($quote_date) + ($quote_validity_days * 24 * 60 * 60);

        if (time() > $expiry_timestamp) {
            $this->display_expired_page($entry, $expiry_timestamp);
            exit;
        }

        // Display quote acceptance page
        $this->display_acceptance_page($entry, $token);
        exit;
    }

    /**
     * Display quote acceptance page
     */
    private function display_acceptance_page($entry, $token) {
        // Get related data
        $customer = $entry->customer_id ? $this->db->get_customer($entry->customer_id) : null;
        $accessories = $this->db->get_job_accessories($entry->id);

        // Calculate totals
        $subtotal = $this->db->calculate_job_subtotal($entry->id);
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        $vat_amount = 0;
        $total = $subtotal;
        if ($vat_enabled == '1') {
            $vat_amount = $subtotal * ($vat_rate / 100);
            $total = $subtotal + $vat_amount;
        }

        // Calculate discount if exists
        $discount_amount = 0;
        $has_discount = false;
        if (!empty($entry->discount_type) && !empty($entry->discount_value) && $entry->discount_value > 0) {
            $has_discount = true;
            $discount_amount = WP_Staff_Diary_Currency_Helper::calculate_discount($total, $entry->discount_type, $entry->discount_value);
            $total = $total - $discount_amount;
        }

        // Get company details
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $company_address = get_option('wp_staff_diary_company_address', '');
        $company_phone = get_option('wp_staff_diary_company_phone', '');
        $company_email = get_option('wp_staff_diary_company_email', '');
        $company_logo = get_option('wp_staff_diary_company_logo', '');
        $terms = get_option('wp_staff_diary_terms_conditions', '');

        // Get date format
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

        // Output HTML
        $this->output_page_header($company_name);
        ?>
        <div class="quote-acceptance-container">
            <div class="quote-header">
                <?php if ($company_logo && wp_get_attachment_url($company_logo)): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_url($company_logo)); ?>" alt="<?php echo esc_attr($company_name); ?>" class="company-logo">
                <?php endif; ?>
                <h1><?php echo esc_html($company_name); ?></h1>
                <?php if ($company_address): ?>
                    <p class="company-address"><?php echo nl2br(esc_html($company_address)); ?></p>
                <?php endif; ?>
                <?php if ($company_phone || $company_email): ?>
                    <p class="company-contact">
                        <?php if ($company_phone): ?>
                            Tel: <?php echo esc_html($company_phone); ?>
                        <?php endif; ?>
                        <?php if ($company_phone && $company_email): ?> | <?php endif; ?>
                        <?php if ($company_email): ?>
                            Email: <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="quote-card">
                <h2>Quote Details</h2>
                <div class="quote-info">
                    <div class="info-row">
                        <strong>Quote Number:</strong>
                        <span><?php echo esc_html($entry->order_number); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Quote Date:</strong>
                        <span><?php echo $entry->quote_date ? date($date_format, strtotime($entry->quote_date)) : date($date_format, strtotime($entry->job_date)); ?></span>
                    </div>
                    <?php if ($customer): ?>
                        <div class="info-row">
                            <strong>Customer:</strong>
                            <span><?php echo esc_html($customer->customer_name); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($entry->product_description): ?>
                    <div class="product-section">
                        <h3>Product/Service Description</h3>
                        <p><?php echo nl2br(esc_html($entry->product_description)); ?></p>
                    </div>
                <?php endif; ?>

                <div class="financial-summary">
                    <h3>Financial Summary</h3>
                    <table>
                        <tr>
                            <td>Subtotal:</td>
                            <td class="amount"><?php echo WP_Staff_Diary_Currency_Helper::format($subtotal); ?></td>
                        </tr>
                        <?php if ($vat_enabled == '1'): ?>
                            <tr>
                                <td>VAT (<?php echo $vat_rate; ?>%):</td>
                                <td class="amount"><?php echo WP_Staff_Diary_Currency_Helper::format($vat_amount); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($has_discount): ?>
                            <tr>
                                <td>Original Total:</td>
                                <td class="amount"><?php echo WP_Staff_Diary_Currency_Helper::format($total + $discount_amount); ?></td>
                            </tr>
                            <tr class="discount-row">
                                <td>
                                    <strong>Discount (<?php echo WP_Staff_Diary_Currency_Helper::format_discount($entry->discount_type, $entry->discount_value); ?>):</strong>
                                </td>
                                <td class="amount discount-amount">-<?php echo WP_Staff_Diary_Currency_Helper::format($discount_amount); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Final Total:</strong></td>
                                <td class="amount"><strong><?php echo WP_Staff_Diary_Currency_Helper::format($total); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr class="total-row">
                                <td><strong>Total:</strong></td>
                                <td class="amount"><strong><?php echo WP_Staff_Diary_Currency_Helper::format($total); ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <?php if ($terms): ?>
                    <div class="terms-section">
                        <h3>Terms & Conditions</h3>
                        <div class="terms-content">
                            <?php echo wp_kses_post($terms); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="acceptance-section">
                    <h3>Accept This Quote</h3>
                    <p>By clicking "Accept Quote" below, you agree to the terms and conditions and confirm your acceptance of this quote.</p>

                    <form id="quote-acceptance-form">
                        <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">

                        <div class="form-field">
                            <label for="customer_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="customer_email" name="customer_email" value="<?php echo $customer && $customer->customer_email ? esc_attr($customer->customer_email) : ''; ?>" required>
                            <p class="description">We'll send a confirmation to this email address.</p>
                        </div>

                        <div class="form-field">
                            <label>
                                <input type="checkbox" name="accept_terms" required>
                                I accept the terms and conditions
                            </label>
                        </div>

                        <button type="submit" class="accept-button" id="accept-quote-btn">
                            Accept Quote
                        </button>

                        <div id="acceptance-message" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('quote-acceptance-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const button = document.getElementById('accept-quote-btn');
            const message = document.getElementById('acceptance-message');

            button.disabled = true;
            button.textContent = 'Processing...';

            const formData = new FormData(this);
            formData.append('action', 'accept_quote');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    message.style.display = 'block';
                    message.style.background = '#d4edda';
                    message.style.color = '#155724';
                    message.style.padding = '15px';
                    message.style.borderRadius = '4px';
                    message.style.marginTop = '15px';
                    message.textContent = data.data.message;

                    // Hide form, show success message
                    document.getElementById('quote-acceptance-form').style.display = 'none';

                    // Refresh page after 2 seconds to show accepted state
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    message.style.display = 'block';
                    message.style.background = '#f8d7da';
                    message.style.color = '#721c24';
                    message.style.padding = '15px';
                    message.style.borderRadius = '4px';
                    message.style.marginTop = '15px';
                    message.textContent = 'Error: ' + data.data.message;

                    button.disabled = false;
                    button.textContent = 'Accept Quote';
                }
            })
            .catch(error => {
                message.style.display = 'block';
                message.style.background = '#f8d7da';
                message.style.color = '#721c24';
                message.style.padding = '15px';
                message.style.borderRadius = '4px';
                message.style.marginTop = '15px';
                message.textContent = 'An error occurred. Please try again.';

                button.disabled = false;
                button.textContent = 'Accept Quote';
            });
        });
        </script>
        <?php
        $this->output_page_footer();
    }

    /**
     * Display error page
     */
    private function display_error_page($message) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));

        $this->output_page_header($company_name);
        ?>
        <div class="quote-acceptance-container">
            <div class="error-message">
                <h2>Error</h2>
                <p><?php echo esc_html($message); ?></p>
            </div>
        </div>
        <?php
        $this->output_page_footer();
    }

    /**
     * Display already accepted page
     */
    private function display_already_accepted_page($entry) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

        $this->output_page_header($company_name);
        ?>
        <div class="quote-acceptance-container">
            <div class="success-message">
                <h2>Quote Already Accepted</h2>
                <p>This quote (#<?php echo esc_html($entry->order_number); ?>) was already accepted on <?php echo date($date_format . ' H:i', strtotime($entry->accepted_date)); ?>.</p>
                <p>Thank you for your business!</p>
            </div>
        </div>
        <?php
        $this->output_page_footer();
    }

    /**
     * Display expired page
     */
    private function display_expired_page($entry, $expiry_timestamp) {
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $company_email = get_option('wp_staff_diary_company_email', '');
        $company_phone = get_option('wp_staff_diary_company_phone', '');
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

        $this->output_page_header($company_name);
        ?>
        <div class="quote-acceptance-container">
            <div class="warning-message">
                <h2>Quote Expired</h2>
                <p>This quote (#<?php echo esc_html($entry->order_number); ?>) expired on <?php echo date($date_format, $expiry_timestamp); ?>.</p>
                <p>Please contact us to request a new quote.</p>
                <?php if ($company_email || $company_phone): ?>
                    <p class="contact-info">
                        <?php if ($company_phone): ?>
                            <strong>Phone:</strong> <?php echo esc_html($company_phone); ?><br>
                        <?php endif; ?>
                        <?php if ($company_email): ?>
                            <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $this->output_page_footer();
    }

    /**
     * Process quote acceptance
     */
    public function process_quote_acceptance() {
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';

        if (empty($token) || empty($customer_email)) {
            wp_send_json_error(array('message' => 'Missing required fields'));
        }

        // Get entry by token
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_diary WHERE acceptance_token = %s",
            $token
        ));

        if (!$entry) {
            wp_send_json_error(array('message' => 'Invalid quote link'));
        }

        // Check if already accepted
        if (!empty($entry->accepted_date)) {
            wp_send_json_error(array('message' => 'This quote has already been accepted'));
        }

        // Update entry as accepted
        $result = $wpdb->update(
            $table_diary,
            array(
                'accepted_date' => current_time('mysql'),
                'status' => 'in-progress' // Change status to in-progress when accepted
            ),
            array('id' => $entry->id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to process acceptance'));
        }

        // Update discount offer status if exists
        $table_discount_offers = $wpdb->prefix . 'staff_diary_discount_offers';
        $wpdb->update(
            $table_discount_offers,
            array('status' => 'accepted'),
            array('diary_entry_id' => $entry->id),
            array('%s'),
            array('%d')
        );

        // Log notification
        $table_notification_logs = $wpdb->prefix . 'staff_diary_notification_logs';
        $wpdb->insert(
            $table_notification_logs,
            array(
                'diary_entry_id' => $entry->id,
                'notification_type' => 'quote_accepted',
                'recipient' => $customer_email,
                'method' => 'web',
                'status' => 'success'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        wp_send_json_success(array(
            'message' => 'Thank you! Your quote acceptance has been received. We will be in touch shortly.'
        ));
    }

    /**
     * Output page header
     */
    private function output_page_header($title) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - Quote Acceptance</title>
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #f5f5f5;
                    padding: 20px;
                }
                .quote-acceptance-container {
                    max-width: 800px;
                    margin: 0 auto;
                }
                .quote-header {
                    text-align: center;
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .company-logo {
                    max-width: 200px;
                    max-height: 80px;
                    margin-bottom: 15px;
                }
                .quote-header h1 {
                    color: #2271b1;
                    margin-bottom: 10px;
                    font-size: 28px;
                }
                .company-address, .company-contact {
                    font-size: 14px;
                    color: #666;
                    margin: 5px 0;
                }
                .company-contact a {
                    color: #2271b1;
                    text-decoration: none;
                }
                .quote-card {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .quote-card h2 {
                    color: #2271b1;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #2271b1;
                }
                .quote-card h3 {
                    color: #333;
                    margin: 20px 0 10px 0;
                    font-size: 18px;
                }
                .quote-info {
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                .product-section {
                    margin: 20px 0;
                    padding: 15px;
                    background: #f9f9f9;
                    border-radius: 4px;
                }
                .financial-summary table {
                    width: 100%;
                    margin-top: 10px;
                }
                .financial-summary tr {
                    border-bottom: 1px solid #eee;
                }
                .financial-summary td {
                    padding: 10px 5px;
                }
                .financial-summary td.amount {
                    text-align: right;
                    font-weight: 500;
                }
                .financial-summary .discount-row {
                    background: #e3f2fd;
                }
                .financial-summary .discount-amount {
                    color: #2271b1;
                }
                .financial-summary .total-row {
                    background: #f0f0f0;
                    font-weight: bold;
                }
                .terms-section {
                    margin: 20px 0;
                    padding: 15px;
                    background: #f9f9f9;
                    border-radius: 4px;
                    font-size: 13px;
                }
                .acceptance-section {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f0f7ff;
                    border-radius: 4px;
                    border: 2px solid #2271b1;
                }
                .form-field {
                    margin: 15px 0;
                }
                .form-field label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                }
                .form-field input[type="email"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                }
                .form-field .description {
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                }
                .required {
                    color: #d63638;
                }
                .accept-button {
                    background: #2271b1;
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    font-size: 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 15px;
                }
                .accept-button:hover {
                    background: #135e96;
                }
                .accept-button:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                }
                .error-message, .success-message, .warning-message {
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    text-align: center;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .error-message {
                    border-left: 4px solid #d63638;
                }
                .success-message {
                    border-left: 4px solid #00a32a;
                }
                .warning-message {
                    border-left: 4px solid #dba617;
                }
                .error-message h2 {
                    color: #d63638;
                    margin-bottom: 15px;
                }
                .success-message h2 {
                    color: #00a32a;
                    margin-bottom: 15px;
                }
                .warning-message h2 {
                    color: #dba617;
                    margin-bottom: 15px;
                }
                .contact-info {
                    margin-top: 20px;
                    padding: 15px;
                    background: #f9f9f9;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
        <?php
    }

    /**
     * Output page footer
     */
    private function output_page_footer() {
        ?>
        </body>
        </html>
        <?php
    }
}
