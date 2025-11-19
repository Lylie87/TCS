<?php
/**
 * Shared Job Entry Form
 * Used by both List View (my-diary.php) and Calendar View (calendar-view.php)
 *
 * @since      2.2.1
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings for form configuration
$job_time_type = get_option('wp_staff_diary_job_time_type', 'ampm');
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');
$currency_symbol = get_option('wp_staff_diary_currency_symbol', '£');
$date_format = get_option('wp_staff_diary_date_format', 'Y-m-d');
$statuses = get_option('wp_staff_diary_statuses', array());
$default_status = get_option('wp_staff_diary_default_status', 'pending');
$db = new WP_Staff_Diary_Database();
$accessories = $db->get_all_accessories();
?>

<!-- Job Entry Form -->
<form id="diary-entry-form">
    <input type="hidden" id="entry-id" name="entry_id" value="">
    <input type="hidden" id="order-number" name="order_number" value="">

    <div class="form-sections">
        <!-- Order Info Section -->
        <div class="form-section">
            <h3>Order Information</h3>
            <div class="form-grid">
                <div class="form-field" id="order-number-display" style="display: none;">
                    <label>Order Number</label>
                    <div><strong id="order-number-value" style="font-size: 18px; color: #2271b1;"></strong></div>
                </div>
                <div class="form-field">
                    <label for="job-type">Job Type <span class="required">*</span></label>
                    <select id="job-type" name="job_type" required>
                        <option value="domestic">Domestic</option>
                        <option value="commercial">Commercial</option>
                    </select>
                    <p class="description">Select whether this is a domestic or commercial job.</p>
                </div>
            </div>
            <div class="form-field">
                <label for="status">Status <span class="required">*</span></label>
                <select id="status" name="status" required>
                    <?php foreach ($statuses as $status_key => $status_label): ?>
                        <option value="<?php echo esc_attr($status_key); ?>"
                            <?php echo ($status_key === $default_status) ? 'selected' : ''; ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Current status of this job.</p>
            </div>
        </div>

        <!-- Customer Section -->
        <div class="form-section">
            <h3>Customer Details</h3>
            <div class="customer-selection">
                <input type="hidden" id="customer-id" name="customer_id" value="">
                <input type="text" id="customer-search" placeholder="Search for existing customer..." autocomplete="off">
                <div id="customer-search-results" class="search-results"></div>
                <div id="selected-customer-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                    <strong>Selected Customer:</strong> <span id="selected-customer-name"></span>
                    <button type="button" class="button button-small" id="clear-customer-btn" style="margin-left: 10px;">Change Customer</button>
                </div>
                <button type="button" class="button" id="add-new-customer-inline" style="margin-top: 10px;">
                    <span class="dashicons dashicons-plus-alt"></span> Add New Customer
                </button>
            </div>
        </div>

        <!-- Fitter Section -->
        <div class="form-section">
            <h3>Fitter Assignment</h3>
            <div class="form-field">
                <label for="fitter">Assign to Fitter</label>
                <select id="fitter" name="fitter_id">
                    <option value="">None</option>
                    <?php
                    $fitters = get_option('wp_staff_diary_fitters', array());
                    foreach ($fitters as $fitter_id => $fitter):
                    ?>
                        <option value="<?php echo esc_attr($fitter_id); ?>">
                            <?php echo esc_html($fitter['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Address Section -->
        <div class="form-section">
            <h3>Fitting Address</h3>
            <div class="form-field">
                <label for="fitting-address-line-1">Address Line 1</label>
                <input type="text" id="fitting-address-line-1" name="fitting_address_line_1">
            </div>
            <div class="form-field">
                <label for="fitting-address-line-2">Address Line 2</label>
                <input type="text" id="fitting-address-line-2" name="fitting_address_line_2">
            </div>
            <div class="form-field">
                <label for="fitting-address-line-3">City/Town</label>
                <input type="text" id="fitting-address-line-3" name="fitting_address_line_3">
            </div>
            <div class="form-field">
                <label for="fitting-postcode">Postcode</label>
                <input type="text" id="fitting-postcode" name="fitting_postcode">
            </div>

            <div class="form-field" style="margin-top: 15px;">
                <label>
                    <input type="checkbox" id="billing-address-different" name="billing_address_different" value="1">
                    <strong>Billing address is different from fitting address</strong>
                </label>
            </div>

            <div id="billing-address-section" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Billing Address</h4>
                <div class="form-field">
                    <label for="billing-address-line-1">Address Line 1</label>
                    <input type="text" id="billing-address-line-1" name="billing_address_line_1">
                </div>
                <div class="form-field">
                    <label for="billing-address-line-2">Address Line 2</label>
                    <input type="text" id="billing-address-line-2" name="billing_address_line_2">
                </div>
                <div class="form-field">
                    <label for="billing-address-line-3">City/Town</label>
                    <input type="text" id="billing-address-line-3" name="billing_address_line_3">
                </div>
                <div class="form-field">
                    <label for="billing-postcode">Postcode</label>
                    <input type="text" id="billing-postcode" name="billing_postcode">
                </div>
            </div>
        </div>

        <!-- Job Details Section -->
        <div class="form-section">
            <h3>Job Details</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label for="job-date">Order Date <span class="required">*</span></label>
                    <input type="date" id="job-date" name="job_date" value="<?php echo date($date_format); ?>" required>
                </div>
                <?php if ($job_time_type === 'time'): ?>
                <div class="form-field">
                    <label for="job-time">Job Time</label>
                    <input type="time" id="job-time" name="job_time">
                </div>
                <?php endif; ?>
                <div class="form-field">
                    <label for="fitting-date">Fitting Date</label>
                    <input type="date" id="fitting-date" name="fitting_date">
                    <label style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                        <input type="checkbox" id="fitting-date-unknown" name="fitting_date_unknown" value="1">
                        <span>Fitting Date Unknown (stock needs to be ordered)</span>
                    </label>
                </div>
                <?php if ($job_time_type === 'ampm'): ?>
                <div class="form-field">
                    <label for="fitting-time-period">Fitting Time</label>
                    <select id="fitting-time-period" name="fitting_time_period">
                        <option value="">Select...</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Section -->
        <div class="form-section">
            <h3>Product Details</h3>

            <!-- Product Source Toggle -->
            <div class="form-field" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Product Source</label>
                <div style="display: flex; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="product_source" id="product-source-manual" value="manual" checked>
                        <span>Manual Entry</span>
                    </label>
                    <?php if (class_exists('WooCommerce')): ?>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="product_source" id="product-source-woocommerce" value="woocommerce">
                        <span>WooCommerce Product</span>
                    </label>
                    <?php else: ?>
                    <p class="description" style="color: #d63638; margin: 0;">
                        <strong>WooCommerce not detected.</strong> Install WooCommerce to select products from your store.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WooCommerce Product Search (hidden by default) -->
            <?php if (class_exists('WooCommerce')): ?>
            <div id="woocommerce-product-selector" style="display: none; margin-bottom: 20px;">
                <div class="form-field">
                    <label for="woocommerce-product-search">Search WooCommerce Products</label>
                    <div style="position: relative;">
                        <input type="text" id="woocommerce-product-search" placeholder="Type to search products..." autocomplete="off" style="width: 100%;">
                        <input type="hidden" id="woocommerce-product-id" name="woocommerce_product_id" value="">
                        <div id="woocommerce-product-results" class="search-results"></div>
                    </div>
                    <div id="selected-wc-product-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                        <strong>Selected Product:</strong> <span id="selected-wc-product-name"></span>
                        <button type="button" class="button button-small" id="clear-wc-product-btn" style="margin-left: 10px;">Change</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Product Details Fields -->
            <div id="product-details-fields">
                <div class="form-field">
                    <label for="product-description">Product Description</label>
                    <textarea id="product-description" name="product_description" rows="3"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="size">Size</label>
                        <input type="text" id="size" name="size" placeholder="e.g. 4 x 3">
                        <p class="description">Enter as length x width (e.g. 4 x 3 or 2.5 x 6). Will auto-calculate m².</p>
                    </div>
                    <div class="form-field">
                        <label for="sq-mtr-qty">Sq.Mtr / Quantity</label>
                        <input type="number" id="sq-mtr-qty" name="sq_mtr_qty" step="0.01" min="0" readonly style="background: #f0f0f1;">
                        <p class="description">Auto-calculated from Size field</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="price-per-sq-mtr">Price per Sq.Mtr (<?php echo esc_html($currency_symbol); ?>)</label>
                        <input type="number" id="price-per-sq-mtr" name="price_per_sq_mtr" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-field" style="margin-top: 15px;">
                    <label for="fitting-cost">Fitting Cost (<?php echo esc_html($currency_symbol); ?>)</label>
                    <input type="number" id="fitting-cost" name="fitting_cost" step="0.01" min="0" value="0.00">
                    <p class="description">Customer cost for fitting the product</p>
                </div>
            </div>

            <div class="calculation-display">
                <strong>Product Total:</strong> <?php echo esc_html($currency_symbol); ?><span id="product-total-display">0.00</span>
            </div>
        </div>

        <!-- Accessories Section -->
        <div class="form-section">
            <h3>Accessories</h3>
            <div id="accessories-list">
                <?php foreach ($accessories as $accessory): ?>
                    <div class="accessory-item">
                        <label>
                            <input type="checkbox" class="accessory-checkbox"
                                   data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                                   data-accessory-name="<?php echo esc_attr($accessory->accessory_name); ?>"
                                   data-price="<?php echo esc_attr($accessory->price); ?>">
                            <?php echo esc_html($accessory->accessory_name); ?>
                            (<?php echo esc_html($currency_symbol); ?><?php echo number_format($accessory->price, 2); ?>)
                        </label>
                        <input type="number" class="accessory-quantity"
                               data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                               min="1" value="1" step="0.01"
                               style="width: 80px; margin-left: 10px;"
                               disabled>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="calculation-display">
                <strong>Accessories Total:</strong> <?php echo esc_html($currency_symbol); ?><span id="accessories-total-display">0.00</span>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="form-section financial-summary">
            <h3>Financial Summary</h3>
            <table class="calculation-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="amount"><?php echo esc_html($currency_symbol); ?><span id="subtotal-display">0.00</span></td>
                </tr>
                <?php if ($vat_enabled == '1'): ?>
                <tr>
                    <td>VAT (<?php echo $vat_rate; ?>%):</td>
                    <td class="amount"><?php echo esc_html($currency_symbol); ?><span id="vat-display">0.00</span></td>
                </tr>
                <?php endif; ?>
                <tr id="original-total-row" style="display: none;">
                    <td>Original Total:</td>
                    <td class="amount"><?php echo esc_html($currency_symbol); ?><span id="original-total-display">0.00</span></td>
                </tr>
                <tr id="discount-row" style="display: none;">
                    <td>Discount (<span id="discount-label-display"></span>):</td>
                    <td class="amount" style="color: #2271b1;">-<?php echo esc_html($currency_symbol); ?><span id="discount-amount-display">0.00</span></td>
                </tr>
                <tr class="total-row">
                    <td><strong><span id="total-label">Total:</span></strong></td>
                    <td class="amount"><strong><?php echo esc_html($currency_symbol); ?><span id="total-display">0.00</span></strong></td>
                </tr>
            </table>
        </div>

        <!-- Photos Section -->
        <div class="form-section" id="photos-section" style="display: none;">
            <h3>Photos</h3>
            <div id="job-photos-container">
                <p class="description">No photos uploaded yet.</p>
            </div>
            <button type="button" class="button" id="upload-photo-form-btn">
                <span class="dashicons dashicons-camera"></span> Upload Photo
            </button>
            <input type="file" id="photo-upload-input-form" accept="image/*" style="display: none;">
        </div>

        <!-- Payment Recording Section -->
        <div class="form-section" id="payment-section" style="display: none;">
            <h3>Payments</h3>

            <!-- Payment History -->
            <div id="payment-history-container" style="margin-bottom: 15px;">
                <p class="description">No payments recorded yet.</p>
            </div>

            <!-- Record New Payment Form -->
            <div id="payment-form-container" class="payment-form" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                <h4 style="margin-top: 0; margin-bottom: 15px;">Record New Payment</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Amount (<?php echo esc_html($currency_symbol); ?>):</strong></label>
                        <input type="number" id="payment-amount-form" step="0.01" min="0.01" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Payment Method:</strong></label>
                        <select id="payment-method-form" style="width: 100%;">
                            <?php
                            $payment_methods = get_option('wp_staff_diary_payment_methods', array());
                            foreach ($payment_methods as $key => $label):
                            ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;"><strong>Payment Type:</strong></label>
                    <select id="payment-type-form" style="width: 100%;">
                        <option value="deposit">Deposit</option>
                        <option value="partial">Partial Payment</option>
                        <option value="final">Final Payment</option>
                        <option value="full">Full Payment</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;"><strong>Notes:</strong></label>
                    <textarea id="payment-notes-form" rows="2" style="width: 100%;"></textarea>
                </div>
                <button type="button" class="button button-primary" id="record-payment-form-btn">
                    <span class="dashicons dashicons-yes"></span> Record Payment
                </button>
            </div>
        </div>

        <!-- Internal Notes Section -->
        <div class="form-section">
            <h3>Internal Notes</h3>
            <div class="form-field">
                <textarea id="notes" name="notes" rows="4" placeholder="Add any internal notes or special instructions..."></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="button button-primary button-large" id="save-entry-btn">
            <span class="dashicons dashicons-yes"></span> Save Job
        </button>
        <button type="button" class="button button-large" id="cancel-entry-btn">Cancel</button>
    </div>
</form>
