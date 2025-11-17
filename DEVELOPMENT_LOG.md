# Development Log - Staff Daily Job Planner

## Session Overview
**Date**: Current Session (Continuation)
**Branch**: `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`
**Starting Version**: 2.5.0
**Current Version**: 2.6.0

---

## Phase 1 Implementation - v2.6.0

### Features Implemented

#### 1. Before/After Photo Gallery System

**Objective**: Allow categorization of photos into Before, During, After, and General categories with visual comparison capabilities.

**Database Changes**:
- Column `image_category` already existed in `staff_diary_images` table (varchar(50), default 'general')
- Migration code already in place in `includes/class-upgrade.php` (lines 217-223)

**Files Modified**:

##### `includes/modules/images/class-images-repository.php`
**Purpose**: Handle database operations for categorized images

**Changes Made**:
1. Updated `add_image()` method (line 30):
   - Added `$category` parameter (default 'general')
   - Stores category in `image_category` column

2. Added `get_entry_images_by_category()` method (lines 64-71):
   - Fetches images filtered by specific category
   - Uses prepared statements for security

3. Added `get_entry_images_grouped()` method (lines 79-98):
   - Returns images grouped by category in associative array
   - Keys: 'before', 'during', 'after', 'general'
   - Handles undefined categories gracefully

**Technical Details**:
```php
public function add_image($diary_entry_id, $image_url, $attachment_id = null, $caption = '', $category = 'general') {
    $data = array(
        'diary_entry_id' => $diary_entry_id,
        'image_url' => $image_url,
        'attachment_id' => $attachment_id,
        'image_caption' => $caption,
        'image_category' => $category,  // NEW
        'uploaded_at' => current_time('mysql')
    );
    return $this->create($data);
}
```

##### `includes/modules/images/class-images-controller.php`
**Purpose**: Handle AJAX requests for image uploads with category support

**Changes Made** (lines 61-71):
1. Added category parameter extraction from POST
2. Added category validation against whitelist
3. Passes category to repository's add_image()

**Security Implementation**:
```php
$category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'general';

// Validate category
$valid_categories = array('before', 'during', 'after', 'general');
if (!in_array($category, $valid_categories)) {
    $category = 'general';
}
```

##### `assets/js/admin.js`
**Purpose**: Client-side photo upload UI and before/after comparison view

**Major Changes**:

1. **Utility Function** (lines 25-30):
   - Added `escapeHtml()` function to prevent XSS attacks
   - Uses DOM textContent approach for safe escaping

2. **Photo Category Modal** (lines 1139-1197):
   - Function: `showPhotoCategoryModal(file, entryId, callback)`
   - Shows modal with 4 category options + optional caption field
   - Prevents duplicate modals with existence check
   - Implements callback pattern for async flow
   - Handles Escape key with namespaced event (`keydown.photoCategoryModal`)
   - Cleans up event handlers on close to prevent memory leaks

**Modal HTML Structure**:
```javascript
<select id="photo-category-select">
    <option value="before">Before</option>
    <option value="during">During</option>
    <option value="after">After</option>
    <option value="general">General</option>
</select>
<input type="text" id="photo-caption-input" placeholder="Enter photo caption...">
```

3. **Photo Upload Handlers** (lines 1205-1312):
   - Two handlers: view modal and edit form
   - Both use category modal before upload
   - Reset file input on cancel to prevent re-triggers
   - Send category and caption in FormData to AJAX endpoint

