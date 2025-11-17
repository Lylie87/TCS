<?php
/**
 * Quotes Page - Manage Quotations
 *
 * @since      2.4.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('edit_posts')) {
    wp_die(__('Sorry, you are not allowed to access this page.'));
}

$current_user = wp_get_current_user();
$db = new WP_Staff_Diary_Database();

// Get quotes (status = 'quotation')
global $wpdb;
$table_diary = $wpdb->prefix . 'staff_diary_entries';
$quotes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_diary
     WHERE user_id = %d
     AND status = 'quotation'
     AND is_cancelled = 0
     ORDER BY created_at DESC",
    $current_user->ID
));

// Get statuses for dropdown
$statuses = get_option('wp_staff_diary_statuses', array(
    'quotation' => 'Quotation',
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));

// Get accessories for selection
$accessories = $db->get_all_accessories(true); // Active only

// Get fitters (for conversion modal)
$fitters = get_option('wp_staff_diary_fitters', array());

// Get VAT settings
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');
?>

<div class="wrap wp-staff-diary-wrap">
    <h1>
        <span class="dashicons dashicons-edit-page"></span> <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <div class="diary-header">
        <div class="date-selector">
            <input type="text" id="quote-search" placeholder="Search by customer name..." style="width: 300px;">
        </div>

        <div class="view-actions">
            <button type="button" class="button button-primary" id="add-new-quote">
                <span class="dashicons dashicons-plus-alt"></span> Add New Quote
            </button>
        </div>
    </div>

    <div class="diary-entries">
        <table class="wp-list-table widefat fixed striped quotes-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Quote #</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 25%;">Customer</th>
                    <th style="width: 18%;">Product</th>
                    <th style="width: 12%; text-align: right;">Total</th>
                    <th style="width: 21%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <p style="color: #666; font-size: 16px;">No quotes found. Click "Add New Quote" to create one.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quotes as $quote): ?>
                        <?php
                        $customer_id = isset($quote->customer_id) ? $quote->customer_id : null;
                        $customer = $customer_id ? $db->get_customer($customer_id) : null;

                        $subtotal = $db->calculate_job_subtotal($quote->id);
                        $total = $subtotal;
                        if ($vat_enabled == '1') {
                            $total = $subtotal * (1 + ($vat_rate / 100));
                        }

                        $order_number = isset($quote->order_number) ? $quote->order_number : 'Quote #' . $quote->id;
                        // Ensure customer_name is a string to avoid PHP 8.1+ deprecation warnings
                        $customer_name = $customer && $customer->customer_name ? $customer->customer_name : '';
                        ?>
                        <tr data-quote-id="<?php echo esc_attr($quote->id); ?>"
                            data-customer-name="<?php echo esc_attr(strtolower($customer_name)); ?>">
                            <td><strong><?php echo esc_html($order_number); ?></strong></td>
                            <td>
                                <?php echo esc_html(date('d/m/Y', strtotime($quote->created_at))); ?>
                            </td>
                            <td>
                                <?php if ($customer): ?>
                                    <strong><?php echo esc_html($customer->customer_name ?? ''); ?></strong>
                                    <?php if (!empty($customer->customer_phone)): ?>
                                        <br><small><?php echo esc_html($customer->customer_phone); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">No customer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Ensure product_description is a string to avoid PHP 8.1+ deprecation warnings
                                $product_desc = $quote->product_description ?? '';
                                echo !empty($product_desc) ? esc_html(wp_trim_words($product_desc, 5)) : '<span style="color: #999;">—</span>';
                                ?>
                            </td>
                            <td style="text-align: right;">
                                <strong>£<?php echo number_format($total, 2); ?></strong>
                            </td>
                            <td>
                                <button class="button button-small view-quote" data-id="<?php echo esc_attr($quote->id); ?>">View</button>
                                <button class="button button-small edit-quote" data-id="<?php echo esc_attr($quote->id); ?>">Edit</button>
                                <button class="button button-primary button-small convert-to-job" data-id="<?php echo esc_attr($quote->id); ?>">Convert to Job</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Quote Modal -->
<div id="quote-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2 id="quote-modal-title">Add New Quote</h2>

        <?php include WP_STAFF_DIARY_PATH . 'admin/views/partials/quote-form.php'; ?>
    </div>
</div>

<!-- View Quote Modal -->
<div id="view-quote-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <div id="quote-details-content"></div>
    </div>
</div>

<!-- Convert to Job Modal -->
<div id="convert-to-job-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 500px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2>Convert Quote to Job</h2>
        <p style="margin: 15px 0; color: #666;">Please provide the fitting details to convert this quote into a job.</p>

        <form id="convert-to-job-form">
            <input type="hidden" id="convert-quote-id">

            <div class="form-field">
                <label for="convert-fitting-date">Fitting Date <span class="required">*</span></label>
                <input type="date" id="convert-fitting-date" required>
            </div>

            <div class="form-field">
                <label for="convert-fitting-time-period">Time Period</label>
                <select id="convert-fitting-time-period">
                    <option value="">Not specified</option>
                    <option value="am">Morning (AM)</option>
                    <option value="pm">Afternoon (PM)</option>
                    <option value="all-day">All Day</option>
                </select>
            </div>

            <div class="form-field">
                <label for="convert-fitter">Fitter <span class="required">*</span></label>
                <select id="convert-fitter" required>
                    <option value="">Select a fitter...</option>
                    <?php foreach ($fitters as $fitter_id => $fitter): ?>
                        <option value="<?php echo esc_attr($fitter_id); ?>">
                            <?php echo esc_html($fitter['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Select a fitter to see their availability</p>
            </div>

            <!-- Availability Display -->
            <div id="fitter-availability-display" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Fitter Availability (Next 2 Weeks)</h4>
                <div id="availability-loading" style="display: none; text-align: center; padding: 20px;">
                    <span class="dashicons dashicons-update dashicons-spin" style="font-size: 24px;"></span>
                    <p>Loading availability...</p>
                </div>
                <div id="availability-calendar" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
                    <!-- Availability will be populated here -->
                </div>
                <div id="availability-legend" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px;">
                    <strong>Legend:</strong>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #4caf50; border-radius: 2px; margin-right: 5px;"></span>
                        Available
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #ff9800; border-radius: 2px; margin-right: 5px;"></span>
                        Partially Booked
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #f44336; border-radius: 2px; margin-right: 5px;"></span>
                        Fully Booked
                    </span>
                </div>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" id="convert-fitting-date-unknown">
                    Fitting date not yet confirmed
                </label>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary">Convert to Job</button>
                <button type="button" class="button cancel-convert">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add New Customer Inline Modal -->
<div id="quick-add-customer-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 500px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2>Add New Customer</h2>
        <form id="quick-add-customer-form">
            <div class="form-field">
                <label for="quick-customer-name">Customer Name <span class="required">*</span></label>
                <input type="text" id="quick-customer-name" required>
            </div>
            <div class="form-field">
                <label for="quick-customer-phone">Phone</label>
                <input type="tel" id="quick-customer-phone">
            </div>
            <div class="form-field">
                <label for="quick-customer-email">Email</label>
                <input type="email" id="quick-customer-email">
            </div>
            <div class="form-field">
                <label for="quick-address-line-1">Address Line 1</label>
                <input type="text" id="quick-address-line-1">
            </div>
            <div class="form-field">
                <label for="quick-address-line-2">Address Line 2</label>
                <input type="text" id="quick-address-line-2">
            </div>
            <div class="form-field">
                <label for="quick-address-line-3">Address Line 3</label>
                <input type="text" id="quick-address-line-3">
            </div>
            <div class="form-field">
                <label for="quick-postcode">Postcode</label>
                <input type="text" id="quick-postcode" style="max-width: 150px;">
            </div>
            <div class="modal-footer">
                <button type="submit" class="button button-primary">Add Customer</button>
                <button type="button" class="button" id="cancel-quick-customer">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var vatEnabled = <?php echo $vat_enabled; ?>;
    var vatRate = <?php echo $vat_rate; ?>;

    jQuery(document).ready(function($) {
        // Search quotes by customer name
        $('#quote-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();

            $('.quotes-table tbody tr').not(':has(td[colspan])').each(function() {
                const $row = $(this);
                const customerName = $row.data('customer-name') || '';

                if (customerName.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show/hide "no results" message
            const visibleRows = $('.quotes-table tbody tr:visible').not(':has(td[colspan])').length;
            if (visibleRows === 0 && searchTerm) {
                if ($('.quotes-table tbody .no-results-row').length === 0) {
                    $('.quotes-table tbody').append(
                        '<tr class="no-results-row"><td colspan="6" style="text-align: center; padding: 40px;">' +
                        '<p style="color: #666; font-size: 16px;">No quotes match your search.</p></td></tr>'
                    );
                }
            } else {
                $('.quotes-table tbody .no-results-row').remove();
            }
        });

        // Toggle fitting date field based on checkbox
        $('#convert-fitting-date-unknown').on('change', function() {
            if ($(this).is(':checked')) {
                $('#convert-fitting-date').prop('required', false).prop('disabled', true);
                $('#convert-fitting-time-period').prop('disabled', true);
            } else {
                $('#convert-fitting-date').prop('required', true).prop('disabled', false);
                $('#convert-fitting-time-period').prop('disabled', false);
            }
        });
    });
</script>
