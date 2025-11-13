# Staff Daily Job Planner

A comprehensive WordPress plugin for managing daily job planning, customer information, job tracking, financial management, and detailed reporting. Built for flooring and fitting businesses but adaptable for any service-based industry.

## Version

2.0.25

## Overview

Staff Daily Job Planner is a complete business management solution designed specifically for WordPress. It provides staff members with tools to track their daily jobs, manage customer information, record payments, and generate professional documentation. Managers gain oversight of all operations through comprehensive dashboards and filtering capabilities.

## Core Features

### Job Management
- **Individual Job Planner** - Each staff member has their own job tracking system
- **Calendar View** - Visual calendar interface showing all jobs at a glance
- **List View** - Detailed table view with advanced filtering and search
- **Order Numbering** - Automatic sequential order number generation with customizable prefix
- **Job Status Tracking** - Track jobs through multiple states (Pending, In Progress, Completed, Cancelled)
- **Custom Statuses** - Add your own job statuses beyond the defaults
- **Fitter Assignment** - Assign specific fitters to jobs with color coding

### Customer Management
- **Customer Database** - Centralized customer information storage
- **UK Address Format** - Three-line address format with postcode
- **Customer Search** - Quick search and auto-complete for existing customers
- **Customer History** - View all jobs for any customer
- **Separate Billing/Fitting Addresses** - Support for different billing and installation addresses

### Product & Pricing
- **Product Description** - Detailed product information per job
- **Quantity Tracking** - Square meter / quantity calculations
- **Price per Unit** - Flexible pricing per square meter or unit
- **Fitting Cost** - Separate charge for installation/fitting services
- **Accessories System** - Add multiple accessories with individual pricing
  - U/Lay, S/Edge, Plates, Adhesive, Screed, Plyboard (customizable)
  - Quantity tracking for each accessory
  - Automatic total calculations
- **VAT Calculations** - Configurable VAT rate (default 20%)
- **Automatic Totals** - Real-time calculation of subtotals, VAT, and grand totals

### Financial Management
- **Payment Recording** - Track all payments received
- **Payment Types** - Categorize payments (Deposit, Partial, Final, Full)
- **Payment Methods** - Multiple payment methods (Cash, Bank Transfer, Card Payment)
- **Payment History** - Complete payment trail for each job
- **Balance Tracking** - Automatic calculation of outstanding balances
- **Financial Summary** - Clear breakdown of charges, payments, and balance due

### Image Management
- **Multiple Images per Job** - Upload unlimited photos per job entry
- **Image Categories** - Organize photos (General, Before, After, etc.)
- **WordPress Media Integration** - Uses WordPress media library
- **Image Gallery** - Grid view of all job photos
- **Full-Screen Viewing** - Click to view images at full resolution

### Documentation & Reporting
- **PDF Job Sheets** - Generate professional PDF documents (requires TCPDF)
- **Download from View Mode** - Direct PDF download from job details
- **Company Branding** - Add company logo, details, and bank information
- **Terms & Conditions** - Include custom T&Cs on documents
- **Professional Layout** - Clean, organized document format

### Dashboard & Overview
- **Staff Overview** - Managers can view all staff activities
- **Filter by Staff** - View jobs for specific team members
- **Filter by Date** - Monthly, date range, and custom filtering
- **Filter by Status** - View only jobs with specific statuses
- **Filter by Fitter** - See jobs assigned to specific fitters
- **Search Functionality** - Quick search across all job fields
- **Dashboard Widget** - "My Jobs This Week" widget on WordPress dashboard

### Settings & Configuration
- **General Settings**
  - Date format customization
  - Time format (24-hour or 12-hour)
  - Week start day (Monday/Sunday)
  - Default job status
  - Job time selection (None, AM/PM, or specific time)

- **Company Details**
  - Company name, address, phone, email
  - VAT number and registration number
  - Bank details for customer payments
  - Company logo upload

- **Order Settings**
  - Order number prefix
  - Starting order number
  - Current order number tracking

- **VAT Configuration**
  - Enable/disable VAT
  - Customizable VAT rate

- **Accessories Management**
  - Add/edit/delete accessories
  - Set pricing for each item
  - Enable/disable accessories
  - Custom display order

- **Fitters Management**
  - Add/edit/delete fitters
  - Assign colors for calendar identification

