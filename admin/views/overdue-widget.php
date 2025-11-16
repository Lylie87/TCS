<?php
/**
 * Overdue Payments Dashboard Widget
 *
 * @since      2.5.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
    .overdue-widget-content {
        margin: 0 -12px;
    }
    .overdue-widget-item {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    .overdue-widget-item:hover {
        background-color: #fff8e5;
    }
    .overdue-widget-item:last-child {
        border-bottom: none;
    }
    .overdue-widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .overdue-widget-job-number {
        font-weight: 600;
        color: #2271b1;
        font-size: 14px;
    }
    .overdue-widget-amount {
        font-weight: 700;
        color: #d63638;
        font-size: 16px;
    }
    .overdue-widget-details {
        font-size: 13px;
        color: #646970;
        line-height: 1.6;
    }
    .overdue-widget-customer {
        font-weight: 500;
        color: #1d2327;
    }
    .overdue-widget-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #d63638;
        color: white;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }
    .overdue-widget-empty {
        padding: 30px 12px;
        text-align: center;
        color: #50575e;
    }
    .overdue-widget-empty-icon {
        font-size: 48px;
        color: #00a32a;
        margin-bottom: 10px;
    }
    .overdue-widget-footer {
        padding: 12px;
        text-align: center;
        border-top: 1px solid #f0f0f0;
        background: #f6f7f7;
    }
    .overdue-widget-footer a {
        text-decoration: none;
        color: #2271b1;
        font-weight: 500;
    }
    .overdue-widget-summary {
        padding: 12px;
        background: #fff3cd;
        border-left: 4px solid #f59e0b;
        margin: 0 -12px 12px -12px;
        font-size: 13px;
    }
    .overdue-widget-summary strong {
        color: #d63638;
        font-size: 16px;
    }
</style>

<div class="overdue-widget-content">
    <?php if (empty($overdue_jobs)): ?>
        <div class="overdue-widget-empty">
            <div class="overdue-widget-empty-icon">✓</div>
            <p><strong>All caught up!</strong></p>
            <p>No overdue payments at this time.</p>
        </div>
    <?php else: ?>
        <?php
        // Calculate total overdue amount
        $total_overdue = 0;
        foreach ($overdue_jobs as $item) {
            $total_overdue += $item['balance'];
        }
        ?>

        <div class="overdue-widget-summary">
            <strong><?php echo count($overdue_jobs); ?></strong> job<?php echo count($overdue_jobs) !== 1 ? 's' : ''; ?> overdue
            | Total outstanding: <strong>£<?php echo number_format($total_overdue, 2); ?></strong>
        </div>

        <?php
        // Show maximum 10 overdue jobs
        $display_jobs = array_slice($overdue_jobs, 0, 10);
        foreach ($display_jobs as $item):
            $job = $item['job'];
            $customer = $item['customer'];
        ?>
            <div class="overdue-widget-item">
                <div class="overdue-widget-header">
                    <div class="overdue-widget-job-number">
                        <?php echo esc_html($job->order_number); ?>
                        <span class="overdue-widget-badge"><?php echo $item['days_overdue']; ?> days</span>
                    </div>
                    <div class="overdue-widget-amount">
                        £<?php echo number_format($item['balance'], 2); ?>
                    </div>
                </div>
                <div class="overdue-widget-details">
                    <div class="overdue-widget-customer">
                        <?php if ($customer): ?>
                            <?php echo esc_html($customer->customer_name); ?>
                        <?php else: ?>
                            <em>No customer assigned</em>
                        <?php endif; ?>
                    </div>
                    <div>
                        Job Date: <?php echo date('d/m/Y', strtotime($job->job_date)); ?>
                        <?php if ($job->job_type): ?>
                            | <?php echo ucfirst($job->job_type); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        Paid: £<?php echo number_format($item['payments'], 2); ?>
                        of £<?php echo number_format($item['total'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($overdue_jobs) > 10): ?>
            <div class="overdue-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=wp-staff-diary'); ?>">
                    View all <?php echo count($overdue_jobs); ?> overdue jobs →
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
