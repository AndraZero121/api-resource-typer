# Changelog

All notable changes to `api-resource-typer` will be documented in this file.

## [1.0.0] - 2025-06-29

### Added

- Initial release
- Auto-generate TypeScript interfaces from Laravel API Resource Controllers
- Support for Laravel Resource and ResourceCollection
- Smart type inference from actual response data
- Artisan command `generate:api-types` for manual generation
- Middleware `api-typer` for automatic generation on API responses
- Trait `ApiResourceTyper` for easy controller integration
- Configurable type mappings and excluded columns
- Pagination support with Laravel pagination structure
- Auto-detection of date strings and proper Date type mapping
- Support for Laravel 8.x, 9.x, 10.x, and 11.x
- Support for PHP 8.0, 8.1, 8.2, and 8.3

### Features

- Generates individual TypeScript interfaces for each model
- Creates collection and response wrapper types
- Smart type inference for arrays, objects, and primitive types
- Configurable output directory (default: `resources/js/types`)
- Excludes sensitive columns like passwords by default
- Works only in debug mode for performance
- File caching to prevent unnecessary regeneration

### Configuration Options

- `output_path`: Customize where TypeScript files are generated
- `auto_generate`: Enable/disable automatic generation
- `type_mappings`: Customize PHP to TypeScript type mappings
- `exclude_columns`: Specify columns to exclude from generation
- `models_path` and `controllers_path`: Customize source directories
