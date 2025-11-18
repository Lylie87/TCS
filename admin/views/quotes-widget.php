<style>
    .quotes-widget-content {
        margin: 0 -12px;
    }

    .quote-widget-item {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }

    .quote-widget-item:hover {
        background-color: #f9f9f9;
    }

    .quote-widget-item:last-child {
        border-bottom: none;
    }

    .quote-widget-info {
        flex: 1;
    }

    .quote-widget-number {
        font-weight: 600;
        color: #2271b1;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .quote-widget-customer {
        color: #50575e;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .quote-widget-date {
        color: #787c82;
        font-size: 12px;
    }

    .quote-widget-age {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 3px;
        display: inline-block;
        margin-top: 4px;
    }

    .quote-age-green {
        background-color: #d4edda;
        color: #155724;
    }

    .quote-age-orange {
        background-color: #fff3cd;
        color: #856404;
    }

    .quote-age-red {
        background-color: #f8d7da;
        color: #721c24;
    }

    .quote-widget-product {
        color: #50575e;
        font-size: 12px;
        margin-top: 4px;
        font-style: italic;
    }

    .quote-widget-amount {
        font-weight: 600;
        font-size: 15px;
        color: #2271b1;
        margin-right: 12px;
    }

    .quote-widget-actions {
        display: flex;
        gap: 5px;
    }

    .quote-widget-btn {
        padding: 5px 10px;
        font-size: 12px;
        height: auto;
        line-height: 1.4;
    }

    .quotes-widget-empty {
        padding: 20px;
        text-align: center;
        color: #787c82;
    }

    .quotes-widget-footer {
        padding: 10px 12px;
        border-top: 1px solid #f0f0f0;
        background-color: #f9f9f9;
        text-align: center;
    }

    .quotes-widget-footer a {
        text-decoration: none;
        font-weight: 500;
    }
</style>

<div class="quotes-widget-content">
    <?php if (empty($quotes)): ?>
        <div class="quotes-widget-empty">
            <p>No recent quotes found.</p>
            <p><a href="<?php echo admin_url('admin.php?page=wp-staff-diary-quotes'); ?>" class="button button-primary">Go to Quotes Page</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($quotes as $quote):
            // Calculate quote age
            $created_date = new DateTime($quote->created_at);
            $today = new DateTime();
            $age_days = $today->diff($created_date)->days;

            // Determine age color class
            if ($age_days <= 6) {
                $age_class = 'quote-age-green';
            } elseif ($age_days <= 13) {
                $age_class = 'quote-age-orange';
            } else {
                $age_class = 'quote-age-red';
            }

            $age_text = $age_days == 0 ? 'Today' : ($age_days == 1 ? '1 day' : $age_days . ' days');
        ?>
            <div class="quote-widget-item">
                <div class="quote-widget-info">
                    <div class="quote-widget-number"><?php echo esc_html($quote->order_number); ?></div>
                    <div class="quote-widget-customer">
                        <?php if (!empty($quote->customer)): ?>
                            <?php echo esc_html($quote->customer->customer_name); ?>
                        <?php else: ?>
                            <em>No customer assigned</em>
                        <?php endif; ?>
                    </div>
                    <div class="quote-widget-date">
                        <?php echo date('d/m/Y', strtotime($quote->created_at)); ?>
                        <span class="quote-widget-age <?php echo $age_class; ?>"><?php echo $age_text; ?></span>
                    </div>
                    <?php if (!empty($quote->product_description)): ?>
                        <div class="quote-widget-product">
                            <?php echo esc_html(wp_trim_words($quote->product_description, 8)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="quote-widget-amount">
                    £<?php echo number_format($quote->total, 2); ?>
                </div>

                <div class="quote-widget-actions">
                    <button type="button"
                            class="button button-small quote-widget-btn view-quote-dashboard"
                            data-quote-id="<?php echo $quote->id; ?>">
                        View
                    </button>
                    <button type="button"
                            class="button button-primary button-small quote-widget-btn convert-quote-dashboard"
                            data-quote-id="<?php echo $quote->id; ?>">
                        Convert
                    </button>
                    <button type="button"
                            class="button button-small quote-widget-btn send-discount-dashboard"
                            data-quote-id="<?php echo $quote->id; ?>"
                            title="Send Discount Offer">
                        💰 Discount
                    </button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="quotes-widget-footer">
            <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-quotes'); ?>">View All Quotes →</a>
        </div>
    <?php endif; ?>
</div>

<!-- Discount Modal -->
<div id="dashboard-discount-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 5px;">
        <h3 style="margin-top: 0;">Send Discount Offer</h3>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Discount Type:</label>
            <select id="discount-type" style="width: 100%; padding: 8px;">
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount (£)</option>
            </select>
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Discount Value:</label>
            <input type="number" id="discount-value" min="0" step="0.01" style="width: 100%; padding: 8px;" placeholder="Enter discount amount">
        </div>
        <div style="text-align: right;">
            <button type="button" class="button" id="cancel-discount" style="margin-right: 10px;">Cancel</button>
            <button type="button" class="button button-primary" id="send-discount">Send Discount Email</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentDiscountQuoteId = null;

    // View quote from dashboard - trigger click on hidden view button
    $(document).on('click', '.view-quote-dashboard', function() {
        var quoteId = $(this).data('quote-id');

        // Simulate clicking the view-entry button which will trigger admin.js handler
        $('<button class="view-entry" data-id="' + quoteId + '" style="display:none;"></button>')
            .appendTo('body')
            .trigger('click')
            .remove();
    });

    // Convert quote to job from dashboard - open the convert modal
    $(document).on('click', '.convert-quote-dashboard', function() {
        var quoteId = $(this).data('quote-id');

        // Trigger the convert button which will be handled by quotes.js
        $('<button class="convert-to-job" data-id="' + quoteId + '" style="display:none;"></button>')
            .appendTo('body')
            .trigger('click')
            .remove();
    });

    // Send discount from dashboard
    $(document).on('click', '.send-discount-dashboard', function() {
        currentDiscountQuoteId = $(this).data('quote-id');
        $('#discount-type').val('percentage');
        $('#discount-value').val('');
        $('#dashboard-discount-modal').fadeIn(200);
    });

    // Cancel discount
    $('#cancel-discount').on('click', function() {
        $('#dashboard-discount-modal').fadeOut(200);
        currentDiscountQuoteId = null;
    });

    // Send discount email
    $('#send-discount').on('click', function() {
        var discountType = $('#discount-type').val();
        var discountValue = parseFloat($('#discount-value').val());

        if (!discountValue || discountValue <= 0) {
            alert('Please enter a valid discount value');
            return;
        }

        if (discountType === 'percentage' && discountValue > 100) {
            alert('Percentage discount cannot exceed 100%');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> Sending...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'send_discount_offer',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_nonce'); ?>',
                entry_id: currentDiscountQuoteId,
                discount_type: discountType,
                discount_value: discountValue
            },
            success: function(response) {
                if (response.success) {
                    alert('Discount email sent successfully!');
                    $('#dashboard-discount-modal').fadeOut(200);
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to send discount email'));
                }
                $btn.prop('disabled', false).html('Send Discount Email');
            },
            error: function() {
                alert('An error occurred while sending the discount email');
                $btn.prop('disabled', false).html('Send Discount Email');
            }
        });
    });

    // Close modal on background click
    $('#dashboard-discount-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
            currentDiscountQuoteId = null;
        }
    });
});
</script>
