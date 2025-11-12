<?php
/**
 * PDF Generation Class
 *
 * Generates job sheets and invoices using TCPDF
 *
 * @since      2.0.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_PDF_Generator {

    private $db;
    private $tcpdf_available;

    public function __construct() {
        $this->db = new WP_Staff_Diary_Database();
        $this->tcpdf_available = $this->check_tcpdf();
    }

    /**
     * Check if TCPDF is available
     */
    private function check_tcpdf() {
        $tcpdf_path = WP_STAFF_DIARY_PATH . 'libs/tcpdf/tcpdf.php';
        return file_exists($tcpdf_path);
    }

    /**
     * Check if PDF generation is available
     */
    public function is_available() {
        return $this->tcpdf_available;
    }

    /**
     * Generate job sheet PDF
     */
    public function generate_job_sheet($entry_id, $output_mode = 'I') {
        if (!$this->tcpdf_available) {
            return array(
                'success' => false,
                'message' => 'TCPDF library not installed. Please see libs/README.md for installation instructions.'
            );
        }

        // Load TCPDF
        require_once(WP_STAFF_DIARY_PATH . 'libs/tcpdf/tcpdf.php');

        // Get entry data
        $entry = $this->db->get_entry($entry_id);
        if (!$entry) {
            return array('success' => false, 'message' => 'Job not found');
        }

        // Get related data
        $customer = $entry->customer_id ? $this->db->get_customer($entry->customer_id) : null;
        $accessories = $this->db->get_job_accessories($entry_id);
        $payments = $this->db->get_entry_payments($entry_id);
        $images = $this->db->get_entry_images($entry_id);

        // Calculate totals
        $subtotal = $this->db->calculate_job_subtotal($entry_id);
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        $vat_amount = 0;
        $total = $subtotal;
        if ($vat_enabled == '1') {
            $vat_amount = $subtotal * ($vat_rate / 100);
            $total = $subtotal + $vat_amount;
        }

        $total_payments = $this->db->get_entry_total_payments($entry_id);
        $balance = $total - $total_payments;

        // Get company details
        $company_name = get_option('wp_staff_diary_company_name', '');
        $company_address = get_option('wp_staff_diary_company_address', '');
        $company_phone = get_option('wp_staff_diary_company_phone', '');
        $company_email = get_option('wp_staff_diary_company_email', '');
        $company_vat = get_option('wp_staff_diary_company_vat_number', '');
        $company_reg = get_option('wp_staff_diary_company_reg_number', '');
        $company_bank = get_option('wp_staff_diary_company_bank_details', '');
        $company_logo = get_option('wp_staff_diary_company_logo', '');
        $terms = get_option('wp_staff_diary_terms_conditions', '');

        // Create PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Staff Daily Job Planner');
        $pdf->SetAuthor($company_name);
        $pdf->SetTitle('Job Sheet - ' . $entry->order_number);
        $pdf->SetSubject('Job Sheet');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Build HTML content
        $html = $this->build_pdf_html($entry, $customer, $accessories, $payments, $images,
                                      $subtotal, $vat_amount, $total, $total_payments, $balance,
                                      $company_name, $company_address, $company_phone, $company_email,
                                      $company_vat, $company_reg, $company_bank, $company_logo, $terms,
                                      $vat_enabled, $vat_rate);

        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF
        $filename = 'job-sheet-' . $entry->order_number . '.pdf';

        if ($output_mode === 'S') {
            // Return as string
            return array(
                'success' => true,
                'content' => $pdf->Output($filename, 'S')
            );
        } elseif ($output_mode === 'F') {
            // Save to file
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/staff-diary-pdfs/';
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }
            $filepath = $pdf_dir . $filename;
            $pdf->Output($filepath, 'F');
            return array(
                'success' => true,
                'filepath' => $filepath,
                'url' => $upload_dir['baseurl'] . '/staff-diary-pdfs/' . $filename
            );
        } else {
            // Output to browser (I = inline, D = download)
            $pdf->Output($filename, $output_mode);
            exit;
        }
    }

    /**
     * Build PDF HTML content
     */
    private function build_pdf_html($entry, $customer, $accessories, $payments, $images,
                                   $subtotal, $vat_amount, $total, $total_payments, $balance,
                                   $company_name, $company_address, $company_phone, $company_email,
                                   $company_vat, $company_reg, $company_bank, $company_logo, $terms,
                                   $vat_enabled, $vat_rate) {

        $html = '<style>
            h1 { font-size: 24px; color: #2271b1; margin-bottom: 5px; }
            h2 { font-size: 16px; color: #333; margin-top: 10px; margin-bottom: 5px; border-bottom: 2px solid #2271b1; padding-bottom: 3px; }
            h3 { font-size: 14px; color: #555; margin-top: 8px; margin-bottom: 5px; }
            .company-header { margin-bottom: 20px; }
            .section { margin-bottom: 15px; }
            .info-table { width: 100%; border-collapse: collapse; }
            .info-table td { padding: 5px; font-size: 10px; }
            .info-table strong { color: #333; }
            .financial-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .financial-table th, .financial-table td { padding: 6px; border: 1px solid #ddd; font-size: 10px; }
            .financial-table th { background-color: #f0f0f0; font-weight: bold; text-align: left; }
            .financial-table td.amount { text-align: right; }
            .total-row { background-color: #f9f9f9; font-weight: bold; }
            .balance-due { background-color: #fff3cd; font-weight: bold; font-size: 12px; }
            .terms { font-size: 9px; color: #666; margin-top: 15px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; }
            .payment-row { background-color: #e8f5e9; }
            .order-number { font-size: 20px; color: #2271b1; font-weight: bold; }
        </style>';

        // Company Header
        $html .= '<div class="company-header">';
        if ($company_logo && file_exists(get_attached_file($company_logo))) {
            $html .= '<img src="' . get_attached_file($company_logo) . '" height="60" /><br>';
        }
        $html .= '<h1>' . htmlspecialchars($company_name) . '</h1>';
        if ($company_address) {
            $html .= '<div style="font-size: 10px;">' . nl2br(htmlspecialchars($company_address)) . '</div>';
        }
        if ($company_phone || $company_email) {
            $html .= '<div style="font-size: 10px; margin-top: 3px;">';
            if ($company_phone) $html .= 'Tel: ' . htmlspecialchars($company_phone) . ' ';
            if ($company_email) $html .= 'Email: ' . htmlspecialchars($company_email);
            $html .= '</div>';
        }
        if ($company_vat || $company_reg) {
            $html .= '<div style="font-size: 9px; margin-top: 3px;">';
            if ($company_vat) $html .= 'VAT: ' . htmlspecialchars($company_vat) . ' ';
            if ($company_reg) $html .= 'Reg: ' . htmlspecialchars($company_reg);
            $html .= '</div>';
        }
        $html .= '</div>';

        // Job Sheet Title
        $html .= '<h1>JOB SHEET</h1>';
        $html .= '<div class="order-number">Order #' . htmlspecialchars($entry->order_number) . '</div>';
        $html .= '<div style="font-size: 10px; margin-bottom: 15px;">Date: ' . date('d/m/Y', strtotime($entry->job_date)) . '</div>';

        // Customer Information
        if ($customer) {
            $html .= '<h2>Customer Details</h2>';
            $html .= '<table class="info-table">';
            $html .= '<tr><td width="20%"><strong>Name:</strong></td><td>' . htmlspecialchars($customer->customer_name) . '</td></tr>';

            // Build UK address from parts
            $address_parts = array_filter(array(
                isset($customer->address_line_1) ? $customer->address_line_1 : '',
                isset($customer->address_line_2) ? $customer->address_line_2 : '',
                isset($customer->address_line_3) ? $customer->address_line_3 : '',
                isset($customer->postcode) ? $customer->postcode : ''
            ));
            if (!empty($address_parts)) {
                $html .= '<tr><td><strong>Address:</strong></td><td>' . nl2br(htmlspecialchars(implode("\n", $address_parts))) . '</td></tr>';
            }

            if ($customer->customer_phone) {
                $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($customer->customer_phone) . '</td></tr>';
            }
            if ($customer->customer_email) {
                $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($customer->customer_email) . '</td></tr>';
            }
            $html .= '</table>';
        }

        // Job Details
        $html .= '<h2>Job Details</h2>';
        $html .= '<table class="info-table">';
        if ($entry->job_date) {
            $html .= '<tr><td width="25%"><strong>Job Date:</strong></td><td>' . date('d/m/Y', strtotime($entry->job_date));
            if ($entry->job_time) $html .= ' at ' . date('H:i', strtotime($entry->job_time));
            $html .= '</td></tr>';
        }
        if ($entry->fitting_date) {
            $html .= '<tr><td><strong>Fitting Date:</strong></td><td>' . date('d/m/Y', strtotime($entry->fitting_date));
            if ($entry->fitting_time_period) $html .= ' (' . $entry->fitting_time_period . ')';
            $html .= '</td></tr>';
        }
        if ($entry->area) {
            $html .= '<tr><td><strong>Area:</strong></td><td>' . htmlspecialchars($entry->area) . '</td></tr>';
        }
        if ($entry->size) {
            $html .= '<tr><td><strong>Size:</strong></td><td>' . htmlspecialchars($entry->size) . '</td></tr>';
        }
        if ($entry->status) {
            $html .= '<tr><td><strong>Status:</strong></td><td>' . htmlspecialchars(ucfirst($entry->status)) . '</td></tr>';
        }
        $html .= '</table>';

        // Product Details
        $html .= '<h2>Product & Services</h2>';
        $html .= '<table class="financial-table">';
        $html .= '<thead><tr><th width="50%">Description</th><th width="15%">Qty</th><th width="18%">Price</th><th width="17%">Total</th></tr></thead>';
        $html .= '<tbody>';

        // Main product
        if ($entry->product_description) {
            $product_total = $entry->sq_mtr_qty && $entry->price_per_sq_mtr ?
                             ($entry->sq_mtr_qty * $entry->price_per_sq_mtr) : 0;
            $html .= '<tr>';
            $html .= '<td>' . nl2br(htmlspecialchars($entry->product_description)) . '</td>';
            $html .= '<td>' . ($entry->sq_mtr_qty ? number_format($entry->sq_mtr_qty, 2) : '-') . '</td>';
            $html .= '<td class="amount">£' . ($entry->price_per_sq_mtr ? number_format($entry->price_per_sq_mtr, 2) : '-') . '</td>';
            $html .= '<td class="amount">£' . number_format($product_total, 2) . '</td>';
            $html .= '</tr>';
        }

        // Accessories
        if (!empty($accessories)) {
            foreach ($accessories as $acc) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($acc->accessory_name) . '</td>';
                $html .= '<td>' . number_format($acc->quantity, 2) . '</td>';
                $html .= '<td class="amount">£' . number_format($acc->price_per_unit, 2) . '</td>';
                $html .= '<td class="amount">£' . number_format($acc->total_price, 2) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        // Financial Summary
        $html .= '<h2>Financial Summary</h2>';
        $html .= '<table class="financial-table">';
        $html .= '<tr><td width="70%"><strong>Subtotal:</strong></td><td width="30%" class="amount">£' . number_format($subtotal, 2) . '</td></tr>';
        if ($vat_enabled == '1') {
            $html .= '<tr><td><strong>VAT (' . $vat_rate . '%):</strong></td><td class="amount">£' . number_format($vat_amount, 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td><strong>Total:</strong></td><td class="amount"><strong>£' . number_format($total, 2) . '</strong></td></tr>';

        // Payments
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                $html .= '<tr class="payment-row"><td>Payment (' . htmlspecialchars($payment->payment_method) . ') - ' . date('d/m/Y', strtotime($payment->recorded_at)) . '</td><td class="amount">-£' . number_format($payment->amount, 2) . '</td></tr>';
            }
        }

        // Balance
        $html .= '<tr class="balance-due"><td><strong>Balance Due:</strong></td><td class="amount"><strong>£' . number_format($balance, 2) . '</strong></td></tr>';
        $html .= '</table>';

        // Additional Notes
        if ($entry->notes) {
            $html .= '<h2>Additional Notes</h2>';
            $html .= '<div style="font-size: 10px;">' . nl2br(htmlspecialchars($entry->notes)) . '</div>';
        }

        // Bank Details
        if ($company_bank) {
            $html .= '<h3>Payment Details</h3>';
            $html .= '<div style="font-size: 10px;">' . nl2br(htmlspecialchars($company_bank)) . '</div>';
        }

        // Terms and Conditions
        if ($terms) {
            $html .= '<div class="terms">';
            $html .= '<strong>Terms & Conditions:</strong><br>';
            $html .= strip_tags($terms, '<p><br><strong><em><ul><ol><li>');
            $html .= '</div>';
        }

        return $html;
    }
}
