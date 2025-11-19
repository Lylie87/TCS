# Session Restoration Protocol
**Staff Daily Job Planner WordPress Plugin**
**Last Updated:** 2025-11-17
**Current Version:** 2.7.0
**Active Branch:** `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`

---

## Quick Context Restoration

### 1. Project Identity
This is **Staff Daily Job Planner** - a WordPress plugin for service-based business management (specifically flooring/fitting companies). It's a complete digital replacement for paper job sheets, payment tracking, customer management, quotes, and communications.

**Core Purpose:** Give staff tools to track daily jobs and managers oversight of all operations through dashboards with integrated payment tracking, quote generation, SMS/email communications.

**Key Stats:**
- Main plugin file: `wp-staff-diary.php`
- 50+ PHP files in `/includes/`
- 9 custom database tables
- Active modular architecture (since v2.1.0)
- Email template system (v2.7.0)
- SMS integration with Twilio (v2.7.0)

---

## 2. Get Up to Speed Fast

### Essential First Commands
```bash
# Check current branch and status
git status
git log --oneline -10

# View recent changes
git log -3 --stat

# Check version
grep "Version:" wp-staff-diary.php

# Understand project structure
ls -la includes/
```

### Quick File Review (Priority Order)
1. **`DEVELOPMENT_LOG.md`** - CRITICAL! 1,841 lines of detailed implementation docs
2. `wp-staff-diary.php` - Main plugin file (version, initialization, class loading order)
3. `includes/class-wp-staff-diary.php` - Core orchestrator
4. `includes/class-module-registry.php` - Module management
5. `admin/class-admin.php` - Admin functionality
6. `includes/modules/*/` - Modular architecture

### Key Architecture Files
```
includes/
├── interfaces/
│   ├── interface-module.php
│   ├── interface-controller.php
│   └── interface-repository.php
├── shared/
│   ├── abstract-class-base-module.php
│   ├── abstract-class-base-controller.php
│   └── abstract-class-base-repository.php
├── modules/
│   ├── payments/
│   ├── customers/
│   ├── jobs/
│   ├── images/
│   └── notifications/
└── services/
    ├── class-sms-service.php
    └── class-template-service.php
```

---

## 3. Architecture Understanding

### Three-Layer Pattern (Modular)
1. **Module Layer** - Feature coordination, hook registration
2. **Controller Layer** - HTTP/AJAX handling, nonces, validation
3. **Repository Layer** - Pure database operations (CRUD)

### Services Layer (v2.7.0)
- **SMS Service** - Twilio integration with test mode
- **Template Service** - Variable replacement for emails/SMS

### Database Schema (9 Tables)
- `wp_staff_diary_customers` - Customer info with SMS opt-in
- `wp_staff_diary_entries` - Main jobs table (with job_type: residential/commercial)
- `wp_staff_diary_images` - Job photos (with image_category: before/during/after/general)
- `wp_staff_diary_payments` - Payment records (with payment_type tracking)
- `wp_staff_diary_accessories` - Master accessories list
- `wp_staff_diary_job_accessories` - Job-accessory junction
- `wp_staff_diary_notification_logs` - Notification tracking
- `wp_staff_diary_email_templates` - Email template storage (v2.7.0)
- `wp_staff_diary_sms_logs` - SMS delivery tracking (v2.7.0)

---

## 4. Version History & Features

### v2.7.0 (Phase 2) - Current Version
**Major Features:**
- Email template editor with visual builder
- Template variable replacement system ({customer_name}, {job_number}, etc.)
- SMS integration with Twilio
- SMS test mode (no actual sending during development)
- Customer SMS opt-in/opt-out tracking
- Communications settings tab
- Auto-discount scheduler for quotes
- Public quote acceptance pages with secure tokens

**Files Added/Modified:**
- `includes/services/class-sms-service.php` - NEW
- `includes/services/class-template-service.php` - NEW
- `includes/class-email-template-processor.php` - NEW
- `includes/class-quote-acceptance.php` - NEW
- `includes/class-auto-discount-scheduler.php` - NEW
- `admin/views/email-templates.php` - NEW
- Database: Added 2 new tables

**Critical Fix:**
- Fixed fatal error: Database class loading order (must load before Quote Acceptance)

