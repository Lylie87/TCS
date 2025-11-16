# Session Restoration Protocol
**Staff Daily Job Planner WordPress Plugin**
**Last Updated:** 2025-11-16
**Current Version:** 2.2.7 (tags through v2.4.9)
**Active Branch:** `claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe`

---

## Quick Context Restoration

### 1. Project Identity
This is **Staff Daily Job Planner** - a WordPress plugin for service-based business management (specifically flooring/fitting companies). It's a complete digital replacement for paper job sheets, payment tracking, and customer management.

**Core Purpose:** Give staff tools to track daily jobs and managers oversight of all operations through dashboards.

**Key Stats:**
- Main plugin file: `wp-staff-diary.php`
- 41 PHP files in `/includes/`
- 7 custom database tables
- 1,523-line legacy admin class being migrated
- Active modular architecture migration (since v2.1.0)

---

## 2. Get Up to Speed Fast

### Essential First Commands
```bash
# Check current branch and status
git status
git log --oneline -10

# View recent changes
git log -3 --stat

# Understand project structure
ls -la
tree -L 2 -I 'node_modules|vendor'
```

### Quick File Review (Priority Order)
1. `wp-staff-diary.php` - Main plugin file (197 lines) - version, hooks, initialization
2. `includes/class-wp-staff-diary.php` - Core orchestrator
3. `includes/class-module-registry.php` - Module management
4. `admin/class-admin.php` - Legacy admin (1,523 lines) - being phased out
5. `includes/modules/*/` - New modular architecture

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
└── modules/
    ├── payments/
    ├── customers/
    ├── jobs/
    ├── images/
    └── notifications/
```

---

## 3. Architecture Understanding

### Three-Layer Pattern (Modular)
1. **Module Layer** - Feature coordination, hook registration
2. **Controller Layer** - HTTP/AJAX handling, nonces, validation
3. **Repository Layer** - Pure database operations (CRUD)

### Modules Migration Status
✅ **Fully Migrated:**
- Payments (v2.1.0)
- Customers (v2.1.2)
- Jobs (v2.1.2)
- Images (v2.1.2)
- Notifications (v2.2.0)

⚠️ **Legacy (being phased out):**
- `admin/class-admin.php` - Menu, assets, settings, legacy AJAX
- `includes/class-database.php` - Some complex queries still here

### Database Schema (7 Tables)
- `wp_staff_diary_customers` - Customer info
- `wp_staff_diary_entries` - Main jobs table
- `wp_staff_diary_images` - Job photos
- `wp_staff_diary_payments` - Payment records
- `wp_staff_diary_accessories` - Master accessories list
- `wp_staff_diary_job_accessories` - Job-accessory junction
- `wp_staff_diary_notification_logs` - Notification tracking

---

## 4. Recent Development History

### Latest Work (Last 16-17 hours)
- **v2.2.7:** Force cache refresh - `wp-staff-diary.php` updated
- **v2.2.6:** Critical job display and data saving fixes
  - Modified: `admin/class-admin.php`, `assets/js/admin.js`, `includes/modules/jobs/class-jobs-repository.php`
- **v2.2.5:** WooCommerce product search dropdown scroll fix

### Recent Features
- Database diagnostics and repair tool (v2.2.4)
- WooCommerce customer integration (v2.2.3)
- Job form refactoring to shared partial

### Development Patterns Observed
1. Strong focus on **data integrity**
2. **Progressive enhancement** (backwards compatibility maintained)
3. **Modularization** (reducing coupling)
4. **User experience improvements**
5. **Integration capabilities** (WooCommerce, notifications)

---

## 5. Git Workflow

### Branch Strategy
**Active Branch:** `claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe`
**Main Branch:** (check with `git remote show origin`)

### Git Push Protocol
```bash
# CRITICAL: Branch must start with 'claude/' and end with session ID
# Always use -u flag for first push
git push -u origin claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe

# If network failure: Retry up to 4 times with exponential backoff
# (2s, 4s, 8s, 16s)
```

### Current Git State
- Status: Clean (as of session start)
- HEAD: 070a6c5 (Merge PR #38)
- Recent commits all from last 16-17 hours

---

## 6. Key Features Reference

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

👥 **Customer Management**
- UK address format (3 lines + postcode)
- Separate billing/fitting addresses
- Auto-complete search
- Customer history

💰 **Financial**
- Payment types: Deposit, Partial, Final, Full
- Methods: Cash, Bank Transfer, Card
- VAT calculations (configurable, default 20%)
- "PAID IN FULL" indicators

📄 **Documentation**
- PDF generation via TCPDF
- Professional branding
- Company logo, bank details, T&Cs

🛒 **Integrations**
- WooCommerce products
- Email/SMS notifications

---

## 7. Common Tasks & Workflows

### Bug Fix Workflow
1. Read relevant files to understand issue
2. Check recent git history for related changes
3. Test locally if possible
4. Make fix with clear comments
5. Commit with descriptive message
6. Push to active branch

### Adding New Feature
1. Determine if it fits existing module or needs new one
2. Follow modular pattern: Interface → Base Class → Concrete Implementation
3. Create Repository (data operations)
4. Create Controller (AJAX/HTTP handling)
5. Create Module (hook registration)
6. Register in Module Registry
7. Test with backwards compatibility in mind

### Code Review Checklist
- [ ] Follows modular architecture pattern
- [ ] Nonce verification on AJAX requests
- [ ] Data sanitization on input
- [ ] Escaping on output
- [ ] Backwards compatibility maintained
- [ ] No breaking changes to database schema
- [ ] Comments for complex logic

---

## 8. Critical File Locations

### Configuration
- Main plugin: `wp-staff-diary.php` (version, hooks)
- Activation: `includes/class-activator.php` (DB creation)
- Upgrades: `includes/class-upgrade.php` (migrations)

### Views (Admin UI)
- Calendar: `admin/views/calendar-view.php`
- List: `admin/views/my-diary.php`
- Staff Overview: `admin/views/staff-overview.php`
- Customers: `admin/views/customers.php`
- Settings: `admin/views/settings.php` (68,101 lines!)

### Assets
- CSS: `assets/css/admin.css` (1,051+ lines)
- JS: `assets/js/admin.js` (1,265 lines)

### External Dependencies
- TCPDF: `libs/tcpdf/` (optional, for PDFs)

---

## 9. Development Environment

### Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- TCPDF library (optional)

### Testing Considerations
- Role-based access must be tested for Staff vs Manager vs Admin
- Payment calculations must be accurate (VAT, accessories, totals)
- Database operations should maintain referential integrity
- AJAX calls require nonce verification

---

## 10. Quick Restoration Commands

### For New Session
```bash
# 1. Verify you're on correct branch
git checkout claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe

