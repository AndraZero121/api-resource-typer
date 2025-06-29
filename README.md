# API Resource Typer

🚀 Automatically generate TypeScript interfaces from your Laravel API Resource Controllers!

## Features

- ✅ Auto-generate TypeScript interfaces from API responses
- ✅ Support for Laravel Resource and ResourceCollection
- ✅ Smart type inference from actual data
- ✅ Artisan command for manual generation
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
php artisan vendor:publish --provider="Andrazero121\ApiResourceTyper\Providers\ApiResourceTyperServiceProvider" --tag="config"
```

## Usage

### Method 1: Using Trait (Recommended)

Add the trait to your API controllers:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Andrazero121\ApiResourceTyper\Traits\ApiResourceTyper;

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

### Method 3: Manual Generation

Generate types manually using Artisan command:

```bash
# Generate for all models
php artisan generate:api-types

# Generate for specific model
php artisan generate:api-types --model=User
```

## Generated Output

The package will generate TypeScript interfaces like this:

```typescript
// Auto-generated TypeScript interface
// Generated at: 2025-06-29 10:30:00

export interface UserType {
  id: number;
  name: string;
  email: string;
  created_at: Date;
  updated_at: Date;
}

export interface UserTypeResponse {
  data: UserType;
}

export interface UserTypeCollection {
  data: UserType[];
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
```

## Configuration

Edit `config/api-resource-typer.php`:

```php
return [
    // Output directory for TypeScript files
    'output_path' => resource_path('js/types'),

    // Auto-generate on API responses (debug mode only)
    'auto_generate' => env('API_RESOURCE_TYPER_AUTO_GENERATE', true),

    // Type mappings
    'type_mappings' => [
        'string' => 'string',
        'integer' => 'number',
        'boolean' => 'boolean',
        'datetime' => 'Date',
        // ... more mappings
    ],

    // Columns to exclude
    'exclude_columns' => [
        'password',
        'remember_token',
    ],
];
```

## Environment Variables

Add to your `.env`:

```env
# Enable/disable auto-generation
API_RESOURCE_TYPER_AUTO_GENERATE=true
```

## Frontend Usage

Import and use the generated types in your frontend:

```typescript
// React/Vue/Angular example
import { UserType, UserTypeCollection } from "@/types/UserType";

const fetchUsers = async (): Promise<UserTypeCollection> => {
  const response = await fetch("/api/users");
  return response.json();
};

const fetchUser = async (id: number): Promise<UserType> => {
  const response = await fetch(`/api/users/${id}`);
  const data = await response.json();
  return data.data; // Laravel Resource wraps data
};
```

## Requirements

- PHP 8.0+
- Laravel 8.0+

## Contributing

Pull requests are welcome! For major changes, please open an issue first.

## License

MIT