### v2.6.0 (Phase 1)
**Major Features:**
- Before/After Photo Gallery with categorization
- Photo comparison view (side-by-side before/after)
- Payment progress visualization
- Deposit/partial payment tracking with visual progress bars
- Payment breakdown by type (Deposit, Partial, Final, Full)

**Security Fixes:**
- XSS protection via `escapeHtml()` utility
- Escaped all user-controlled photo captions and URLs
- Modal duplicate prevention
- Event handler memory leak fixes

**Files Modified:**
- `includes/modules/images/class-images-repository.php` - Category methods
- `includes/modules/images/class-images-controller.php` - Category validation
- `assets/js/admin.js` - Photo gallery, comparison view, payment progress
- `assets/js/quotes.js` - Category selection for quotes

### v2.5.0
**Features:**
- Job type selection (Residential/Commercial)
- Payment terms settings (days/weeks/months/years)
- Payment policy (which job types require payment first)
- Enhanced bank details (structured fields)
- Overdue payment notifications
- Overdue payments dashboard widget

**Files Modified:**
- `includes/class-activator.php` - Added job_type column, settings
- `admin/views/settings.php` - Payment terms UI, bank details
- `admin/views/partials/job-form.php` - Job type dropdown
- `admin/views/partials/quote-form.php` - Job type dropdown
- `assets/js/admin.js` - Job type handling
- `assets/js/quotes.js` - Job type handling
- `admin/class-admin.php` - Overdue widget, bank details in emails
- `admin/views/overdue-widget.php` - NEW

### Earlier Versions (v2.1.0 - v2.4.9)
- Modular architecture migration
- WooCommerce integration
- Customer management improvements
- Payment reminder system
- Job templates
- Activity log timeline
- Bulk actions system
- Quote generation
- PDF generation

---

## 5. Git Workflow

### Branch Strategy
**Active Branch:** `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`
**Main Branch:** `master`

### Git Protocol
```bash
# First push to new branch requires -u flag
git push -u origin claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM

# Subsequent pushes
git push

# Check branch status
git status
git log --oneline -10
```

### Current Git State
- Branch: `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`
- Latest commit: a026ad4 (Database class loading fix)
- Recent work: v2.7.0 features + critical bug fixes

---

## 6. Critical Class Loading Order

**IMPORTANT:** The order classes are loaded in `wp-staff-diary.php` matters!

```php
// CORRECT ORDER (as of v2.7.0):
1. Core plugin class
2. Database class (MUST be early - other classes depend on it)
3. Currency helper
4. Email template processor
5. Quote acceptance (depends on Database)
6. Auto discount scheduler
7. Upgrade check
8. GitHub updater
9. Main plugin initialization
```

**Fatal Error Fixed:** Quote Acceptance was instantiated before Database class was loaded. Fixed by moving Database class loading earlier in the sequence.

---

## 7. Key Features Reference

### User Roles
- **Staff** (`edit_posts`) - View/edit own jobs
- **Managers** (`edit_users`) - View all staff jobs, filtering
- **Admins** (`manage_options`) - Full settings access

### Main Features

📅 **Job Management**
- Calendar + list views
- Sequential order numbering (customizable prefix)
- Status tracking (Pending, In Progress, Completed, Cancelled)
- Fitter assignment with color coding
- Job types: Residential/Commercial
- Before/During/After photo gallery

👥 **Customer Management**
- UK address format (3 lines + postcode)
- Separate billing/fitting addresses
- Auto-complete search
- Customer history
- SMS opt-in/opt-out tracking

💰 **Financial**
- Payment types: Deposit, Partial, Final, Full
- Payment methods: Cash, Bank Transfer, Card
- VAT calculations (configurable, default 20%)
- Payment progress visualization
- Payment terms (configurable: days/weeks/months/years)
- Overdue payment tracking
- "PAID IN FULL" indicators

📄 **Quotes & Documents**
- Quote generation with line items
- Public quote acceptance pages (secure tokens)
- Automatic discount scheduling
- PDF generation via TCPDF
- Professional branding

💬 **Communications** (v2.7.0)
- Email template editor with visual builder
- Template variables: {customer_name}, {job_number}, {total}, etc.
- SMS integration with Twilio
- SMS test mode for development
- Notification logs

🛒 **Integrations**
- WooCommerce products
- Twilio SMS
- GitHub auto-updater

---

## 8. Common Tasks & Workflows

