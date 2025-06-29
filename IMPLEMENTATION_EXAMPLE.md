# Implementation Example

Ini adalah contoh lengkap bagaimana menggunakan package ApiResourceTyper dalam project Laravel.

## 1. Installation di Project Laravel

```bash
# Install package
composer require andrazero121/api-resource-typer

# Publish config
php artisan vendor:publish --provider="Andrazero121\ApiResourceTyper\Providers\ApiResourceTyperServiceProvider" --tag="config"
```

## 2. Setup Model dan Resource

```php
<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'tanggal_lahir'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'email_verified_at' => 'datetime',
    ];
}
```

```php
<?php
// app/Http/Resources/UserResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->name,
            'email' => $this->email,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## 3. Setup Controller dengan Trait

```php
<?php
// app/Http/Controllers/Api/UserController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Andrazero121\ApiResourceTyper\Traits\ApiResourceTyper;

class UserController extends Controller
{
    use ApiResourceTyper;

    public function index()
    {
        $users = User::paginate();

        // Ini akan generate UserType.ts otomatis!
        return $this->responseWithTypes(UserResource::collection($users));
    }

    public function show(User $user)
    {
        return $this->responseWithTypes(new UserResource($user));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'tanggal_lahir' => 'nullable|date',
        ]);

        $user = User::create([
            'name' => $request->nama,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'tanggal_lahir' => $request->tanggal_lahir,
        ]);

        return $this->responseWithTypes(new UserResource($user), 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'nama' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'tanggal_lahir' => 'nullable|date',
        ]);

        $user->update([
            'name' => $request->nama ?? $user->name,
            'email' => $request->email ?? $user->email,
            'tanggal_lahir' => $request->tanggal_lahir,
        ]);

        return $this->responseWithTypes(new UserResource($user));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
```

## 4. Setup Routes

```php
<?php
// routes/api.php
use App\Http\Controllers\Api\UserController;

Route::prefix('api')->group(function () {
    Route::apiResource('users', UserController::class);
});

// Atau dengan middleware
Route::middleware(['api', 'api-typer'])->prefix('api')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

## 5. Generated TypeScript Output

Setelah hit API endpoint, file ini akan auto-generate di `resources/js/types/UserType.ts`:

```typescript
// Auto-generated TypeScript interface
// Generated at: 2025-06-29 15:30:00
// From: App\Http\Controllers\Api\UserController

export interface UserType {
  id: number;
  nama: string;
  email: string;
  tanggal_lahir: Date;
  created_at: Date;
  updated_at: Date;
}

export interface UserTypeCollection {
  data: UserType[];
  links?: PaginationLinks;
  meta?: PaginationMeta;
}

export interface UserTypeResponse {
  data: UserType;
}

// Common pagination interfaces
interface PaginationLinks {
  first: string;
  last: string;
  prev: string | null;
  next: string | null;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}
```

## 6. Usage di Frontend (React/Vue/Angular)

```typescript
// Frontend usage example
import {
  UserType,
  UserTypeCollection,
  UserTypeResponse,
} from "@/types/UserType";

// Fetch users with pagination
const fetchUsers = async (page: number = 1): Promise<UserTypeCollection> => {
  const response = await fetch(`/api/users?page=${page}`);
  return response.json();
};

// Fetch single user
const fetchUser = async (id: number): Promise<UserType> => {
  const response = await fetch(`/api/users/${id}`);
  const data: UserTypeResponse = await response.json();
  return data.data;
};

// Create user
const createUser = async (
  userData: Omit<UserType, "id" | "created_at" | "updated_at">
): Promise<UserType> => {
  const response = await fetch("/api/users", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      nama: userData.nama,
      email: userData.email,
      password: "somepassword",
      tanggal_lahir: userData.tanggal_lahir,
    }),
  });

  const data: UserTypeResponse = await response.json();
  return data.data;
};

// React component example
import React, { useState, useEffect } from "react";

const UserList: React.FC = () => {
  const [users, setUsers] = useState<UserType[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    fetchUsers().then((response) => {
      setUsers(response.data);
      setLoading(false);
    });
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <h1>Users</h1>
      {users.map((user) => (
        <div key={user.id}>
          <h3>{user.nama}</h3>
          <p>{user.email}</p>
          <p>Born: {new Date(user.tanggal_lahir).toLocaleDateString()}</p>
        </div>
      ))}
    </div>
  );
};
```

## 7. Manual Generation Command

```bash
# Generate for all models
php artisan generate:api-types

# Generate for specific model
php artisan generate:api-types --model=User

# Output akan ada di resources/js/types/
```

## 8. Postman Testing

Sekarang response di Postman akan auto-complete karena TypeScript types sudah ter-generate:

```json
{
  "data": [
    {
      "id": 1,
      "nama": "John Doe",
      "email": "john@example.com",
      "tanggal_lahir": "1990-01-01",
      "created_at": "2025-06-29T15:30:00.000000Z",
      "updated_at": "2025-06-29T15:30:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/users?page=1",
    "last": "http://localhost/api/users?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

Frontend developer tinggal import types dan langsung tau struktur response-nya! 🎉