4. **Photo Display with Category Badges** (lines 241-257, 1062-1084):
   - Color-coded badges overlay on each photo:
     - Before: Blue (#3b82f6)
     - During: Orange (#f59e0b)
     - After: Green (#10b981)
     - General: Gray (#6b7280)
   - Badge positioned top-right with CSS
   - All user data escaped with `escapeHtml()`

5. **Before/After Comparison View** (lines 1372-1530):
   - Function: `showBeforeAfterComparison(entry)`
   - Triggered by "View Before/After Comparison" button
   - Only shown when job has both before AND after photos

   **Layout Structure**:
   - Side-by-side grid for before/after pairs
   - Handles unequal photo counts gracefully
   - Shows "No before/after photo" placeholders
   - Separate section for "during" progress photos
   - Full-screen modal overlay (z-index: 999998)
   - Click to open full-size in new tab

   **Technical Implementation**:
   ```javascript
   const beforeImages = entry.images.filter(img => img.image_category === 'before');
   const afterImages = entry.images.filter(img => img.image_category === 'after');
   const duringImages = entry.images.filter(img => img.image_category === 'during');

   const maxPairs = Math.max(beforeImages.length, afterImages.length);
   // Loop creates side-by-side pairs
   ```

   **Modal Features**:
   - Escape key closes modal
   - Background click closes modal
   - Proper cleanup function prevents memory leaks
   - Prevents duplicate modals

##### `assets/js/quotes.js`
**Purpose**: Add category support to quote photo uploads

**Changes Made** (lines 613-673):
1. Added identical `showPhotoCategoryModal()` function
2. Updated `handleQuotePhotoUpload()` to use category modal
3. Sends category and caption to backend
4. Same security measures as admin.js

---

#### 2. Deposit & Partial Payment Tracking

**Objective**: Visualize payment progress with breakdown by payment type (Deposit, Partial, Final, Full).

**Note**: Payment types already existed in database and forms. This phase added visual representation.

**Database Schema** (Reference - `staff_diary_payments` table):
```sql
payment_type varchar(100) DEFAULT NULL  -- Values: 'deposit', 'partial', 'final', 'full'
```

**Files Modified**:

##### `assets/js/admin.js`
**Purpose**: Add payment progress visualization to job details view

**Changes Made** (lines 972-1030):

1. **Payment Progress Section**:
   - Only displays when `entry.total > 0`
   - Positioned after financial summary table

2. **Calculation Logic**:
   ```javascript
   const totalPaid = Math.max(0, parseFloat(entry.total) - balance);
   let percentPaid = (totalPaid / entry.total) * 100;

   // Clamp percentage between 0-100
   percentPaid = Math.min(100, Math.max(0, percentPaid));

   // Handle NaN
   if (isNaN(percentPaid)) {
       percentPaid = 0;
   }
   ```

3. **Payment Type Aggregation**:
   ```javascript
   const paymentsByType = {
       'deposit': 0,
       'partial': 0,
       'final': 0,
       'full': 0
   };

   entry.payments.forEach(function(payment) {
       const type = payment.payment_type || 'partial';
       const amount = parseFloat(payment.amount);
       if (!isNaN(amount) && amount > 0) {
           paymentsByType[type] += amount;
       }
   });
   ```

4. **Visual Components**:

   **Progress Bar**:
   - Container: Gray background (#e0e0e0), 30px height, rounded
   - Fill: Green gradient (#10b981 → #34d399), width = percentPaid
   - Text: Centered percentage overlay, white text when filled

   **Payment Breakdown Grid**:
   - 2-column grid layout
   - Shows Total Due vs Total Paid
   - Conditionally shows each payment type (only if > 0)
   - Color-coded: Green for Total Paid

---

### Bug Fixes - Critical Security & Stability

**Commit**: `138be58` - Fix: Critical bug fixes for Phase 1 features (v2.6.0)

#### 1. XSS Security Vulnerabilities ⚠️ HIGH PRIORITY

**Issue**: User-controlled data (photo captions, URLs, order numbers) inserted into HTML without escaping.

**Attack Vector**:
```javascript
// Example malicious caption:
"<img src=x onerror='alert(document.cookie)'>"
```

**Fix Applied**:
1. Created `escapeHtml()` utility function (admin.js lines 25-30)
2. Escaped all user data in 8 locations:
   - Photo captions in view modal (line 1081)
   - Photo captions in edit form (line 254)
   - Photo URLs in view modal (line 1079)
   - Photo URLs in edit form (line 254)
   - Before photo captions (line 1460)
   - After photo captions (line 1473)
   - During photo captions (line 1492)
   - Order number in comparison modal (line 1443)

**Implementation**:
```javascript
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;  // Safe: browser escapes automatically
    return div.innerHTML;
}

// Usage:
${escapeHtml(image.image_caption)}
```

#### 2. Duplicate Modal Prevention

**Issue**: Clicking upload/comparison buttons multiple times created stacked modals.

**Problems Caused**:
- Multiple modals with same IDs
- Event handlers firing multiple times
- Memory leaks from unclosed modals
- Confusing UX

**Fix Applied**:
```javascript
// Photo category modal (admin.js line 1140-1143)
if ($('#photo-category-modal').length > 0) {
    return;
}

// Comparison modal (admin.js line 1402-1404)
if ($('#comparison-modal').length > 0) {
    return;
}

// Same fix in quotes.js (line 615-617)
```

#### 3. Event Handler Memory Leaks

**Issue**: Escape key event handlers not cleaned up when modals closed.

**Problem**:
- Event handlers accumulate on each modal open
- Multiple handlers fire on single keypress
- Memory leaks over time

**Fix Applied**:

1. **Namespaced Events**:
   ```javascript
   $(document).on('keydown.photoCategoryModal', function(e) {
       if (e.key === 'Escape' || e.keyCode === 27) {
           $('#photo-category-modal').remove();
           $(document).off('keydown.photoCategoryModal');  // Cleanup
           callback(null);
       }
   });
   ```

2. **Cleanup on Modal Close**:
   ```javascript
   // Photo category modal
   $('#cancel-photo-upload').on('click', function() {
       $('#photo-category-modal').remove();
       $(document).off('keydown.photoCategoryModal');  // Cleanup
       callback(null);
   });

   $('#confirm-photo-upload').on('click', function() {
       // ... get values ...
       $('#photo-category-modal').remove();
       $(document).off('keydown.photoCategoryModal');  // Cleanup
       callback({category, caption});
   });
   ```

3. **Dedicated Close Function for Comparison Modal**:
   ```javascript
   function closeComparisonModal() {
       $('#comparison-modal').remove();
       $(document).off('keydown.comparisonModal');  // Cleanup
   }

   $('#close-comparison-modal').on('click', closeComparisonModal);
   $('#comparison-modal').on('click', function(e) {
       if (e.target.id === 'comparison-modal') {
           closeComparisonModal();
       }
   });
   $(document).on('keydown.comparisonModal', function(e) {
       if (e.key === 'Escape' || e.keyCode === 27) {
           closeComparisonModal();
       }
   });
   ```

#### 4. Payment Progress Calculation Edge Cases

**Issues**: Multiple edge cases could break progress bar:

**Edge Case 1: Overpayment (>100%)**
```javascript
// Scenario: total = £100, paid = £150
percentPaid = (150 / 100) * 100 = 150%
// Problem: Progress bar breaks, shows 150% width
```

**Edge Case 2: Negative Values**
```javascript
// Scenario: balance = -10 (overpaid)
totalPaid = 100 - (-10) = 110
// Problem: Could show negative amounts in some scenarios
```

**Edge Case 3: Division by Zero**
```javascript
// Scenario: total = 0
percentPaid = (paid / 0) * 100 = Infinity or NaN
```

**Edge Case 4: Invalid Payment Amounts**
```javascript
// Scenario: payment.amount = "invalid"
parseFloat("invalid") = NaN
paymentsByType['deposit'] = 0 + NaN = NaN
```

**Fix Applied** (lines 974-999):
```javascript
// Protection 1: Prevent negative totalPaid
const totalPaid = Math.max(0, parseFloat(entry.total) - balance);

// Protection 2: Calculate percentage
let percentPaid = (totalPaid / entry.total) * 100;

// Protection 3: Clamp between 0-100
percentPaid = Math.min(100, Math.max(0, percentPaid));

// Protection 4: Handle NaN
if (isNaN(percentPaid)) {
    percentPaid = 0;
}

// Protection 5: Validate payment amounts
entry.payments.forEach(function(payment) {
    const type = payment.payment_type || 'partial';
    const amount = parseFloat(payment.amount);
    if (!isNaN(amount) && amount > 0) {  // Validate
        paymentsByType[type] = (paymentsByType[type] || 0) + amount;
    }
});
```

#### 5. Null/Undefined Data Handling

**Issue**: Code could crash if expected data was missing.

**Scenarios**:
- Entry has no images array
- Image has no category set
- Payment has no type set

**Fix Applied**:

1. **Images Array Validation** (line 1407-1410):
   ```javascript
   if (!entry.images || entry.images.length === 0) {
       alert('No images found for this job');
       return;
   }
   ```

2. **Category Fallbacks** (throughout):
   ```javascript
   const category = image.image_category || 'general';
   ```

3. **Payment Type Fallbacks** (line 994):
   ```javascript
   const type = payment.payment_type || 'partial';
   ```

---

### Version Update

**File**: `wp-staff-diary.php`

**Changes** (lines 6, 21):
```php
* Version: 2.6.0

define('WP_STAFF_DIARY_VERSION', '2.6.0');
```

---

## Git Commit History

### Commit 1: Feature Implementation
**Hash**: `3329df7`
**Message**: Feature: Before/After Photo Gallery & Payment Progress Tracking (v2.6.0)

**Summary**:
- Photo categorization system (Before, During, After, General)
- Category selection modal with caption support
- Color-coded category badges on all photos
- Before/After comparison view modal
- Payment progress visualization with percentage bar
- Payment breakdown by type

**Files Changed**: 5 files, 469 insertions, 65 deletions

### Commit 2: Bug Fixes
**Hash**: `138be58`
**Message**: Fix: Critical bug fixes for Phase 1 features (v2.6.0)

**Summary**:
- XSS vulnerability fixes (8 locations)
- Duplicate modal prevention (3 modals)
- Event handler memory leak fixes
- Payment calculation edge cases (5 protections)
- Null/undefined data validation

**Files Changed**: 2 files, 96 insertions, 17 deletions

---

## Technical Architecture Decisions

### 1. Why Category Modal Instead of Inline Dropdown?

**Decision**: Use modal dialog for category selection instead of inline dropdown on upload button.

**Reasoning**:
- Provides space for optional caption field
- Clear, focused UX - user must make conscious category choice
- Prevents accidental uploads without categorization
- Allows future expansion (e.g., add tags, select multiple categories)
- Modal can be cancelled without uploading

**Alternative Considered**: Dropdown next to upload button
**Why Rejected**: Limited space, no room for caption, easy to miss

### 2. Why Callback Pattern for Category Modal?

**Decision**: Use callback function instead of Promise or async/await.

**Reasoning**:
- jQuery-based codebase - callbacks are standard pattern
- Simple async flow - no complex chaining needed
- Consistent with existing codebase patterns
- No polyfill requirements for older browsers

**Implementation**:
```javascript
showPhotoCategoryModal(file, entryId, function(result) {
    if (!result) {
        // User cancelled
        $input.val('');
        return;
    }

    // User confirmed - proceed with upload
    const formData = new FormData();
    formData.append('category', result.category);
    formData.append('caption', result.caption);
    // ... continue upload
});
```

### 3. Why escapeHtml() Instead of jQuery's .text()?

**Decision**: Create dedicated `escapeHtml()` function using DOM API.

**Reasoning**:
- Works with template literals (can't use jQuery in template strings)
- Explicit escaping - clear security intent
- Reusable utility function
- No jQuery dependency for the function itself
- Standard DOM API - universally understood

**Alternative Considered**: jQuery's `$('<div>').text(caption).html()`
**Why Rejected**: Requires jQuery, less clear in template literals

### 4. Why Math.min(100, Math.max(0, %)) for Clamping?

**Decision**: Use nested Math functions instead of if statements.

**Reasoning**:
- Single expression - can be used inline
- Functional programming style - no side effects
- Clear intent - "clamp between 0 and 100"
- Standard JavaScript pattern
- More concise than:
  ```javascript
  if (percentPaid < 0) percentPaid = 0;
  if (percentPaid > 100) percentPaid = 100;
  ```

### 5. Why Filter Images Client-Side Instead of Server-Side?

**Decision**: Group images by category in JavaScript, not PHP.

**Reasoning**:
- Images already loaded for display - no extra AJAX needed
- Reduces server load - no additional queries
- Faster UX - instant filtering
- Simpler backend - no new endpoints
- Frontend has all data needed

**Trade-off**: Slightly more JavaScript code, but better performance overall.

### 6. Why Namespaced Event Handlers?

**Decision**: Use `keydown.photoCategoryModal` instead of `keydown`.

**Reasoning**:
- Allows specific unbinding: `$(document).off('keydown.photoCategoryModal')`
- Won't interfere with other Escape key handlers
- Prevents accidentally unbinding other features
- Standard jQuery pattern for event namespacing
- Clean separation of concerns

**Example**:
```javascript
// Without namespace - WRONG
$(document).on('keydown', handler);
$(document).off('keydown');  // Unbinds ALL keydown handlers!

// With namespace - CORRECT
$(document).on('keydown.photoCategoryModal', handler);
$(document).off('keydown.photoCategoryModal');  // Only unbinds this one
```

---

## Code Style & Patterns

### Consistent Patterns Used

1. **Modal Structure**:
   - Always check for duplicates first
   - Use template literals for HTML
   - Append to body with `$('body').append()`
   - Fade in with `$('#modal').fadeIn()`
   - Remove completely on close (not just hide)
   - Clean up event handlers on close

2. **Security Pattern**:
   - Always escape user data: `${escapeHtml(userInput)}`
   - Validate categories against whitelist
   - Use prepared statements in PHP (already in place)
   - Sanitize all POST data in PHP

3. **Error Handling**:
   - Validate data exists before using: `if (!data || !data.length)`
   - Provide fallback values: `value || 'default'`
   - Check for NaN: `if (isNaN(number))`
   - Use Math.max(0, value) to prevent negatives

4. **Color Coding**:
   - Define color objects at usage point
   - Use semantic color variables in CSS-in-JS
   - Consistent color scheme:
     ```javascript
     const categoryColors = {
         'before': '#3b82f6',   // Blue
         'during': '#f59e0b',   // Orange
         'after': '#10b981',    // Green
         'general': '#6b7280'   // Gray
     };
     ```

---

## Testing Checklist

### Manual Testing Required Before Release

#### Photo Category System
- [ ] Upload photo with each category (before, during, after, general)
- [ ] Verify category badge appears with correct color
- [ ] Upload photo with caption - verify caption displays
- [ ] Upload photo without caption - verify no caption area shows
- [ ] Test Escape key closes category modal
- [ ] Test clicking Cancel closes modal without uploading
- [ ] Test clicking outside modal (should NOT close - by design)
- [ ] Test uploading twice quickly - verify no duplicate modals
- [ ] Test with special characters in caption: `<script>alert('test')</script>`
- [ ] Verify caption is escaped (shows as text, not executed)

#### Before/After Comparison
- [ ] Job with only before photos - verify no comparison button
- [ ] Job with only after photos - verify no comparison button
- [ ] Job with both - verify comparison button appears
- [ ] Click comparison button - verify modal opens
- [ ] Verify before photos appear in left column
- [ ] Verify after photos appear in right column
- [ ] Test with unequal counts (3 before, 1 after) - verify placeholders
- [ ] Verify during photos appear in separate section
- [ ] Click photo in comparison - verify opens in new tab
- [ ] Test Escape key closes comparison modal
- [ ] Test background click closes modal
- [ ] Test opening comparison twice quickly - verify no duplicates

#### Payment Progress
- [ ] Job with 0% paid - verify empty progress bar with "0.0% Paid"
- [ ] Job with 50% paid - verify half-filled bar with "50.0% Paid"
- [ ] Job with 100% paid - verify full bar with "100.0% Paid"
- [ ] Job with overpayment - verify clamped at "100.0% Paid"
- [ ] Verify payment breakdown shows correct totals
- [ ] Deposit payment - verify appears in "Deposits: £X.XX"
- [ ] Partial payment - verify appears in "Partial Payments: £X.XX"
- [ ] Multiple payment types - verify all show correctly
- [ ] Job with £0.00 total - verify progress section doesn't appear

#### Edge Cases
- [ ] Job with no images - verify graceful handling
- [ ] Photo with null category - verify defaults to "General"
- [ ] Payment with null type - verify defaults to "Partial"
- [ ] Very long caption (500 chars) - verify doesn't break layout
- [ ] Image URL with quotes/special chars - verify properly escaped
- [ ] Rapid clicking upload button - verify no crashes

#### Browser Compatibility
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## Known Limitations & Future Improvements

### Current Limitations

1. **No Photo Deletion in Category Modal**
   - Once uploaded, photos must be deleted from main view
   - Could add delete button in comparison modal
   - Not critical - low priority

2. **No Photo Re-categorization**
   - Category is set on upload, cannot be changed
   - Would require new AJAX endpoint to update category
   - Future enhancement: Edit photo modal

3. **No Photo Sorting**
   - Photos display in upload order (ID ASC)
   - Could add drag-and-drop reordering
   - Could add manual sort order field

4. **Fixed Progress Bar Colors**
   - Always green, even when overdue
   - Could turn red/yellow when overdue
   - Would need overdue calculation logic

5. **No Photo Thumbnails in Category Modal**
   - Preview before categorizing would be helpful
   - FileReader API could show preview
   - Low priority - file name usually sufficient

### Recommended Future Enhancements

**High Priority**:
1. Email Templates System (Phase 2)
2. SMS Notifications (Phase 2)
3. Customer Portal (Phase 3)

**Medium Priority**:
4. Photo re-categorization UI
5. Progress bar color coding for overdue jobs
6. Photo sorting/reordering
7. Bulk photo upload with category

**Low Priority**:
8. Photo preview in category modal
9. Photo editing (crop, rotate)
10. Photo metadata (EXIF data display)

---

## Debugging Guide

### Common Issues & Solutions

#### Issue: Photos not showing category badges
**Check**:
1. Is `image_category` column in database? Run: `SHOW COLUMNS FROM wp_staff_diary_images LIKE 'image_category'`
2. Is upgrade script running? Check `includes/class-upgrade.php` line 217-223
3. Is JavaScript loading? Check browser console for errors
4. Is category data being returned? Check AJAX response in Network tab

**Solution**: Most likely database column missing. Run:
```sql
ALTER TABLE wp_staff_diary_images
ADD COLUMN image_category varchar(50) DEFAULT 'general'
AFTER image_caption;
```

#### Issue: Category modal not appearing
**Check**:
1. Browser console for JavaScript errors
2. Is modal being created? Check Elements tab in DevTools
3. Is modal hidden by CSS? Check z-index (should be 999999)
4. Is jQuery loaded? Type `jQuery.fn.jquery` in console

**Solution**: Check for JavaScript errors preventing execution. Modal HTML might have syntax error in template literal.

#### Issue: Escape key not closing modal
**Check**:
1. Are event handlers being bound? Add console.log in handler
2. Is event being fired? Test with: `$(document).on('keydown', e => console.log(e.key))`
3. Is handler being removed? Check that cleanup code runs

**Solution**: Verify namespaced event is correctly bound and cleaned up. Check for duplicate bindings.

#### Issue: Progress bar showing wrong percentage
**Check**:
1. Log values: `console.log({total, balance, totalPaid, percentPaid})`
2. Check for NaN: `console.log(isNaN(percentPaid))`
3. Verify payments array: `console.log(entry.payments)`

**Solution**: Most likely data type issue. Ensure parseFloat() is used on all numeric values.

#### Issue: XSS vulnerability (caption executing scripts)
**Check**:
1. Is `escapeHtml()` function defined?
2. Is it being called on user data?
3. Check HTML output in DevTools - should see `&lt;script&gt;` not `<script>`

**Solution**: Every instance of user-controlled data in template literals must use `${escapeHtml(data)}`.

---

## Development Workflow

### Making Changes to Photo System

1. **Adding New Category**:
   ```javascript
   // Step 1: Update modal dropdown (admin.js ~line 1149)
   <select id="photo-category-select">
       <option value="before">Before</option>
       <option value="during">During</option>
       <option value="after">After</option>
       <option value="general">General</option>
       <option value="NEW_CATEGORY">New Category</option>  // ADD
   </select>

   // Step 2: Update color mapping (admin.js ~line 1070, 243)
   const categoryColors = {
       'before': '#3b82f6',
       'during': '#f59e0b',
       'after': '#10b981',
       'general': '#6b7280',
       'NEW_CATEGORY': '#COLOR_CODE'  // ADD
   };

   // Step 3: Update validation (class-images-controller.php line 66)
   $valid_categories = array('before', 'during', 'after', 'general', 'NEW_CATEGORY');

   // Step 4: Update grouping (class-images-repository.php line 81-85)
   $grouped = array(
       'before' => array(),
       'during' => array(),
       'after' => array(),
       'general' => array(),
       'NEW_CATEGORY' => array()  // ADD
   );
   ```

2. **Modifying Progress Bar**:
   ```javascript
   // Located in: assets/js/admin.js lines 972-1030

   // Change colors:
   background: linear-gradient(90deg, #10b981 0%, #34d399 100%);

   // Change height:
   height: 30px;

   // Add thresholds (e.g., color code by percentage):
   const barColor = percentPaid < 25 ? '#ef4444' :  // Red
                    percentPaid < 75 ? '#f59e0b' :  // Orange
                                      '#10b981';    // Green
   ```

3. **Adding Fields to Category Modal**:
   ```javascript
   // Located in: showPhotoCategoryModal() function

   // Add after caption input:
   <label>New Field:</label>
   <input type="text" id="photo-new-field" placeholder="...">

   // In confirm handler, add:
   const newField = $('#photo-new-field').val();
   callback({category: category, caption: caption, newField: newField});

   // Update upload handlers to include in FormData:
   formData.append('new_field', result.newField);

   // Update backend to save:
   // includes/modules/images/class-images-controller.php
   $new_field = sanitize_text_field($_POST['new_field']);
   // Add to database table first
   ```

### Making Changes to Payment Progress

1. **Change Progress Bar Style**:
   ```javascript
   // Located: assets/js/admin.js line 1005-1011

   // Currently: Horizontal bar
   // Change to: Circular progress (requires different HTML/CSS)
   // Or: Vertical bar (change grid layout)
   ```

2. **Add Payment Milestones**:
   ```javascript
   // Add markers at specific percentages:
   const milestones = [25, 50, 75, 100];
   milestones.forEach(m => {
       html += `<div style="position: absolute; left: ${m}%;
                top: 0; height: 100%; width: 1px;
                background: rgba(0,0,0,0.2);"></div>`;
   });
   ```

3. **Add Remaining Amount Display**:
   ```javascript
   // Add after totalPaid:
   const remaining = entry.total - totalPaid;
   html += `<div><strong>Remaining:</strong>
            <span style="color: #ef4444;">£${remaining.toFixed(2)}</span></div>`;
   ```

---

## Security Checklist

### Before Deploying Changes

- [ ] All user input escaped with `escapeHtml()`
- [ ] All AJAX endpoints verify nonce
- [ ] All database queries use prepared statements
- [ ] No eval() or similar dangerous functions
- [ ] File uploads validated (already handled by WordPress)
- [ ] No sensitive data in client-side JavaScript
- [ ] HTTPS enforced (WordPress/server config)
- [ ] SQL injection protected (prepared statements)
- [ ] XSS protected (escapeHtml everywhere)
- [ ] CSRF protected (nonce verification)

---

## Performance Considerations

### Current Performance

**Image Loading**:
- All images loaded eagerly on job view
- No lazy loading implemented
- Images served at full resolution

**Recommendations**:
1. Add lazy loading for comparison modal
2. Generate thumbnails on upload
3. Use WordPress image sizes
4. Implement CDN for images

**JavaScript Bundle Size**:
- admin.js: ~1500 lines (acceptable for admin panel)
- No minification currently
- Could split into modules if grows larger

**Database Queries**:
- Images loaded with job entry (single query with JOIN)
- No N+1 query problems
- Indexes on diary_entry_id and image_category

---

## Data Flow Diagrams

### Photo Upload Flow

```
User clicks "Upload Photo"
    ↓
showPhotoCategoryModal() called
    ↓
User selects category + caption
    ↓
Callback receives {category, caption}
    ↓
Create FormData with:
    - action: 'upload_job_image'
    - nonce
    - diary_entry_id
    - image (file)
    - category
    - caption
    ↓
AJAX POST to admin-ajax.php
    ↓
WordPress routes to: wp_ajax_upload_job_image
    ↓
WP_Staff_Diary_Images_Controller::upload()
    ↓
Validate nonce & entry_id
    ↓
media_handle_upload() - WordPress handles file
    ↓
Get attachment ID & URL
    ↓
Sanitize & validate category
    ↓
WP_Staff_Diary_Images_Repository::add_image()
    ↓
Insert into wp_staff_diary_images:
    - diary_entry_id
    - image_url
    - attachment_id
    - image_caption
    - image_category
    - uploaded_at
    ↓
Return success + image object
    ↓
JavaScript receives response
    ↓
Reload job view to show new photo
```

### Before/After Comparison Flow

```
User clicks "View Before/After Comparison"
    ↓
Get entry_id from button data attribute
    ↓
AJAX GET wp_ajax_get_diary_entry
    ↓
Receive entry with images array
    ↓
showBeforeAfterComparison(entry) called
    ↓
Check for duplicate modal (return if exists)
    ↓
Validate entry.images exists
    ↓
Filter images by category:
    - beforeImages = filter(category === 'before')
    - afterImages = filter(category === 'after')
    - duringImages = filter(category === 'during')
    ↓
Build HTML:
    - Loop Math.max(before.length, after.length) times
    - Create side-by-side pairs
    - Show placeholders for missing photos
    - Add during section if duringImages.length > 0
    ↓
Append modal HTML to body
    ↓
Bind event handlers:
    - Close button click
    - Background click
    - Escape key (namespaced)
    ↓
Modal displays
    ↓
User closes modal
    ↓
closeComparisonModal() called
    ↓
Remove modal from DOM
    ↓
Unbind keydown.comparisonModal event
```

### Payment Progress Calculation Flow

```
displayEntryDetails(entry) called
    ↓
Check entry.total > 0
    ↓
Calculate totalPaid:
    totalPaid = Math.max(0, entry.total - entry.balance)
    ↓
Calculate percentPaid:
    percentPaid = (totalPaid / entry.total) * 100
    ↓
Clamp percentage:
    percentPaid = Math.min(100, Math.max(0, percentPaid))
    ↓
Validate percentage:
    if (isNaN(percentPaid)) percentPaid = 0
    ↓
Aggregate payments by type:
    Loop entry.payments
        Validate payment.amount
        Add to paymentsByType[payment.payment_type]
    ↓
Build HTML:
    - Progress bar container
    - Fill div (width = percentPaid%)
    - Percentage text overlay
    - 2-column breakdown grid
    - Total Due
    - Total Paid
    - Each payment type (if > 0)
    ↓
Insert HTML into job details view
```

---

## API Reference

### New/Modified AJAX Endpoints

#### upload_job_image (Modified)
**Action**: `wp_ajax_upload_job_image`
**Handler**: `WP_Staff_Diary_Images_Controller::upload()`
**Method**: POST
**Parameters**:
- `nonce` (string, required) - WordPress nonce for security
- `diary_entry_id` (int, required) - Job entry ID
- `image` (file, required) - Image file to upload
- `caption` (string, optional) - Photo caption
- `category` (string, optional, default: 'general') - Photo category

**Valid Categories**: 'before', 'during', 'after', 'general'

**Response Success**:
```json
{
    "success": true,
    "data": {
        "message": "Image uploaded successfully",
        "image": {
            "id": 123,
            "diary_entry_id": 456,
            "image_url": "http://...",
            "attachment_id": 789,
            "image_caption": "Caption text",
            "image_category": "before",
            "uploaded_at": "2024-01-01 12:00:00"
        }
    }
}
```

**Response Error**:
```json
{
    "success": false,
    "data": {
        "message": "Error message here"
    }
}
```

### New Repository Methods

#### WP_Staff_Diary_Images_Repository::get_entry_images_by_category()
**Purpose**: Get images for a specific job filtered by category
**Parameters**:
- `$diary_entry_id` (int) - Job entry ID
- `$category` (string) - Category to filter by
**Returns**: Array of image objects
**Example**:
```php
$beforeImages = $repository->get_entry_images_by_category($entry_id, 'before');
```

#### WP_Staff_Diary_Images_Repository::get_entry_images_grouped()
**Purpose**: Get all images grouped by category
**Parameters**:
- `$diary_entry_id` (int) - Job entry ID
**Returns**: Associative array with category keys
**Example**:
```php
$grouped = $repository->get_entry_images_grouped($entry_id);
// Returns:
// [
//     'before' => [image1, image2],
//     'during' => [image3],
//     'after' => [image4, image5, image6],
//     'general' => []
// ]
```

---

## Migration & Upgrade Notes

### Upgrading from v2.5.0 to v2.6.0

**Database Changes**: None required - column already exists via upgrade script

**Existing Data**:
- All existing photos will have `image_category = 'general'`
- They will display with gray "General" badge
- Users can upload new photos with specific categories
- No data migration needed

**WordPress Requirements**:
- WordPress 5.0+ (no change)
- PHP 7.4+ (no change)
- MySQL 5.6+ (no change)

**JavaScript Dependencies**:
- jQuery (already required by WordPress)
- No new dependencies added

**Breaking Changes**: None

**Deprecations**: None

---

## Rollback Procedure

### If Issues Found in Production

**Option 1: Revert to v2.5.0**
```bash
git checkout restore-v2.6.0  # Or whatever tag/branch v2.5.0 is on
# Upload files to server
# Database columns remain (harmless)
```

**Option 2: Disable Photo Categories (Temporary)**
```javascript
// In admin.js, comment out category modal:
// showPhotoCategoryModal(file, entryId, function(result) {
//     ...
// });

// Replace with direct upload:
const formData = new FormData();
formData.append('action', 'upload_job_image');
formData.append('nonce', wpStaffDiary.nonce);
formData.append('diary_entry_id', entryId);
formData.append('image', file);
formData.append('category', 'general');  // Always general

$.ajax({
    url: wpStaffDiary.ajaxUrl,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if (response.success) {
            alert('Photo uploaded successfully!');
            viewEntryDetails(entryId);
        }
    }
});
```

**Option 3: Hide Payment Progress (Temporary)**
```javascript
// In admin.js line 972, add return:
// Payment Progress Visualization
if (entry.total > 0) {
    return;  // TEMPORARY: Disable payment progress
    // ... rest of code
}
```

---

## Future Development Notes

### Phase 2 Planning (Not Yet Implemented)

**Email Template Builder**:
- Visual editor for payment reminders
- Variable placeholders ({{customer_name}}, {{balance}}, etc.)
- Preview before sending
- Save templates to database

**SMS Notifications**:
- Twilio integration
- SMS templates
- Opt-in/opt-out management
- Cost tracking

### Phase 3 Planning (Not Yet Implemented)

**Customer Portal**:
- Customer login with WordPress users
- View own jobs/quotes
- See payment history
- Upload photos from customer side
- Approve quotes online

**Accounting Integration**:
- QuickBooks API
- Xero API
- Sync payments automatically
- Export reports

---

## Contact & Support

### If You Need Help Understanding This Code

**Ask About**:
1. "Why was X implemented this way?" - Check Architecture Decisions section
2. "How do I add feature Y?" - Check Development Workflow section
3. "What does this function do?" - Check inline comments in code
4. "I found a bug" - Check Debugging Guide section
5. "How do I test X?" - Check Testing Checklist section

**Code Locations Quick Reference**:
- Photo upload modal: `assets/js/admin.js` lines 1139-1197
- Before/after comparison: `assets/js/admin.js` lines 1399-1530
- Payment progress: `assets/js/admin.js` lines 972-1030
- Image repository: `includes/modules/images/class-images-repository.php`
- Image controller: `includes/modules/images/class-images-controller.php`
- XSS protection: `assets/js/admin.js` line 25 (escapeHtml function)

**Critical Functions**:
- `showPhotoCategoryModal()` - Photo category selection modal
- `showBeforeAfterComparison()` - Before/after comparison view
- `escapeHtml()` - XSS protection utility
- `closeComparisonModal()` - Modal cleanup with event unbinding

---

## Version History

### v2.6.0 (Current)
- Before/After Photo Gallery System
- Payment Progress Visualization
- Critical security fixes (XSS)
- Memory leak fixes
- Edge case handling

### v2.5.0 (Previous)
- Job Type Selection (Residential/Commercial)
- Payment Terms System
- Payment Policy Settings
- Overdue Notifications
- Enhanced Bank Details
- Overdue Dashboard Widget

### Earlier Versions
- See git history for full changelog

---

## End of Development Log

**Last Updated**: Current session
**Branch**: `claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM`
**Status**: Ready for testing & deployment
**Next Steps**: Manual testing checklist, then merge to master