### Bug Fix Workflow
1. Check debug.log for error details
2. Read relevant files to understand issue
3. Check git history for related changes
4. Make fix with clear comments
5. Commit with descriptive message
6. Test in WordPress
7. Push to active branch

### Adding New Feature
1. Check if fits existing module or needs new one
2. Update database schema in `includes/class-activator.php` if needed
3. Add migration code in `includes/class-upgrade.php`
4. Follow modular pattern if applicable
5. Update DEVELOPMENT_LOG.md with detailed implementation notes
6. Test thoroughly
7. Commit and push

### Code Review Checklist
- [ ] Follows modular architecture pattern (if applicable)
- [ ] Nonce verification on AJAX requests
- [ ] Data sanitization on input
- [ ] Escaping on output (use `escapeHtml()` for user content)
- [ ] Backwards compatibility maintained
- [ ] No breaking changes to database schema
- [ ] Comments for complex logic
- [ ] Updated DEVELOPMENT_LOG.md if significant feature

---

## 9. Critical File Locations

### Configuration
- Main plugin: `wp-staff-diary.php` (version, hooks, class loading)
- Activation: `includes/class-activator.php` (DB creation, settings)
- Upgrades: `includes/class-upgrade.php` (migrations)

### Services (v2.7.0)
- SMS: `includes/services/class-sms-service.php`
- Templates: `includes/services/class-template-service.php`

### Views (Admin UI)
- Calendar: `admin/views/calendar-view.php`
- List: `admin/views/my-diary.php`
- Staff Overview: `admin/views/staff-overview.php`
- Customers: `admin/views/customers.php`
- Quotes: `admin/views/quotes.php`
- Email Templates: `admin/views/email-templates.php`
- Settings: `admin/views/settings.php` (massive - 68K+ lines)

### Assets
- CSS: `assets/css/admin.css`
- JS: `assets/js/admin.js` (1,500+ lines)
- JS: `assets/js/quotes.js`

### External Dependencies
- TCPDF: `libs/tcpdf/` (for PDFs)

---

## 10. Quick Restoration Commands

### For New Session
```bash
# 1. Verify you're on correct branch
git checkout claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM

# 2. Check for any uncommitted changes
git status

# 3. Pull latest from remote
git pull origin claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM

# 4. Review recent work
git log --oneline -10
git diff HEAD~1

# 5. Check version
grep "Version:" wp-staff-diary.php
```

### Essential Reads for Full Context
```bash
# 1. READ THIS FIRST - Most important!
cat DEVELOPMENT_LOG.md

# 2. Read this restoration protocol
cat .claude/RESTORATION_PROTOCOL.md

# 3. Check version and initialization
head -100 wp-staff-diary.php

# 4. View database schema
grep -A 20 "CREATE TABLE" includes/class-activator.php

# 5. Check latest work
git log -p -1
```

---

## 11. Known Issues & Gotchas

### Recent Fixes (v2.7.0)
✅ **Fixed:** Fatal error with Database class loading order
✅ **Fixed:** XSS vulnerabilities in photo captions
✅ **Fixed:** Modal duplicate prevention
✅ **Fixed:** Event handler memory leaks
✅ **Fixed:** Payment progress calculation edge cases

