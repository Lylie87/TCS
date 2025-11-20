<?php
/**
 * Simplified Quote Entry Form
 * Used by Quotes page - simpler version without fitter/fitting date (prompted during conversion)
 *
 * @since      2.4.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings for form configuration
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');
$currency_symbol = get_option('wp_staff_diary_currency_symbol', '£');
$date_format = get_option('wp_staff_diary_date_format', 'Y-m-d');
$db = new WP_Staff_Diary_Database();
$accessories = $db->get_all_accessories();
?>

<!-- Quote Entry Form -->
<form id="quote-entry-form">
    <input type="hidden" id="quote-entry-id" name="entry_id" value="">
    <input type="hidden" id="quote-order-number" name="order_number" value="">
    <input type="hidden" id="quote-status" name="status" value="quotation">
    <input type="hidden" id="quote-job-date" name="job_date" value="<?php echo date('Y-m-d'); ?>">

    <div class="form-sections">
        <!-- Quote Info Section -->
        <div class="form-section">
            <h3>Quote Information</h3>
            <div class="form-field" id="quote-number-display" style="display: none;">
                <label>Quote Number</label>
                <div><strong id="quote-number-value" style="font-size: 18px; color: #2271b1;"></strong></div>
            </div>
            <div class="form-field">
                <label for="quote-job-type">Job Type <span class="required">*</span></label>
                <select id="quote-job-type" name="job_type" required>
                    <option value="domestic">Domestic</option>
                    <option value="commercial">Commercial</option>
                </select>
                <p class="description">Select whether this is a domestic or commercial job.</p>
            </div>
            <p class="description">This quote will be saved as <strong>Quotation</strong> status. Convert to a job when the customer accepts.</p>
        </div>

        <!-- Customer Section -->
        <div class="form-section">
            <h3>Customer Details</h3>
            <div class="customer-selection">
                <input type="hidden" id="quote-customer-id" name="customer_id" value="">
                <input type="text" id="quote-customer-search" placeholder="Search for existing customer..." autocomplete="off">
                <div id="quote-customer-search-results" class="search-results"></div>
                <div id="quote-selected-customer-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                    <strong>Selected Customer:</strong> <span id="quote-selected-customer-name"></span>
                    <button type="button" class="button button-small" id="quote-clear-customer-btn" style="margin-left: 10px;">Change Customer</button>
                </div>
                <button type="button" class="button" id="quote-add-new-customer-inline" style="margin-top: 10px;">
                    <span class="dashicons dashicons-plus-alt"></span> Add New Customer
                </button>
            </div>
        </div>

        <!-- Address Section -->
        <div class="form-section">
            <h3>Fitting Address</h3>
            <div class="form-field">
                <label for="quote-fitting-address-line-1">Address Line 1</label>
                <input type="text" id="quote-fitting-address-line-1" name="fitting_address_line_1">
            </div>
            <div class="form-field">
                <label for="quote-fitting-address-line-2">Address Line 2</label>
                <input type="text" id="quote-fitting-address-line-2" name="fitting_address_line_2">
            </div>
            <div class="form-field">
                <label for="quote-fitting-address-line-3">City/Town</label>
                <input type="text" id="quote-fitting-address-line-3" name="fitting_address_line_3">
            </div>
            <div class="form-field">
                <label for="quote-fitting-postcode">Postcode</label>
                <input type="text" id="quote-fitting-postcode" name="fitting_postcode">
            </div>

            <div class="form-field" style="margin-top: 15px;">
                <label>
                    <input type="checkbox" id="quote-billing-address-different" name="billing_address_different" value="1">
                    <strong>Billing address is different from fitting address</strong>
                </label>
            </div>

            <div id="quote-billing-address-section" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Billing Address</h4>
                <div class="form-field">
                    <label for="quote-billing-address-line-1">Address Line 1</label>
                    <input type="text" id="quote-billing-address-line-1" name="billing_address_line_1">
                </div>
                <div class="form-field">
                    <label for="quote-billing-address-line-2">Address Line 2</label>
                    <input type="text" id="quote-billing-address-line-2" name="billing_address_line_2">
                </div>
                <div class="form-field">
                    <label for="quote-billing-address-line-3">City/Town</label>
                    <input type="text" id="quote-billing-address-line-3" name="billing_address_line_3">
                </div>
                <div class="form-field">
                    <label for="quote-billing-postcode">Postcode</label>
                    <input type="text" id="quote-billing-postcode" name="billing_postcode">
                </div>
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
                        <input type="radio" name="quote_product_source" id="quote-product-source-manual" value="manual" checked>
                        <span>Manual Entry</span>
                    </label>
                    <?php if (class_exists('WooCommerce')): ?>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="quote_product_source" id="quote-product-source-woocommerce" value="woocommerce">
                        <span>WooCommerce Product</span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WooCommerce Product Search (hidden by default) -->
            <?php if (class_exists('WooCommerce')): ?>
            <div id="quote-woocommerce-product-selector" style="display: none; margin-bottom: 20px;">
                <div class="form-field">
                    <label for="quote-woocommerce-product-search">Search WooCommerce Products</label>
                    <div style="position: relative;">
                        <input type="text" id="quote-woocommerce-product-search" placeholder="Type to search products..." autocomplete="off" style="width: 100%;">
                        <input type="hidden" id="quote-woocommerce-product-id" name="woocommerce_product_id" value="">
                        <div id="quote-woocommerce-product-results" class="search-results"></div>
                    </div>
                    <div id="quote-selected-wc-product-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                        <strong>Selected Product:</strong> <span id="quote-selected-wc-product-name"></span>
                        <button type="button" class="button button-small" id="quote-clear-wc-product-btn" style="margin-left: 10px;">Change</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Product Entry Form -->
            <div id="quote-product-entry-form">
                <div class="form-field">
                    <label for="quote-product-description">Product Description</label>
                    <textarea id="quote-product-description" rows="1" style="resize: vertical; min-height: 40px;"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="quote-size">Size</label>
                        <input type="text" id="quote-size" placeholder="e.g. 4 x 3">
                        <p class="description">Enter as length x width (e.g. 4 x 3). Will auto-calculate m².</p>
                    </div>
                    <div class="form-field">
                        <label for="quote-sq-mtr-qty">Sq.Mtr</label>
                        <input type="number" id="quote-sq-mtr-qty" step="0.01" min="0" readonly style="background: #f0f0f1;">
                        <p class="description">Auto-calculated</p>
                    </div>
                    <div class="form-field">
                        <label for="quote-price-per-sq-mtr">Price per Sq.Mtr (<?php echo esc_html($currency_symbol); ?>)</label>
                        <input type="number" id="quote-price-per-sq-mtr" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-field" style="margin-top: 15px;">
                    <input type="hidden" id="quote-current-product-id" value="">
                    <button type="button" id="quote-add-product-btn" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span> Add Product
                    </button>
                    <button type="button" id="quote-cancel-product-edit-btn" class="button" style="display: none;">
                        Cancel
                    </button>
                    <span id="quote-product-preview" style="margin-left: 15px; color: #666; font-size: 13px;"></span>
                </div>
            </div>

            <!-- Products List -->
            <div id="quote-products-list-section" style="display: none; margin-top: 20px;">
                <h4 style="margin-bottom: 10px;">Products Added</h4>
                <table class="wp-list-table widefat fixed" id="quote-products-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 12%;">Size</th>
                            <th style="width: 12%;">Sq.Mtr</th>
                            <th style="width: 14%;">Price/Sq.Mtr</th>
                            <th style="width: 14%;">Total</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="quote-products-tbody">
                        <!-- Products will be inserted here -->
                    </tbody>
                    <tfoot>
                        <tr style="background: #f9f9f9; font-weight: 600;">
                            <td colspan="4" style="text-align: right; padding-right: 10px;">Products Subtotal:</td>
                            <td><?php echo esc_html($currency_symbol); ?><span id="quote-products-subtotal-display">0.00</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Fitting Cost (after products) -->
            <div class="form-field" style="margin-top: 20px;">
                <label for="quote-fitting-cost">Fitting Cost (<?php echo esc_html($currency_symbol); ?>)</label>
                <input type="number" id="quote-fitting-cost" name="fitting_cost" step="0.01" min="0" value="0.00">
                <p class="description">Manual entry - cost for fitting all products</p>
            </div>
        </div>

        <!-- Accessories Section -->
        <div class="form-section">
            <h3>Accessories</h3>
            <div id="quote-accessories-list">
                <?php foreach ($accessories as $accessory): ?>
                    <div class="accessory-item">
                        <label>
                            <input type="checkbox" class="quote-accessory-checkbox"
                                   data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                                   data-accessory-name="<?php echo esc_attr($accessory->accessory_name); ?>"
                                   data-price="<?php echo esc_attr($accessory->price); ?>">
                            <?php echo esc_html($accessory->accessory_name); ?>
                            (<?php echo esc_html($currency_symbol); ?><?php echo number_format($accessory->price, 2); ?>)
                        </label>
                        <input type="number" class="quote-accessory-quantity"
                               data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                               min="1" value="1" step="0.01"
                               style="width: 80px; margin-left: 10px;"
                               disabled>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="calculation-display">
                <strong>Accessories Total:</strong> <?php echo esc_html($currency_symbol); ?><span id="quote-accessories-total-display">0.00</span>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="form-section financial-summary">
            <h3>Financial Summary</h3>
            <table class="calculation-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="amount"><?php echo esc_html($currency_symbol); ?><span id="quote-subtotal-display">0.00</span></td>
                </tr>
                <?php if ($vat_enabled == '1'): ?>
                <tr>
                    <td>VAT (<?php echo $vat_rate; ?>%):</td>
                    <td class="amount"><?php echo esc_html($currency_symbol); ?><span id="quote-vat-display">0.00</span></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td><strong>Total:</strong></td>
                    <td class="amount"><strong><?php echo esc_html($currency_symbol); ?><span id="quote-total-display">0.00</span></strong></td>
                </tr>
            </table>
        </div>

        <!-- Photos Section -->
        <div class="form-section">
            <h3>Photos</h3>
            <p class="description" style="margin-bottom: 10px;">Upload photos of the site, measurements, or product samples.</p>
            <div id="quote-photos-container">
                <p class="description">No photos uploaded yet.</p>
            </div>
            <button type="button" class="button" id="quote-upload-photo-btn">
                <span class="dashicons dashicons-camera"></span> Upload Photo
            </button>
            <input type="file" id="quote-photo-upload-input" accept="image/*" style="display: none;">
        </div>

        <!-- Internal Notes Section -->
        <div class="form-section">
            <h3>Internal Notes</h3>
            <div class="form-field">
                <textarea id="quote-notes" name="notes" rows="4" placeholder="Add any internal notes, customer preferences, or special requirements..."></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="button button-primary button-large" id="save-quote-btn">
            <span class="dashicons dashicons-yes"></span> Save Quote
        </button>
        <button type="button" class="button button-large" id="cancel-quote-btn">Cancel</button>
    </div>
</form>