# 2. Check for any uncommitted changes
git status

# 3. Pull latest from remote
git pull origin claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe

# 4. Review recent work
git log --oneline -5
git diff HEAD~1

# 5. Quick structure check
ls -la includes/modules/
```

### Essential Reads for Full Context
```bash
# Read this restoration protocol
cat .claude/RESTORATION_PROTOCOL.md

# Check version and recent changelog
head -50 wp-staff-diary.php

# View module registry to see what's active
cat includes/class-module-registry.php

# Check latest work in key files
git log -p -1 includes/modules/jobs/class-jobs-repository.php
```

---

## 11. Known Issues & Gotchas

### Current State
- **Job display issues** were just fixed in v2.2.6 (check if any edge cases remain)
- **Cache refresh** was needed in v2.2.7 (monitor for cache-related bugs)
- **WooCommerce dropdown** had scroll-follow issue (fixed in v2.2.5)

### Architecture Transition
- Some code still in `admin/class-admin.php` - eventual migration target
- `class-database.php` has legacy complex queries - handle with care
- Backwards compatibility is CRITICAL - don't break existing installations

### Testing Gaps
- No automated test suite visible
- Manual testing required for AJAX operations
- Role-based access needs careful verification

---

## 12. Communication Style

### Project Owner: Alex Lyle (TCS Flooring Solutions)
- Real business use case (flooring company)
- Values data integrity and stability
- Active development (commits within last day)

### My Identity in This Session
**Dex** - Short for "index," navigator and organizer of code

### Tone Preferences
- Professional and technical
- Direct and objective (no excessive praise)
- Focus on facts and problem-solving
- Concise communication (CLI context)

---

## 13. Agent Instructions Template

**For New Session - Paste This:**

```
I'm continuing work on the Staff Daily Job Planner WordPress plugin.

Quick context:
- This is a business management system for service-based companies
- WordPress plugin (PHP 7.4+, MySQL)
- Currently on branch: claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe
- Version 2.2.7 (latest fixes for job display and caching)
- Mid-migration to modular architecture (Module → Controller → Repository)

Please read the restoration protocol:
.claude/RESTORATION_PROTOCOL.md

Then review recent git history to see what was done:
git log --oneline -10

I need help with: [STATE YOUR TASK HERE]
```

---

## 14. Emergency Recovery

### If Git State Is Unclear
```bash
# See all branches
git branch -a

# See remote configuration
git remote -v

# See what's staged
git status -v

# See unpushed commits
git log origin/claude/clarify-request-01J9bsuNvaZyRMXidTfRhpGe..HEAD
```

### If Code State Is Unclear
```bash
# Find all modified files in last 24 hours
find . -type f -name "*.php" -mtime -1

# Search for recent TODO/FIXME comments
grep -r "TODO\|FIXME\|XXX\|HACK" --include="*.php" .

# Check for syntax errors
find . -name "*.php" -exec php -l {} \;
```

### If Database State Is Unclear
- Check `includes/class-activator.php` for table schemas
- Review `includes/class-upgrade.php` for migration history
- Look at module repositories for current query patterns

---

## 15. Success Metrics

### How to Know You're On Track
✅ All commits pushed to correct branch
✅ No PHP syntax errors
✅ Follows modular architecture pattern
✅ Backwards compatible (don't break existing installs)
✅ Clear commit messages
✅ Code comments for complex logic
✅ Git history is clean and reviewable

---

## End of Restoration Protocol

**Last Session Agent:** Dex
**Session Date:** 2025-11-16
**Session ID:** 01J9bsuNvaZyRMXidTfRhpGe

**Next Agent:** Feel free to pick your own name, but know that Chip and Dex are taken! 😄

---

## Appendix: Quick Reference Commands

```bash
# Project structure overview
tree -L 3 -I 'node_modules|vendor|.git'

# Find all modules
ls -la includes/modules/

# Check plugin version
grep "Version:" wp-staff-diary.php

# See all database tables (in code)
grep "CREATE TABLE" includes/class-activator.php

# Find all AJAX handlers
grep -r "wp_ajax_" includes/ admin/

# Check for security (nonce verification)
grep -r "check_ajax_referer\|wp_verify_nonce" .

# Find all interfaces
ls -la includes/interfaces/

# See registered hooks
grep -r "add_action\|add_filter" includes/class-loader.php
```
