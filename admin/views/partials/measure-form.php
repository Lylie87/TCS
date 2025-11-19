<?php
/**
 * Measure Entry Form
 * Simple form for scheduling measurements
 *
 * @since      2.8.2
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings for form configuration
$date_format = get_option('wp_staff_diary_date_format', 'Y-m-d');
?>

<!-- Measure Entry Form -->
<form id="measure-entry-form">
    <input type="hidden" id="measure-entry-id" name="entry_id" value="">
    <input type="hidden" id="measure-status" name="status" value="measure">
    <input type="hidden" id="measure-job-date" name="job_date" value="<?php echo date($date_format); ?>">

    <div class="form-sections">
        <!-- Measure Info Section -->
        <div class="form-section">
            <h3>Measure Information</h3>
            <div class="form-field" id="measure-number-display" style="display: none;">
                <label>Measure Number</label>
                <div><strong id="measure-number-value" style="font-size: 18px; color: #2271b1;"></strong></div>
            </div>
            <p class="description">Schedule a measurement appointment. This will appear on your calendar at the specified time.</p>
        </div>

        <!-- Customer Section -->
        <div class="form-section">
            <h3>Customer Details</h3>
            <input type="hidden" id="measure-customer-id" name="customer_id" value="">

            <!-- Customer Search -->
            <div class="form-field" id="measure-customer-search-container">
                <label for="measure-customer-search">Search Customer <span class="required">*</span></label>
                <input type="text" id="measure-customer-search" placeholder="Start typing customer name...">
                <div id="measure-customer-search-results" class="search-results-dropdown"></div>
                <button type="button" class="button" id="measure-add-new-customer-inline" style="margin-top: 10px;">
                    <span class="dashicons dashicons-plus-alt"></span> Add New Customer
                </button>
            </div>

            <!-- Selected Customer Display -->
            <div id="measure-selected-customer-display" class="selected-customer-display" style="display: none;">
                <label>Selected Customer</label>
                <div class="selected-customer-info">
                    <strong id="measure-selected-customer-name"></strong>
                    <button type="button" class="button button-small" id="measure-clear-customer-btn">Change Customer</button>
                </div>
            </div>
        </div>

        <!-- Address Section -->
        <div class="form-section">
            <h3>Measure Address</h3>
            <div class="form-field">
                <label for="measure-address-line-1">Address Line 1 <span class="required">*</span></label>
                <input type="text" id="measure-address-line-1" name="fitting_address_line_1" required>
            </div>
            <div class="form-field">
                <label for="measure-address-line-2">Address Line 2</label>
                <input type="text" id="measure-address-line-2" name="fitting_address_line_2">
            </div>
            <div class="form-field">
                <label for="measure-address-line-3">Address Line 3</label>
                <input type="text" id="measure-address-line-3" name="fitting_address_line_3">
            </div>
            <div class="form-field">
                <label for="measure-postcode">Postcode</label>
                <input type="text" id="measure-postcode" name="fitting_postcode">
            </div>
        </div>

        <!-- Schedule Section -->
        <div class="form-section">
            <h3>Schedule</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label for="measure-date">Measure Date <span class="required">*</span></label>
                    <input type="date" id="measure-date" name="fitting_date" value="<?php echo date($date_format); ?>" required>
                </div>
                <div class="form-field">
                    <label for="measure-time">Measure Time <span class="required">*</span></label>
                    <input type="time" id="measure-time" name="job_time" required>
                    <p class="description">Specific time for the measurement appointment</p>
                </div>
            </div>
        </div>

        <!-- Photos Section -->
        <div class="form-section">
            <h3>Photos</h3>
            <div id="measure-photos-container">
                <p class="description">No photos uploaded yet.</p>
            </div>
            <button type="button" class="button" id="upload-measure-photo-btn">
                <span class="dashicons dashicons-camera"></span> Upload Photo
            </button>
            <input type="file" id="measure-photo-upload-input" accept="image/*" style="display: none;">
        </div>

        <!-- Internal Notes Section -->
        <div class="form-section">
            <h3>Internal Notes</h3>
            <div class="form-field">
                <textarea id="measure-notes" name="notes" rows="4" placeholder="Add any internal notes about the measure..."></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="button button-primary button-large" id="save-measure-btn">
            <span class="dashicons dashicons-yes"></span> Save Measure
        </button>
        <button type="button" class="button button-large cancel-modal">Cancel</button>
    </div>
</form>
