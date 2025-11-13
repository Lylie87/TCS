# DEVELOPER.md

Developer documentation and code structure guide for the Staff Daily Job Planner WordPress plugin.

## Project Overview

**Staff Daily Job Planner** is a WordPress plugin that provides a daily job planning and management system for staff members. It allows individual staff to track their daily jobs with detailed information and image uploads, while managers/admins can view an overview of all staff activities.

## Core Features

- Individual job planner per staff member with daily job tracking
- Job details: date, client name, address, phone, description, plans, notes
- Image uploads for each job entry (multiple images per job)
- Staff overview dashboard for managers/admins
- Month-based filtering and viewing
- Status tracking: pending, in-progress, completed

## Installation & Setup

### Installing in WordPress
1. Copy the entire plugin directory to `wp-content/plugins/wp-staff-diary/`
2. Activate the plugin through WordPress admin
3. Database tables are automatically created on activation:
   - `wp_staff_diary_entries` - stores job entries
   - `wp_staff_diary_images` - stores image references

### Development Environment
To develop this plugin, you need:
- WordPress installation (local or remote)
- PHP 7.4+
- MySQL 5.6+
- Write access to wp-content/plugins directory

## Architecture Overview

### Plugin Structure
```
wp-staff-diary/
├── wp-staff-diary.php          # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── includes/                   # Core functionality
│   ├── class-wp-staff-diary.php    # Main plugin class
│   ├── class-loader.php            # Hook loader
│   ├── class-activator.php         # Activation logic
│   ├── class-deactivator.php       # Deactivation logic
│   └── class-database.php          # Database operations
├── admin/                      # Admin interface
│   ├── class-admin.php             # Admin functionality
│   └── views/
│       ├── my-diary.php            # Staff member's diary view
│       └── staff-overview.php      # Manager overview view
├── public/                     # Public-facing (currently minimal)
│   └── class-public.php
└── assets/                     # CSS & JavaScript
    ├── css/admin.css
    └── js/admin.js
```

### Class Hierarchy & Responsibilities

**WP_Staff_Diary** (includes/class-wp-staff-diary.php)
- Main orchestrator class
- Loads all dependencies
- Registers admin and public hooks
- Initializes the loader

**WP_Staff_Diary_Loader** (includes/class-loader.php)
- Registers all WordPress actions and filters
- Decouples hook registration from class implementation

**WP_Staff_Diary_Database** (includes/class-database.php)
- All database operations (CRUD)
- Methods for entries: get_user_entries(), get_all_entries(), create_entry(), update_entry(), delete_entry()
- Methods for images: add_image(), get_entry_images(), delete_image()
- Always use prepared statements for security

**WP_Staff_Diary_Admin** (admin/class-admin.php)
- Handles admin menu pages
- Enqueues admin assets (CSS/JS)
- AJAX request handlers
- Manages nonce verification

### Database Schema

**Table: wp_staff_diary_entries**
```sql
id              - Primary key
user_id         - WordPress user ID (staff member)
job_date        - Date of the job
client_name     - Client name
client_address  - Client address (text)
client_phone    - Client phone number
job_description - Description of work
plans           - Plans/specifications
notes           - Additional notes
status          - pending|in-progress|completed
created_at      - Auto timestamp
updated_at      - Auto timestamp on update
```

**Table: wp_staff_diary_images**
```sql
id              - Primary key
diary_entry_id  - Foreign key to diary entries
image_url       - Full URL to uploaded image
attachment_id   - WordPress attachment ID
image_caption   - Optional caption
uploaded_at     - Auto timestamp
```

## Development Commands

### Testing the Plugin
Since this is a WordPress plugin, testing is done within WordPress:

1. **Enable WordPress Debug Mode** - Add to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. **Check Debug Log**: `wp-content/debug.log`

3. **Test Database Queries**: Use Query Monitor plugin for WordPress

### Making Changes

**When modifying database schema:**
- Update `includes/class-activator.php`
- Increment version in `wp-staff-diary.php`
- Deactivate and reactivate plugin (or use database migration)