- **GitHub Auto-Updates** (for private repositories)
  - Configure Personal Access Token
  - Automatic update detection
  - One-click updates from GitHub releases

## Installation

### Basic Installation

1. Download the plugin files
2. Upload to `wp-content/plugins/wp-staff-diary/`
3. Activate through WordPress admin → Plugins
4. Database tables are created automatically
5. Access via "Job Planner" menu in WordPress admin

### PDF Functionality Setup

PDF generation requires the TCPDF library:

1. Download TCPDF from https://github.com/tecnickcom/TCPDF/releases
2. Extract and place in `wp-staff-diary/libs/tcpdf/`
3. Verify `tcpdf.php` exists at `wp-staff-diary/libs/tcpdf/tcpdf.php`
4. PDF download buttons will become active automatically

### GitHub Auto-Updates Setup (Optional)

For automatic updates from a private GitHub repository:

1. Go to WordPress Admin → Job Planner → Settings → GitHub Updates
2. Create a GitHub Personal Access Token:
   - Visit https://github.com/settings/tokens
   - Generate new token (classic)
   - Select scope: `repo` (Full control of private repositories)
3. Paste token in settings and save
4. Plugin will now check for updates automatically

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **TCPDF**: Optional, for PDF generation

## Database Schema

The plugin creates the following custom tables:

- `wp_staff_diary_entries` - Main job entries
- `wp_staff_diary_customers` - Customer database
- `wp_staff_diary_images` - Job photos and images
- `wp_staff_diary_payments` - Payment records
- `wp_staff_diary_accessories` - Master list of accessories
- `wp_staff_diary_job_accessories` - Accessories assigned to jobs

## User Permissions

- **Staff Members** (capability: `read`)
  - View and edit their own jobs
  - Record payments on their jobs
  - Upload photos
  - Generate PDFs

- **Managers/Admins** (capability: `edit_users`)
  - View all staff jobs
  - Access staff overview dashboard
  - Filter and search across all jobs

- **Administrators** (capability: `manage_options`)
  - Full access to all features
  - Manage settings
  - Configure accessories and fitters
  - Manage GitHub integration

## Usage Guide

### Creating a New Job

1. Click "Job Planner" → "My Jobs" (Calendar or List view)
2. Click "Add New Job"
3. Fill in the form:
   - **Job Date**: When the job was received
   - **Customer**: Search for existing or add new
   - **Billing Address**: Customer's billing address
   - **Fitting Address**: Installation address (if different)
   - **Fitting Date**: Scheduled installation date
   - **Fitting Time**: AM/PM or specific time
   - **Area/Size**: Room or area being fitted
   - **Product Description**: What's being installed
   - **Quantity**: Square meters or units
   - **Price per Sq.Mtr**: Unit price
   - **Fitting Cost**: Installation/labor charge
   - **Accessories**: Select applicable accessories
   - **Notes**: Additional information
   - **Status**: Current job status
4. Click "Save Job"

### Recording a Payment

1. View a job (cannot be in Edit mode)
2. Scroll to "Record Payment" section
3. Enter payment details:
   - Amount
   - Payment method (Cash, Bank Transfer, etc.)
   - Payment type (Deposit, Final, etc.)
   - Optional notes
4. Click "Record Payment"
5. Balance updates automatically

### Uploading Photos

1. Edit an existing job
2. In the "Photos" section, click "Upload Photo"
3. Select image file
4. Photo is automatically uploaded and displayed
5. Photos appear in job view and can be downloaded with PDF

### Generating PDF Job Sheets

1. View a job (not in Edit mode)
2. Click "Download PDF" button
3. PDF is generated with:
   - Company details and logo
   - Customer information
   - Product and service details
   - Financial summary
   - Terms and conditions

### Managing Customers

Customers are created automatically when entering jobs, or you can manage them via:
- Job Planner → Customers
- Add, edit, or delete customers
- View customer history

## Shortcodes

Currently, this plugin does not expose any public-facing shortcodes. All functionality is admin-side only.

## Hooks & Filters

Developers can extend the plugin using WordPress hooks:

- `wp_staff_diary_after_save_entry` - After job entry is saved
- `wp_staff_diary_before_delete_entry` - Before job entry is deleted
- `wp_staff_diary_payment_recorded` - After payment is recorded

