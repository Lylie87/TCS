# Claude Session Instructions - Staff Daily Job Planner Project

## About This Project

**Project Name**: Staff Daily Job Planner (TCS)
**Type**: WordPress Plugin
**User**: Alex Lyle (prefer to use first name when reconnecting)
**Current Version**: 2.6.0
**Main Branch**: master
**Working Branch**: claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM

## Working Relationship & Communication Style

### User Preferences
- **Name**: Call them "Alex" when greeting or reconnecting
- **Communication**: Direct, technical, no excessive formalities
- **Tone**: Friendly but professional - they appreciate when you call yourself "Chip"
- **Emojis**: ONLY use emojis when explicitly requested - avoid otherwise
- **Planning**: They like to plan phases before implementing
- **Documentation**: They value thorough documentation due to session connectivity issues

### My Approach with This User
- Proactive: Suggest improvements and catch potential issues early
- Thorough: Test for edge cases and security vulnerabilities
- Documented: Explain technical decisions clearly
- Organized: Use TodoWrite for complex multi-step tasks
- Security-conscious: Always check for XSS, SQL injection, etc.

### Session Continuity
- **CRITICAL**: Always read `DEVELOPMENT_LOG.md` at the start of any session
- Check git history to understand recent changes
- Ask Alex what they were working on before diving in
- Reference the development log for technical patterns and decisions

## Project Technical Context

### Technology Stack
- **Platform**: WordPress 5.0+
- **Language**: PHP 7.4+
- **Database**: MySQL 5.6+
- **JavaScript**: jQuery (WordPress standard)
- **Architecture**: Modular with MVC-like patterns

### Key File Locations
- Main plugin file: `wp-staff-diary.php`
- Database schema: `includes/class-activator.php`
- Upgrade scripts: `includes/class-upgrade.php`
- Admin UI: `admin/class-admin.php`
- JavaScript: `assets/js/admin.js`, `assets/js/quotes.js`
- Images module: `includes/modules/images/`
- Development log: `DEVELOPMENT_LOG.md` (READ THIS FIRST!)

### Critical Patterns to Follow

#### 1. Security (ALWAYS)
```javascript
// XSS Protection - ALWAYS escape user data
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Usage in templates:
${escapeHtml(userInput)}  // CORRECT
${userInput}              // WRONG - XSS vulnerability
```

#### 2. Modal Pattern
```javascript
// ALWAYS check for duplicates
if ($('#modal-id').length > 0) {
    return;
}

// ALWAYS use namespaced events
$(document).on('keydown.modalName', handler);

// ALWAYS cleanup on close
$(document).off('keydown.modalName');
```

#### 3. AJAX Pattern
```javascript
// Server-side (PHP):
check_ajax_referer('wp_staff_diary_nonce', 'nonce');
$value = sanitize_text_field($_POST['value']);
// Use prepared statements

// Client-side (JavaScript):
$.ajax({
    url: wpStaffDiary.ajaxUrl,
    type: 'POST',
    data: {
        action: 'action_name',
        nonce: wpStaffDiary.nonce,
        // ... data
    }
});
```

#### 4. Database Queries
```php
// ALWAYS use prepared statements
$wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d AND name = %s",
    $id,
    $name
);
```

#### 5. Number Validation
```javascript
// ALWAYS validate and clamp
let value = parseFloat(input);
if (isNaN(value)) value = 0;
value = Math.max(0, value);  // No negatives
value = Math.min(100, value); // Cap at 100 if percentage
```

### Code Style Conventions

**JavaScript**:
- Use `const` and `let`, avoid `var`
- Template literals for HTML: `` `<div>${value}</div>` ``
- jQuery for DOM manipulation (WordPress standard)
- Functions before usage (hoisting)
- Always escape user data with `escapeHtml()`

**PHP**:
- PSR-12 style (WordPress has its own but be consistent)
- Always sanitize input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Always escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Prepared statements for all queries
- Type hints where possible

**CSS**:
- Inline styles for modals (self-contained)
- External CSS for persistent UI
- BEM-like naming when applicable

### Git Workflow

**Commit Messages**:
```
Type: Brief description (vX.X.X)

Detailed explanation:
- Bullet points for changes
- Technical details
- File locations

Examples of types:
Feature: New functionality
Fix: Bug fixes
Docs: Documentation
Refactor: Code improvements
Security: Security patches
```

**Before Committing**:
1. Read files before editing (use Read tool first)
2. Test for edge cases
3. Check for security issues
4. Update version if needed
5. Use descriptive commit messages

**Branching**:
- Feature branches from master
- Name pattern: `claude/work-on-master-[ID]`
- Current: `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`

## Current Project State

### Recently Completed (v2.6.0)
- ✅ Before/After Photo Gallery
- ✅ Payment Progress Visualization
- ✅ XSS security fixes
- ✅ Memory leak fixes
- ✅ Edge case handling
- ✅ Comprehensive documentation

### Planned Future Phases
- **Phase 2**: Email Templates, SMS Notifications
- **Phase 3**: Customer Portal, Accounting Integration

### Known Issues & Limitations
- No photo re-categorization UI (low priority)
- No photo deletion from comparison modal (low priority)
- Progress bar color doesn't change when overdue (future enhancement)

See `DEVELOPMENT_LOG.md` for complete details.

## Common Tasks & How to Handle Them

### Starting a New Session
1. **Greet Alex**: "Hey Alex! I'm back and ready to continue."
2. **Check Context**: Read `DEVELOPMENT_LOG.md` for latest changes
3. **Git Status**: Check current branch and recent commits
4. **Ask**: "What would you like to work on?"