**When adding new AJAX endpoints:**
1. Add method to `admin/class-admin.php`
2. Register in `includes/class-wp-staff-diary.php` define_admin_hooks()
3. Update JavaScript in `assets/js/admin.js`

**When adding admin pages:**
1. Add menu item in `WP_Staff_Diary_Admin::add_plugin_admin_menu()`
2. Create view file in `admin/views/`
3. Add display method in `admin/class-admin.php`

## AJAX API Reference

All AJAX requests require nonce verification using `wp_staff_diary_nonce`.

### save_diary_entry
**Action:** `wp_ajax_save_diary_entry`
**Parameters:**
- entry_id (optional, for updates)
- job_date, client_name, client_address, client_phone
- job_description, plans, notes, status

**Returns:** JSON with entry_id and message

### delete_diary_entry
**Action:** `wp_ajax_delete_diary_entry`
**Parameters:** entry_id
**Returns:** JSON success/error message

### upload_job_image
**Action:** `wp_ajax_upload_job_image`
**Parameters:** entry_id, image (file)
**Returns:** JSON with url and attachment_id

### get_diary_entry
**Action:** `wp_ajax_get_diary_entry`
**Parameters:** entry_id
**Returns:** JSON with full entry object including images array

## Security Considerations

- All AJAX requests verify nonces via `check_ajax_referer()`
- User permissions: 'read' for own diary, 'edit_users' for overview
- Entry ownership verified before delete/update operations
- All inputs sanitized: `sanitize_text_field()`, `sanitize_textarea_field()`
- Database queries use `$wpdb->prepare()` for SQL injection prevention
- File uploads handled by WordPress `wp_handle_upload()` with proper validation

## Important Patterns & Conventions

### Adding a New Field to Diary Entries
1. Update database schema in `includes/class-activator.php`
2. Add field to form in `admin/views/my-diary.php`
3. Update save handler in `admin/class-admin.php::save_diary_entry()`
4. Add to display in overview view if needed

### Permissions Model
- **Staff members** (capability: 'read'): Can view/edit their own job planner
- **Managers/Admins** (capability: 'edit_users'): Can view all staff job planners
- **Admins** (capability: 'delete_users'): Can delete any entry

### Image Handling
Images are stored as WordPress attachments and referenced in the custom images table. This allows:
- Using WordPress media library features
- Proper file management and deletion
- Multiple images per diary entry
- Easy retrieval via attachment IDs

## Common Tasks

### Add a new status option
1. Update status dropdown in `admin/views/my-diary.php` (line ~145)
2. Add corresponding CSS class in `assets/css/admin.css` (around .status-badge section)

### Change date format
- Default: Y-m-d (stored in database)
- Display format: d/m/Y (in view files)
- Month selector: HTML5 month input type

### Extend image functionality
- Image methods are in `WP_Staff_Diary_Database` class
- Upload handler in `WP_Staff_Diary_Admin::upload_job_image()`
- Frontend display in modal and overview views

## Troubleshooting

**Database tables not created:**
- Check activation hook is firing
- Verify dbDelta is working (requires specific SQL formatting)
- Check database user permissions

**AJAX not working:**
- Verify nonce in browser console
- Check wpStaffDiary is localized in JavaScript
- Confirm AJAX actions are registered in define_admin_hooks()

**Images not uploading:**
- Check WordPress upload directory permissions
- Verify wp-content/uploads is writable
- Check PHP upload_max_filesize and post_max_size settings

**Styling issues:**
- Ensure admin.css is enqueued on correct admin pages
- Check for CSS conflicts with WordPress admin styles
- Use browser developer tools to inspect CSS specificity

## Plugin Activation Flow

1. User activates plugin in WordPress admin
2. `activate_wp_staff_diary()` fires
3. `WP_Staff_Diary_Activator::activate()` runs
4. Database tables created via dbDelta()
5. Default options set
6. Plugin ready for use

## Future Enhancement Ideas

- Export job entries to PDF or CSV
- Email notifications for job assignments
- Calendar view of jobs
- Mobile-responsive improvements
- Job templates for recurring work types
- Comments/collaboration on job entries
- Integration with WordPress users/roles for better team management
