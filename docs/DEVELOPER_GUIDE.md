# Islami Dawa Tools - Developer Guide

## Overview

Islami Dawa Tools is a professional WordPress plugin built with a scalable architecture following WordPress and PHP best practices.

## Project Structure

```
islami-dawa-tools/
├── Admin/                          # Admin-specific functionality
│   ├── AdminManager.php            # Main admin manager class
│   ├── index.php                   # Security file (silence is golden)
│   └── Assets/                     # Admin assets (future)
├── Frontend/                       # Frontend-specific functionality
│   ├── Frontend.php                # Main frontend manager class
│   ├── index.php                   # Security file
│   └── GravityForms/               # Gravity Forms integration module
│       ├── GravityForms.php        # GF features (BDT currency)
│       └── index.php               # Security file
├── Inc/                            # Core plugin files
│   ├── Activate.php                # Plugin activation hooks
│   ├── Deactivate.php              # Plugin deactivation hooks
│   ├── Manager.php                 # Main plugin manager
│   └── index.php                   # Security file
├── languages/                      # Translation files
├── assets/                         # Frontend assets (future)
├── vendor/                         # Composer dependencies
├── .distignore                     # Files to exclude from distribution
├── .gitignore                      # Git ignore file
├── .php-version                    # PHP version requirement
├── composer.json                   # PHP dependencies
├── package.json                    # Node dependencies
├── islami-dawa-tools.php           # Main plugin file
├── index.php                       # Security file
├── README.md                       # Public documentation
├── readme.txt                      # WordPress plugin header
└── DEVELOPER_GUIDE.md              # This file
```

## Architecture

### Singleton Pattern

The main plugin uses a singleton pattern to ensure only one instance exists:

```php
final class IslamiDawaTools {
    private static $instance = null;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### PSR-4 Autoloading

The plugin uses PSR-4 autoloading via Composer. Namespace mapping:

- `IslamiDawaTools\` → `Inc/`
- `IslamiDawaTools\Frontend\` → `Frontend/`
- `IslamiDawaTools\Admin\` → `Admin/`

## Core Classes

### IslamiDawaTools (Main Class)

**File:** `islami-dawa-tools.php`

The main plugin class that:
- Defines plugin constants
- Includes necessary files and autoloaders
- Registers hooks for initialization
- Manages activation/deactivation

**Methods:**
- `get_instance()` - Get singleton instance
- `define_constants()` - Define plugin constants
- `include_files()` - Include vendor autoloader
- `init_hooks()` - Register WordPress hooks
- `register_textdomain()` - Load translation files
- `init()` - Initialize plugin manager
- `activate()` - Handle plugin activation
- `deactivate()` - Handle plugin deactivation

### Manager Class

**File:** `Inc/Manager.php`

Initializes all plugin modules:
- Admin Manager
- Frontend Manager

```php
public function init() {
    $this->Admin_Manager = new AdminManager();
    $this->Frontend = new Frontend();
}
```

### Frontend Class

**File:** `Frontend/Frontend.php`

Manages frontend functionality and modules:
- Gravity Forms integration
- Frontend scripts/styles

```php
private function init_modules() {
    $this->gravity_forms = new GravityForms();
}
```

### AdminManager Class

**File:** `Admin/AdminManager.php`

Manages admin functionality:
- Admin scripts/styles
- Admin pages (future)
- Settings (future)

## Features

### Gravity Forms BDT Currency Support

**Location:** `Frontend/GravityForms/GravityForms.php`

Adds Bangladeshi Taka (BDT) currency to Gravity Forms with:
- Bengali Taka symbol (৳)
- Proper formatting
- 2 decimal places
- Comma thousand separator

#### Technical Details

```php
public function add_bdt_currency($currencies) {
    $currencies['BDT'] = [
        'name'               => __('Bangladeshi Taka', 'islami-dawa-tools'),
        'symbol_left'        => '৳ ',
        'symbol_right'       => '',
        'symbol_padding'     => ' ',
        'thousand_separator' => ',',
        'decimal_separator'  => '.',
        'decimals'           => 2,
    ];
    return $currencies;
}
```

## Development Workflow

### 1. Setting Up Development Environment

```bash
# Install Composer dependencies
composer install

