<?php
/**
 * Currency Helper - Formatting and calculations
 *
 * @since      2.3.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Currency_Helper {

    /**
     * Format amount with currency symbol
     *
     * @param float $amount The amount to format
     * @param bool $include_symbol Whether to include currency symbol
     * @return string Formatted amount
     */
    public static function format($amount, $include_symbol = true) {
        $symbol = get_option('wp_staff_diary_currency_symbol', '£');
        $code = get_option('wp_staff_diary_currency_code', 'GBP');
        $position = get_option('wp_staff_diary_currency_position', 'left');
        $decimal_sep = get_option('wp_staff_diary_decimal_separator', '.');
        $thousand_sep = get_option('wp_staff_diary_thousands_separator', ',');

        // Format the number
        $formatted_amount = number_format((float)$amount, 2, $decimal_sep, $thousand_sep);

        if (!$include_symbol) {
            return $formatted_amount;
        }

        // Apply currency symbol based on position
        switch ($position) {
            case 'left':
                return $symbol . $formatted_amount;
            case 'right':
                return $formatted_amount . $symbol;
            case 'left_space':
                return $symbol . ' ' . $formatted_amount;
            case 'right_space':
                return $formatted_amount . ' ' . $symbol;
            default:
                return $symbol . $formatted_amount;
        }
    }

    /**
     * Get currency symbol
     *
     * @return string Currency symbol
     */
    public static function get_symbol() {
        return get_option('wp_staff_diary_currency_symbol', '£');
    }

    /**
     * Get currency code
     *
     * @return string Currency code (GBP, USD, etc.)
     */
    public static function get_code() {
        return get_option('wp_staff_diary_currency_code', 'GBP');
    }

    /**
     * Calculate discount amount
     *
     * @param float $original_amount Original amount before discount
     * @param string $discount_type 'percentage' or 'fixed'
     * @param float $discount_value Discount value
     * @return float Discount amount
     */
    public static function calculate_discount($original_amount, $discount_type, $discount_value) {
        if ($discount_type === 'percentage') {
            return ($original_amount * $discount_value) / 100;
        } else {
            return min($discount_value, $original_amount); // Don't discount more than the total
        }
    }

    /**
     * Calculate final amount after discount
     *
     * @param float $original_amount Original amount before discount
     * @param string $discount_type 'percentage' or 'fixed'
     * @param float $discount_value Discount value
     * @return float Final amount after discount
     */
    public static function calculate_final_amount($original_amount, $discount_type, $discount_value) {
        $discount_amount = self::calculate_discount($original_amount, $discount_type, $discount_value);
        return max(0, $original_amount - $discount_amount);
    }

    /**
     * Format discount display
     *
     * @param string $discount_type 'percentage' or 'fixed'
     * @param float $discount_value Discount value
     * @return string Formatted discount (e.g., "5%" or "£50.00")
     */
    public static function format_discount($discount_type, $discount_value) {
        if ($discount_type === 'percentage') {
            return number_format($discount_value, 2) . '%';
        } else {
            return self::format($discount_value);
        }
    }

    /**
     * Get discount type label
     *
     * @param string $discount_type 'percentage' or 'fixed'
     * @return string Human-readable label
     */
    public static function get_discount_type_label($discount_type) {
        return $discount_type === 'percentage' ? 'percentage' : 'fixed price';
    }
}
