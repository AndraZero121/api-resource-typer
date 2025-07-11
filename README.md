# API Resource Typer

🚀 Automatically generate TypeScript or JavaScript interfaces from your Laravel API Resource Controllers!

## Features

- ✅ Auto-generate TypeScript or JavaScript interfaces from API responses
- ✅ Support for Laravel Resource and ResourceCollection
- ✅ Smart type inference from actual data
- ✅ Artisan command for manual generation with output type selection
- ✅ Middleware for automatic generation
- ✅ Trait for easy controller integration
- ✅ Configurable type mappings
- ✅ Pagination support

## Installation

```bash
composer require andrazero121/api-resource-typer
```

Publish the config file:

```bash
php artisan vendor:publish --provider="AndraZero121\ApiResourceTyper\Providers\ApiResourceTyperServiceProvider" --tag=api-resource-typer-config
php artisan vendor:publish --provider="AndraZero121\ApiResourceTyper\Providers\ApiResourceTyperServiceProvider" --tag=api-resource-typer-extension
```

## Usage

### Method 1: Using Trait

Add the trait to your API controllers:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use AndraZero121\ApiResourceTyper\Traits\ApiResourceTyper;

class UserController extends Controller
{
    use ApiResourceTyper;

    public function index()
    {
        $users = User::paginate();
        return $this->responseWithTypes(UserResource::collection($users));
    }

    public function show(User $user)
    {
        return $this->responseWithTypes(new UserResource($user));
    }
}
```

### Method 2: Using Middleware

Add middleware to your API routes:

```php
// routes/api.php
Route::middleware(['api', 'api-typer'])->group(function () {
    Route::apiResource('users', UserController::class);
});
```

### Method 3: Manual Generation (Recommended)

Generate types manually using Artisan command:

```bash
# Generate for all models as TypeScript (default)
php artisan generate:api-types

# Generate for all models as JavaScript JSDoc
printf artisan generate:api-types --output-type=js

# Generate for specific model
php artisan generate:api-types --model=User --output-type=ts
```

## Generated Output

The package will generate TypeScript or JSDoc interfaces like this:

```typescript
// TypeScript
export interface UserResource {
  id: number;
  name: string;
  email: string;
  created_at: Date;
  updated_at: Date;
}

// JavaScript JSDoc
/**
 * @typedef {Object} UserResource
 * @property {number} id
 * @property {string} name
 * ...
 */
```

## Configuration

Edit `config/api-resource-typer.php` for output path, type mappings, and excluded columns.

## Custom Extension

You can add your own helper or type modifier in `app/ApiResourceTyperExtension.php` after publishing the extension file.

## Requirements

- PHP 8.2+
- Laravel 11.x+

## License

MIT
