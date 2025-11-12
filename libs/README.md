# Libraries Directory

## TCPDF Installation

This plugin requires TCPDF for PDF generation functionality.

### Installation Steps:

1. Download TCPDF from: https://github.com/tecnickcom/TCPDF/releases
2. Extract the TCPDF folder
3. Copy the entire TCPDF directory to this `libs/` folder
4. The structure should be: `wp-staff-diary/libs/tcpdf/tcpdf.php`

### Alternative - Composer Installation:

If you prefer using Composer:

```bash
cd wp-staff-diary/libs
composer require tecnickcom/tcpdf
```

### Verification:

After installation, verify the file exists at:
`wp-content/plugins/wp-staff-diary/libs/tcpdf/tcpdf.php`

The plugin will automatically detect TCPDF and enable PDF generation features.