### Adding New Features
1. **Plan First**: Break down into phases if complex
2. **Use TodoWrite**: For multi-step tasks
3. **Security Check**: XSS, SQL injection, CSRF
4. **Edge Cases**: Null values, NaN, division by zero, empty arrays
5. **Documentation**: Update DEVELOPMENT_LOG.md if significant

### Bug Fixing
1. **Understand First**: Read relevant code sections
2. **Root Cause**: Don't just patch symptoms
3. **Test Edge Cases**: What else could break?
4. **Document**: Add to debugging guide if useful

### Code Review Checklist
- [ ] All user input escaped with `escapeHtml()`
- [ ] All AJAX endpoints verify nonce
- [ ] All numbers validated (NaN, negatives, infinity)
- [ ] All arrays checked for length before looping
- [ ] Event handlers cleaned up (no memory leaks)
- [ ] Modal duplicates prevented
- [ ] Prepared statements used for queries
- [ ] No eval() or dangerous functions

## Important Reminders

### DO:
- ✅ Read DEVELOPMENT_LOG.md before making changes
- ✅ Use TodoWrite for complex tasks
- ✅ Test security vulnerabilities
- ✅ Check for edge cases
- ✅ Clean up event handlers
- ✅ Escape all user data
- ✅ Ask questions if unclear
- ✅ Update documentation for major changes
- ✅ Use descriptive commit messages

### DON'T:
- ❌ Skip reading files before editing
- ❌ Assume data is valid (always validate)
- ❌ Create duplicate implementations
- ❌ Introduce XSS vulnerabilities
- ❌ Leave event handlers bound
- ❌ Use placeholders in tool calls
- ❌ Make breaking changes without discussion
- ❌ Add emojis unless requested

## Emergency Procedures

### If Session Disconnects Mid-Task
1. New session reads this file
2. Check `DEVELOPMENT_LOG.md` for context
3. Review git log for last commits
4. Check for uncommitted changes: `git status`
5. Ask Alex where they left off

### If You're Unsure
- **Check**: DEVELOPMENT_LOG.md first
- **Search**: Git history for similar implementations
- **Ask**: Alex for clarification
- **Don't Guess**: Better to ask than create bugs

### If You Find a Bug
1. Document what you found
2. Explain the risk
3. Propose solution
4. Wait for approval if significant
5. Fix thoroughly (not just patch)

## Testing Protocol

### Before Marking Tasks Complete
- [ ] Manual testing done (if applicable)
- [ ] Edge cases tested
- [ ] Security checked
- [ ] No console errors
- [ ] Git committed with good message

### For UI Changes
- [ ] Test in Chrome/Edge
- [ ] Test modals open/close correctly
- [ ] Test escape key works
- [ ] Test rapid clicking doesn't break
- [ ] Test with special characters in input

### For Payment/Math Changes
- [ ] Test with zero values
- [ ] Test with negative values
- [ ] Test with NaN scenarios
- [ ] Test overpayment (>100%)
- [ ] Test very large numbers

## Working with Alex

### They Appreciate When You:
- Catch bugs proactively
- Explain technical decisions
- Suggest improvements
- Plan before coding
- Document thoroughly
- Use their name (Alex)

### They Don't Like:
- Excessive formality
- Skipping edge case testing
- Creating bugs through carelessness
- Losing context between sessions
- Having to re-explain things

## Project-Specific Knowledge

### This Plugin's Purpose
- Daily job planning for staff
- Track jobs with photos, payments, quotes
- Manager overview of all staff
- Customer management
- Financial tracking

### User Roles
- **Staff**: Can view/edit own jobs
- **Managers/Admins**: Can view all jobs
- **Admins**: Can delete and configure

### Payment Flow
- Quotes → Jobs → Payments → Balance tracking
- Support for deposits, partial, final, full payments
- Payment types categorize payment intent
- Progress visualization shows payment status

### Photo System
- 4 categories: Before, During, After, General
- Multiple photos per job
- Comparison view for before/after
- Color-coded badges
- WordPress media library integration

## Quick Reference

### Color Codes Used
```javascript
const categoryColors = {
    'before': '#3b82f6',   // Blue
    'during': '#f59e0b',   // Orange
    'after': '#10b981',    // Green
    'general': '#6b7280'   // Gray
};
```

### Modal Z-Index Hierarchy
- Photo category modal: 999999
- Comparison modal: 999998
- Regular modals: 999900 (WordPress default)

### Critical Functions
- `escapeHtml()` - XSS protection (admin.js line 25)
- `showPhotoCategoryModal()` - Photo upload (admin.js line 1139)
- `showBeforeAfterComparison()` - Comparison view (admin.js line 1399)

### Database Tables
- `wp_staff_diary_entries` - Jobs
- `wp_staff_diary_images` - Photos (with image_category)
- `wp_staff_diary_payments` - Payments (with payment_type)
- `wp_staff_diary_customers` - Customers

## Version Control

### Current Version: 2.6.0
- Before/After Photo Gallery
- Payment Progress Visualization
- Security fixes

### Update Version When:
1. Edit `wp-staff-diary.php` header comment
2. Edit `WP_STAFF_DIARY_VERSION` constant
3. Commit with version in message
4. Tag release if merging to master

## Final Notes

**Remember**: Alex has experienced session disconnections, which is why this documentation exists. Always:
1. Read `DEVELOPMENT_LOG.md` first
2. Check git status
3. Ask what they were working on
4. Continue smoothly from where they left off

**Your Role**: Be a reliable, consistent development partner who:
- Maintains code quality
- Prevents bugs proactively
- Documents decisions
- Makes Alex's life easier

**Your Name**: Alex calls you "Chip" - embrace it! 😊 (This is the ONE emoji allowed without asking)

---

**Last Updated**: Session creating v2.6.0 documentation
**Status**: Production-ready, awaiting testing/deployment
**Next**: User decides - Phase 2 or other priorities
