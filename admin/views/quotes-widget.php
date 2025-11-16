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
            <p><a href="<?php echo admin_url('admin.php?page=wp-staff-diary-quotes'); ?>" class="button button-primary">Create New Quote</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($quotes as $quote): ?>
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
                    </div>
                </div>

                <div class="quote-widget-amount">
                    £<?php echo number_format($quote->total, 2); ?>
                </div>

                <div class="quote-widget-actions">
                    <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-quotes#quote-' . $quote->id); ?>"
                       class="button button-small quote-widget-btn">View</a>
                    <button type="button"
                            class="button button-primary button-small quote-widget-btn convert-quote-dashboard"
                            data-quote-id="<?php echo $quote->id; ?>">
                        Convert to Job
                    </button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="quotes-widget-footer">
            <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-quotes'); ?>">View All Quotes →</a>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Convert quote to job from dashboard
    $(document).on('click', '.convert-quote-dashboard', function() {
        var quoteId = $(this).data('quote-id');
        var $btn = $(this);
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Converting...');

        // Redirect to quotes page with auto-open convert modal
        window.location.href = '<?php echo admin_url('admin.php?page=wp-staff-diary-quotes'); ?>&convert=' + quoteId;
    });
});
</script>
