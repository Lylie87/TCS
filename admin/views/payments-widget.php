<?php
/**
 * Payments Dashboard Widget
 *
 * @since      2.4.3
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
    .payment-widget-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .payment-stat-box {
        padding: 15px;
        border-radius: 4px;
        text-align: center;
    }
    .payment-stat-box.outstanding {
        background-color: #fff3cd;
        border-left: 4px solid #ff9800;
    }
    .payment-stat-box.received {
        background-color: #d1f2eb;
        border-left: 4px solid #00a32a;
    }
    .payment-stat-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .payment-stat-amount {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .payment-widget-section {
        margin-bottom: 20px;
    }
    .payment-widget-section h4 {
        margin: 0 0 10px 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #ddd;
        font-size: 13px;
        color: #555;
    }
    .payment-widget-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .payment-widget-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .payment-widget-list li:last-child {
        border-bottom: none;
    }
    .payment-job-info {
        flex: 1;
    }
    .payment-job-number {
        font-weight: 600;
        color: #2271b1;
    }
    .payment-amount {
        font-weight: bold;
        white-space: nowrap;
    }
    .payment-amount.outstanding {
        color: #d63638;
    }
    .payment-amount.received {
        color: #00a32a;
    }
    .payment-date {
        font-size: 11px;
        color: #999;
    }
    .payment-widget-empty {
        padding: 20px;
        text-align: center;
        color: #666;
        font-size: 13px;
    }
    .payment-widget-footer {
        padding-top: 10px;
        border-top: 1px solid #ddd;
        text-align: right;
    }
</style>

<div class="payment-widget-content">
    <!-- Summary Stats -->
    <div class="payment-widget-summary">
        <div class="payment-stat-box outstanding">
            <div class="payment-stat-label">Outstanding</div>
            <div class="payment-stat-amount">£<?php echo number_format($total_outstanding, 2); ?></div>
        </div>
        <div class="payment-stat-box received">
            <div class="payment-stat-label">Total Received</div>
            <div class="payment-stat-amount">£<?php echo number_format($total_received, 2); ?></div>
        </div>
    </div>

    <!-- Jobs with Outstanding Balance -->
    <?php if (!empty($jobs_with_balance)): ?>
    <div class="payment-widget-section">
        <h4>Top Outstanding Jobs</h4>
        <ul class="payment-widget-list">
            <?php foreach ($jobs_with_balance as $item): ?>
                <li>
                    <div class="payment-job-info">
                        <span class="payment-job-number"><?php echo esc_html($item['job']->order_number); ?></span>
                        <br>
                        <span class="payment-date"><?php echo date('d/m/Y', strtotime($item['job']->job_date)); ?></span>
                    </div>
                    <span class="payment-amount outstanding">£<?php echo number_format($item['balance'], 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Recent Payments -->
    <?php if (!empty($recent_payments)): ?>
    <div class="payment-widget-section">
        <h4>Recent Payments</h4>
        <ul class="payment-widget-list">
            <?php foreach ($recent_payments as $payment): ?>
                <li>
                    <div class="payment-job-info">
                        <span class="payment-job-number"><?php echo esc_html($payment->order_number); ?></span>
                        <br>
                        <span class="payment-date"><?php echo date('d/m/Y', strtotime($payment->recorded_at)); ?> • <?php echo esc_html($payment->payment_method); ?></span>
                    </div>
                    <span class="payment-amount received">+£<?php echo number_format($payment->amount, 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (empty($jobs_with_balance) && empty($recent_payments)): ?>
    <div class="payment-widget-empty">
        <p>No payment activity yet.</p>
    </div>
    <?php endif; ?>

    <!-- Footer Link -->
    <div class="payment-widget-footer">
        <a href="<?php echo admin_url('admin.php?page=wp-staff-diary'); ?>" class="button button-small">
            View All Jobs
        </a>
    </div>
</div>
