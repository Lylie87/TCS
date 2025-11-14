# Plugin Architecture Documentation

## Overview

As of version 2.1.0, the Staff Daily Job Planner plugin uses a **modular architecture** that separates concerns and allows for safer, more maintainable development.

## Why Modular Architecture?

### Problems with the Old Approach
- Single `class-admin.php` file with 1200+ lines and 34+ methods
- Changes to one feature could break another
- Difficult to test individual features
- Hard for multiple developers to work in parallel
- Code duplication across similar operations

### Benefits of the New Approach
- **Isolation**: Each module is self-contained
- **Testability**: Modules can be tested independently
- **Maintainability**: Smaller, focused files
- **Scalability**: Easy to add new features
- **Parallel Development**: Multiple developers can work on different modules
- **Reusability**: Shared code in base classes

---

## Directory Structure

```
includes/
├── interfaces/                    # Contracts that define behavior
│   ├── interface-module.php       # Module contract
│   ├── interface-controller.php   # Controller contract
│   └── interface-repository.php   # Repository contract
│
├── shared/                        # Shared utilities
│   ├── class-base-module.php      # Base class for modules
│   ├── class-base-controller.php  # Base class for controllers
│   ├── class-base-repository.php  # Base class for repositories
│   └── class-module-registry.php  # Module management
│
└── modules/                       # Feature modules
    ├── payments/
    │   ├── class-payments-module.php       # Module entry point
    │   ├── class-payments-controller.php   # Handles HTTP/AJAX
    │   └── class-payments-repository.php   # Database operations
    │
    ├── customers/
    │   ├── class-customers-module.php
    │   ├── class-customers-controller.php
    │   └── class-customers-repository.php
    │
    └── jobs/
        ├── class-jobs-module.php
        ├── class-jobs-controller.php
        └── class-jobs-repository.php
```

---

## Architecture Layers

### 1. **Module Layer** (`*-module.php`)
- **Purpose**: Coordinates the feature and registers hooks
- **Responsibilities**:
  - Initialize repository and controller
  - Register AJAX actions
  - Set up filters and actions
- **Example**: `WP_Staff_Diary_Payments_Module`

### 2. **Controller Layer** (`*-controller.php`)
- **Purpose**: Handles HTTP/AJAX requests and responses
- **Responsibilities**:
  - Verify nonces and permissions
  - Sanitize input data
  - Call repository methods
  - Send JSON responses
- **Example**: `WP_Staff_Diary_Payments_Controller`

### 3. **Repository Layer** (`*-repository.php`)
- **Purpose**: Handles ALL database operations
- **Responsibilities**:
  - CRUD operations (Create, Read, Update, Delete)
  - Complex queries
  - Data formatting
  - No business logic - pure data access
- **Example**: `WP_Staff_Diary_Payments_Repository`

---

## Creating a New Module

### Step 1: Create Repository

```php
// includes/modules/my-feature/class-my-feature-repository.php
class WP_Staff_Diary_My_Feature_Repository extends WP_Staff_Diary_Base_Repository {

    public function __construct() {
        parent::__construct('staff_diary_my_table');
    }

    // Add custom query methods
    public function get_by_user($user_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = %d",
            $user_id
        );
        return $this->wpdb->get_results($sql);
    }
}
```

### Step 2: Create Controller

```php
// includes/modules/my-feature/class-my-feature-controller.php
class WP_Staff_Diary_My_Feature_Controller extends WP_Staff_Diary_Base_Controller {

    private $repository;

    public function __construct($repository) {
        $this->repository = $repository;
    }

    public function create() {
        if (!$this->verify_request()) {
            return;
        }

        $data = $this->sanitize_data($_POST, array(
            'name' => 'text',
            'value' => 'int'
        ));

        $id = $this->repository->create($data);

        if ($id) {
            $this->send_success(array('id' => $id));
        } else {
            $this->send_error('Failed to create');
        }
    }
}
```

