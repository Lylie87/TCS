# Staff Daily Job Planner

A WordPress plugin for daily job planning and management with image uploads and staff overview functionality.

## Features

- Individual job planner for each staff member
- Job tracking with detailed information (client name, address, phone, description, plans, notes)
- Multiple image uploads per job entry
- Status tracking (pending, in-progress, completed)
- Staff overview dashboard for managers/admins
- Month-based filtering and viewing

## Installation

1. Copy the plugin folder to `wp-content/plugins/wp-staff-diary/`
2. Activate the plugin through the WordPress admin panel
3. Access via the "Job Planner" menu item in WordPress admin

## Usage

### For Staff Members
- Click "Job Planner" in the WordPress admin menu
- Add new job entries with the "Add New Job" button
- Fill in job details, upload images, and set status
- View and edit past entries by month

### For Managers/Admins
- Access "All Staff Jobs" submenu to see all staff activities
- Filter by staff member or date range
- View detailed job information for any staff member

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Database Tables

The plugin creates two custom tables:
- `wp_staff_diary_entries` - Stores job entries
- `wp_staff_diary_images` - Stores image references

## Permissions

- **Staff members**: Can view and edit their own job entries
- **Managers/Admins**: Can view all staff job planners
- **Admins**: Can delete any entry

## License

GPL-2.0+

## Version

1.0.0