# Install Node dependencies (for build scripts)
npm install
```

### 2. Code Quality

```bash
# Run WordPress coding standards check
composer run-script lint:wpcs

# Check PHP syntax
composer run-script lint:php

# Auto-fix code style issues
composer run-script lint:autofix
```

### 3. Translations

```bash
# Generate/update POT file
composer run-script make-pot
```

### 4. Building Release

```bash
# Create distributable ZIP file
npm run bundle
```

## Adding New Features

### Creating a New Module

1. **Create folder structure:**
   ```
   Frontend/NewFeature/
   ├── NewFeature.php
   └── index.php
   ```

2. **Create the module class:**
   ```php
   namespace IslamiDawaTools\Frontend\NewFeature;
   
   class NewFeature {
       public function __construct() {
           $this->init_hooks();
       }
       
       private function init_hooks() {
           // Add your hooks here
       }
   }
   ```

3. **Import in parent module:**
   ```php
   use IslamiDawaTools\Frontend\NewFeature\NewFeature;
   
   private function init_modules() {
       $this->gravity_forms = new GravityForms();
       $this->new_feature = new NewFeature();
   }
   ```

## Hooks and Filters

### Plugin Activation/Deactivation

- `islami_dawa_tools_activated` - Fired on plugin activation
- `islami_dawa_tools_deactivated` - Fired on plugin deactivation

### Gravity Forms

- `gform_currencies` - Filter to add/modify currencies

## Best Practices

1. **Namespace Everything:** All code should be namespaced under `IslamiDawaTools\`

2. **Security:** Use proper sanitization and escaping:
   ```php
   echo esc_html($output);
   sanitize_text_field($_POST['field']);
   ```

3. **Localization:** Always use proper text domain:
   ```php
   __('Text to translate', 'islami-dawa-tools')
   _e('Text to echo', 'islami-dawa-tools')
   ```

4. **File Headers:** Include proper PHP doc comments:
   ```php
   /**
    * FileName.php
    *
    * Description.
    *
    * @package IslamiDawaTools\Namespace
    * @since 1.0.0
    */
   ```

5. **Exit Checks:** Always check for direct access:
   ```php
   if (!defined('ABSPATH')) {
       exit; // Exit if accessed directly
   }
   ```

## Constants

Available plugin constants:

- `ISLAMI_DAWA_TOOLS_VERSION` - Plugin version
- `ISLAMI_DAWA_TOOLS_FILE` - Plugin main file path
- `ISLAMI_DAWA_TOOLS_DIR` - Plugin directory path
- `ISLAMI_DAWA_TOOLS_URL` - Plugin URL
- `ISLAMI_DAWA_TOOLS_BASENAME` - Plugin basename

## Composer

### Dependencies

Current dependencies:
- `appsero/client` - For plugin analytics and updates

### Dev Dependencies

- `wp-coding-standards/wpcs` - WordPress coding standards
- `wp-cli/i18n-command` - Translation tools
- `phpcompatibility/phpcompatibility-wp` - PHP compatibility checker

### Adding New Dependencies

```bash
# Add production dependency
composer require vendor/package

# Add development dependency
composer require --dev vendor/package
```

## Testing

For now, manual testing is recommended. In the future, consider:
- PHPUnit for unit tests
- WordPress testing framework
- Integration tests

## Version Management

Current version: **1.0.0**

Version format: `MAJOR.MINOR.PATCH`

Update version in:
- `islami-dawa-tools.php` - Plugin file
- `composer.json` - Composer config
- `readme.txt` - WordPress plugin readme

## Support & Contribution

- **GitHub:** https://github.com/PairDevs/islami-dawa-tools
- **Author:** PairDevs

## License

GPL2 - See https://www.gnu.org/licenses/gpl-2.0.html

---

Last Updated: 2026-03-19