## Troubleshooting

### Database Tables Not Created

If you see "table doesn't exist" errors:
1. Go to Settings → Plugin Info
2. Click "Run Database Migration"
3. Check if current version matches database version

### PDF Download Shows Error

If "PDF generation not available" appears:
1. Check if TCPDF is installed at `libs/tcpdf/tcpdf.php`
2. See libs/README.md for installation instructions
3. Verify file permissions on libs folder

### GitHub Updates Not Working

If updates don't appear:
1. Go to Settings → GitHub Updates
2. Verify token is configured (should show green checkmark)
3. On Plugins page, click "Clear Cache & Refresh"
4. Check that repository is accessible with the token

### Jobs Not Showing

If no jobs appear after update:
1. Check WordPress debug log (wp-content/debug.log)
2. Run Database Migration from Settings
3. Verify database tables exist in phpMyAdmin
4. Check user permissions (must have 'read' capability minimum)

## Development

### File Structure

```
wp-staff-diary/
├── wp-staff-diary.php           # Main plugin file
├── uninstall.php                # Cleanup on uninstall
├── includes/                    # Core functionality
│   ├── class-wp-staff-diary.php
│   ├── class-loader.php
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-database.php
│   ├── class-upgrade.php
│   ├── class-pdf-generator.php
│   └── class-github-updater.php
├── admin/                       # Admin interface
│   ├── class-admin.php
│   └── views/
│       ├── calendar-view.php
│       ├── my-diary.php
│       ├── staff-overview.php
│       ├── customers.php
│       ├── settings.php
│       └── dashboard-widget.php
├── assets/                      # CSS & JavaScript
│   ├── css/
│   │   ├── admin.css
│   │   └── admin-v2-additions.css
│   └── js/
│       └── admin.js
└── libs/                        # External libraries
    └── tcpdf/                   # PDF generation (not included)
```

### Database Upgrades

Version migrations are handled automatically in `includes/class-upgrade.php`. When updating the database schema:

1. Add new upgrade method (e.g., `upgrade_to_2_0_24()`)
2. Register in `run_upgrades()` method
3. Increment version in `wp-staff-diary.php`
4. Update `includes/class-activator.php` for fresh installs

## Changelog

### Version 2.0.25 (Current)
- Fixed: GitHub auto-update detection for public repositories
- Enhanced: Use /releases endpoint instead of /releases/latest for better reliability
- Enhanced: Flexible ZIP filename matching (handles multiple naming conventions)
- Improved: Better error messages in update diagnostics

### Version 2.0.24
- Enhanced: Financial summary updates immediately after payment recording
- Enhanced: Balance shows "PAID IN FULL" in bright green when £0.00
- Enhanced: Payment rows now stand out with darker green background and border
- Improved: Better visual distinction between paid/unpaid status

### Version 2.0.23
- Fixed: PDF download nonce verification error
- Added: Fitting Cost field for separate labor charges
- Fixed: Fitting cost included in all financial calculations
- Enhanced: Real-time total updates when fitting cost changes

### Version 2.0.22
- Fixed: Removed dangerous table dropping code to prevent data loss
- Enhanced: Database safety improvements

### Version 2.0.21
- Fixed: Duplicate UNIQUE constraint on order_number field
- Fixed: Database table creation issues

### Version 2.0.20
- Enhanced: GitHub auto-update authentication for private repositories
- Added: Personal Access Token configuration in settings
- Added: GitHub Updates settings tab

### Version 2.0.3
- Added: UK address format (three lines + postcode)
- Added: Fitters management and assignment
- Enhanced: Customer address handling

### Version 2.0.0
- Major rewrite with enhanced features
- Added: Customer database
- Added: Accessories system
- Added: Payment tracking
- Added: Order numbering system
- Added: VAT calculations
- Added: PDF generation
- Added: Calendar view

### Version 1.0.0
- Initial release
- Basic job tracking
- Image uploads
- Status management

## Support & Documentation

For additional help:
- Check DEVELOPER.md for detailed code structure and development guide
- Review code comments in source files
- Check libs/README.md for TCPDF installation

## License

GPL-2.0+

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## Credits

Developed for TCS Flooring Solutions
Author: Alex Lyle
Website: https://www.express-websites.co.uk