### Current State
- All v2.7.0 features deployed and active
- Plugin activates without errors
- SMS is in test mode (won't send real messages until configured)
- Email templates functional

### Architecture Notes
- Class loading order is CRITICAL (see section 6)
- Database class must load early
- Some legacy code remains in `admin/class-admin.php`
- Backwards compatibility is CRITICAL - don't break existing installations

---

## 12. Testing Considerations

### Manual Testing Required
- AJAX operations (quote acceptance, photo uploads)
- Role-based access (Staff vs Manager vs Admin)
- Payment calculations (VAT, accessories, totals)
- Email template variable replacement
- SMS opt-in/opt-out functionality
- Photo category filtering and comparison view
- Payment progress visualization

### WordPress Environment
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Twilio account (for SMS - optional)

---

## 13. Communication Style

### Project Owner: Alex Lyle (TCS Flooring Solutions)
- Real business use case (flooring company)
- Values data integrity and stability
- Active development (daily commits)
- Production environment - changes affect real business

### Agent Identity
**Previous Agents:**
- Dex (Session 01J9bsuNvaZyRMXidTfRhpGe)
- Chip (Session 01NamkLhSA2hUnsVz57p5SaM)

**For New Session:** Feel free to pick your own name!

### Tone Preferences
- Professional and technical
- Direct and objective (no excessive praise)
- Focus on facts and problem-solving
- Concise communication
- Clear explanations for complex concepts

---

## 14. Template for New Session

**Paste This When Starting New Session:**

```
I'm continuing work on the Staff Daily Job Planner WordPress plugin.

Quick context:
- Business management system for service companies
- WordPress plugin (PHP 7.4+, MySQL)
- Branch: claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM
- Version: 2.7.0 (SMS, email templates, photo gallery, payment tracking)
- Production environment - real business use

CRITICAL: Please read DEVELOPMENT_LOG.md first (1,841 lines)
Then review this protocol: .claude/RESTORATION_PROTOCOL.md

Recent work:
git log --oneline -10

I need help with: [STATE YOUR TASK HERE]
```

---

## 15. Emergency Recovery

### If Git State Is Unclear
```bash
# See all branches
git branch -a

# See remote configuration
git remote -v

# See what's staged
git status -v

# See unpushed commits
git log origin/claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM..HEAD
```

### If Code State Is Unclear
```bash
# Find recently modified files
find . -type f -name "*.php" -mtime -1

# Check for syntax errors
find . -name "*.php" -exec php -l {} \;

# Search for TODO comments
grep -r "TODO\|FIXME\|XXX" --include="*.php" includes/ admin/
```

### If Database State Is Unclear
- Check `includes/class-activator.php` for table schemas
- Review `includes/class-upgrade.php` for migration history
- Check DEVELOPMENT_LOG.md for recent schema changes

### If Feature State Is Unclear
- Read DEVELOPMENT_LOG.md (MOST IMPORTANT)
- Check git log for recent commits
- Review settings page in WordPress admin

---

## 16. Development Patterns

### Security
- Always use nonce verification: `check_ajax_referer()`
- Sanitize input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Escape output: `esc_html()`, `esc_attr()`, or custom `escapeHtml()`
- Prepared statements: `$wpdb->prepare()`

### JavaScript
- Use namespaced events for cleanup: `keydown.modalName`
- Prevent duplicate modals: `if ($('#modal-id').length > 0) return;`
- Always cleanup event handlers to prevent memory leaks
- Use callbacks for async operations

### PHP
- Follow WordPress coding standards
- Use type hints where appropriate
- Document complex logic with comments
- Maintain backwards compatibility

---

## 17. Success Metrics

### How to Know You're On Track
✅ All commits pushed to correct branch
✅ No PHP syntax errors
✅ Plugin activates without fatal errors
✅ Follows established patterns
✅ Backwards compatible
✅ Clear commit messages
✅ DEVELOPMENT_LOG.md updated for significant changes
✅ Security best practices followed

---

## 18. Quick Reference Commands

```bash
# Project structure overview
tree -L 3 -I 'node_modules|vendor|.git|libs'

# Find all modules
ls -la includes/modules/

# Check plugin version
grep "Version:" wp-staff-diary.php

# See all database tables
grep "CREATE TABLE" includes/class-activator.php | grep -v "^--"

# Find all AJAX handlers
grep -r "wp_ajax_" includes/ admin/ | grep "add_action"

# Check security (nonce verification)
grep -r "check_ajax_referer\|wp_verify_nonce" includes/ admin/

# Find service classes
ls -la includes/services/

# See registered hooks
grep -r "add_action\|add_filter" includes/class-loader.php

# Check for recent errors
tail -50 wp-content/debug.log
```

---

## End of Restoration Protocol

**Last Updated By:** Chip
**Session Date:** 2025-11-17
**Session ID:** 01NamkLhSA2hUnsVz57p5SaM
**Version:** 2.7.0

**Critical Reminder:** ALWAYS read `DEVELOPMENT_LOG.md` first - it contains detailed implementation notes that this protocol references!

---

## Appendix: Phase Implementation Summary

### Phase 1 (v2.6.0) - Photo Gallery & Payment Progress
- Before/After photo categorization
- Photo comparison view
- Payment progress visualization
- Security fixes (XSS protection)

### Phase 2 (v2.7.0) - Communications
- Email template editor
- Template variable system
- SMS integration with Twilio
- Customer SMS opt-in tracking
- Auto-discount scheduler
- Public quote acceptance

### Future Phases (Not Yet Implemented)
- Customer portal
- Accounting software integration
- Additional reporting features
- Mobile app integration

---