### Step 3: Create Module

```php
// includes/modules/my-feature/class-my-feature-module.php
class WP_Staff_Diary_My_Feature_Module extends WP_Staff_Diary_Base_Module {

    public function __construct() {
        $this->name = 'my-feature';

        $repository = new WP_Staff_Diary_My_Feature_Repository();
        $this->controller = new WP_Staff_Diary_My_Feature_Controller($repository);
    }

    public function init() {
        // Initialization code if needed
    }

    public function register_hooks($loader) {
        $this->register_ajax_action($loader, 'create_my_feature', 'create');
        $this->register_ajax_action($loader, 'delete_my_feature', 'delete');
    }
}
```

### Step 4: Register Module

Add to `includes/class-wp-staff-diary.php`:

```php
private function load_modules() {
    // Load My Feature module
    require_once WP_STAFF_DIARY_PATH . 'includes/modules/my-feature/class-my-feature-repository.php';
    require_once WP_STAFF_DIARY_PATH . 'includes/modules/my-feature/class-my-feature-controller.php';
    require_once WP_STAFF_DIARY_PATH . 'includes/modules/my-feature/class-my-feature-module.php';

    $my_feature_module = new WP_Staff_Diary_My_Feature_Module();
    $this->module_registry->register($my_feature_module);

    // ... other modules

    $this->module_registry->init_all();
}
```

---

## Best Practices

### 1. **Separation of Concerns**
- Controllers handle HTTP/AJAX only
- Repositories handle database only
- Modules coordinate between them

### 2. **Never Mix Layers**
- ❌ **Bad**: Controller doing database queries
- ✅ **Good**: Controller calling repository methods

### 3. **Use Base Classes**
- Extend `WP_Staff_Diary_Base_Repository` for standard CRUD
- Extend `WP_Staff_Diary_Base_Controller` for request handling
- Extend `WP_Staff_Diary_Base_Module` for modules

### 4. **Security First**
- Always call `verify_request()` in controllers
- Always sanitize input with `sanitize_data()`
- Always use prepared statements in repositories

### 5. **Keep Methods Small**
- Each method should do one thing
- Methods over 30 lines should be refactored
- Extract complex logic into private methods

---

## Migration Status

### ✅ Completed Modules
- **Payments Module** - Fully modular and tested

### 🔄 In Progress
- Customers Module
- Jobs Module
- Images Module
- Settings Module

### 📝 Planned
- PDF Module
- Reporting Module
- Analytics Module

---

## Backwards Compatibility

The old `class-admin.php` still exists and works alongside the new modules:

```php
// Old payment handlers (commented out)
// $this->loader->add_action('wp_ajax_add_payment', $plugin_admin, 'add_payment');

// New payment handlers (active)
// Handled by Payments Module via module registry
```

This allows gradual migration without breaking existing functionality.

---

## Testing

### Unit Testing
Each module can be tested independently:

```php
// Test repository
$repo = new WP_Staff_Diary_Payments_Repository();
$payment_id = $repo->create(['amount' => 100.00, ...]);
$payment = $repo->find_by_id($payment_id);
assert($payment->amount == 100.00);

// Test controller
$controller = new WP_Staff_Diary_Payments_Controller($repo);
$_POST = ['entry_id' => 1, 'amount' => 50.00, 'nonce' => '...'];
$controller->add();
```

---

## Future Enhancements

1. **Dependency Injection Container** - Automate object creation
2. **Service Layer** - Add business logic between controller and repository
3. **Event System** - Allow modules to communicate via events
4. **API Versioning** - Support multiple API versions
5. **Automated Testing** - PHPUnit tests for each module

---

## Questions?

For questions about the architecture or how to add new features:
1. Review this documentation
2. Look at the Payments module as a reference
3. Check inline code comments
4. Refer to the interface contracts

---

## Version History

- **2.1.0** - Initial modular architecture with Payments module
- **2.0.27** - Legacy monolithic architecture
