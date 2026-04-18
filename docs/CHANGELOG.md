# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Framework Authentication Flow:
  - Added `Authenticatable` trait for User models with `login()`, `logout()`, and `user()` methods
  - Added `AuthManager` for login/logout state management with Gate integration

- Navigation System:
  - Added `NavigationRenderer` and `NavigationViewExtension` for first-class menu rendering in Twig

- Permission Middleware:
  - Added `PermissionMiddleware` for route-level permission checks

- Config Merging:
  - Added `ConfigMerger` for additive config list merging

- Module Enhancements:
  - Added module dependency validation
  - Module seeder support (`module:seed` command)
  - Manifest-driven view namespace registration (fixed lazy-load for Twig extensions)

- Database:
  - Added DBForge for database management

- Security:
  - Added security risk logging and reporting (`security:report` command)
  - Added Kafka consumer command and notification channel
  - Added configurable session savePath with framework-managed default

- Documentation improvements:
  - Getting Started section with installation and project structure guides
  - Quick Start guide with Mermaid diagrams
  - Middleware reference
  - Events reference
  - Configuration reference
  - Console commands overview
  - Deployment guide
  - Testing guide
  - Troubleshooting guide
  - Architecture section with boot flow and design docs

### Refactored
- Validation System Refactoring:
  - Extracted validation rules into modular `ValidationRule/` directory structure
  - Created `RuleRegistry` class for centralized rule management
  - Extracted helper classes to `Validation/Helpers/` for better separation of concerns
  - Reduced `RequestValidator.php` from 687 lines to 245 lines (64% reduction)
  - Added support for custom validation rules via:
    - Constructor injection of custom rule classes
    - `validateInputWithCustomRules()` method for runtime custom rules
  - 27 individual rule classes organized by category:
    - TypeRules (14): required, present, filled, string, integer, numeric, boolean, array, email, url, file, image, accepted, declined
    - ComparisonRules (6): min, max, between, in, same, confirmed
    - DateRules (3): date, date_format, regex
    - TransformRules (4): trim, lowercase, uppercase, default

- Helpers Refactoring:
  - Extracted helper functions into modular `Helpers/` directory
  - `Helpers.php` reduced from 544 lines to 16 lines (re-exports only)
  - 8 new helper files organized by category:
    - Paths.php (90 lines): path_*() functions
    - Container.php (48 lines): app(), config(), cache(), storage(), db()
    - SessionRequest.php (69 lines): env(), session(), request()
    - Services.php (76 lines): event(), logger(), mailer(), router(), http(), etc.
    - Security.php (59 lines): security(), csrf_*(), throttle(), etc.
    - ValidationResponse.php (55 lines): validate_request(), old(), response()
    - ViewDebug.php (67 lines): view(), image(), debugger(), etc.
    - Utilities.php (67 lines): generate_key(), with(), tap(), dd()
  - 100% backward compatible

## [1.0.0] - 2026-04-10

### Added
- Initial release
- HTTP kernel with middleware pipeline
- Console application
- Database integration via marwa-db
- MVC architecture with controllers and models
- Template rendering with Twig
- Session management
- Security middleware
- Event system
- Notification system
- Mail system
- HTTP client
- File storage
- Cache system
- Logging
- Validation
- Security features

### Features
- Service container with dependency injection
- Service providers
- Module system
- Debug bar
- CLI commands
- Database migrations
- Database seeding
- DBForge for database management

### Documentation
- Quick Start guide
- Tutorial guides for all major features
- API reference for all components
- Developer guides for extensibility