# Islami Dawa Tools

A comprehensive toolkit for Islamic Dawa (outreach) with Elementor integration, custom widgets, and enhanced form features.

## Features

### Gravity Forms Integration

- **Multi-Currency Support**: Built-in support for various currencies including:
  - **BDT (Bangladeshi Taka)** - ৳ with proper formatting
  - All standard Gravity Forms currencies

#### BDT Currency Details
- **Symbol**: ৳ (Bengali Taka Sign)
- **Code**: BDT
- **Decimal Places**: 2
- **Thousand Separator**: Comma (,)
- **Decimal Separator**: Period (.)

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/islami-dawa-tools/`
3. Run `composer install` to install dependencies
4. Activate the plugin in WordPress admin

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Gravity Forms (for form-related features)
- Composer

## Structure

```
islami-dawa-tools/
├── Admin/                    # Admin functionality
│   └── AdminManager.php      # Admin manager class
├── Frontend/                 # Frontend functionality
│   ├── Frontend.php          # Frontend manager class
│   └── GravityForms/         # Gravity Forms integration
│       └── GravityForms.php  # BDT currency and GF features
├── Inc/                      # Core plugin files
│   ├── Activate.php          # Activation hooks
│   ├── Deactivate.php        # Deactivation hooks
│   └── Manager.php           # Main manager class
├── languages/                # Localization files
├── composer.json             # PHP dependencies
├── package.json              # Node dependencies
└── islami-dawa-tools.php     # Main plugin file
```

## Development

### Running Code Quality Checks

```bash
# Check WordPress coding standards
composer run-script lint:wpcs

# Check PHP syntax
composer run-script lint:php

# Auto-fix coding standards
composer run-script lint:autofix

# Generate POT translation file
composer run-script make-pot
```

### Building Release Package

```bash
npm run bundle
```

## Author

**PairDevs**
- GitHub: [https://github.com/PairDevs](https://github.com/PairDevs)

## License

This plugin is licensed under the GPL2 License - see the [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for more details.

## Changelog

### Version 1.0.0
- Initial release
- Gravity Forms with BDT currency support
- Professional plugin structure following WordPress standards

## Support

For support and feature requests, visit the [GitHub repository](https://github.com/PairDevs/islami-dawa-tools).
