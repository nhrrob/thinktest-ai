# Laravel Database Guidelines

## Migration Standards

### Migration Naming Conventions
- Use descriptive, action-based names
- Follow Laravel's timestamp prefix format
- Use snake_case for migration names

```php
// Good examples
2024_01_01_000000_create_users_table.php
2024_01_01_000001_add_email_verified_at_to_users_table.php
2024_01_01_000002_create_posts_table.php
2024_01_01_000003_add_foreign_keys_to_posts_table.php
```

### Migration Structure
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

### Column Standards
- Use appropriate data types
- Add indexes for frequently queried columns
- Use foreign key constraints
- Include soft deletes when appropriate
- Always include timestamps unless specifically not needed

## Model Standards

### Model Structure
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    // Accessors & Mutators
    public function getExcerptAttribute(): string
    {
        return Str::limit($this->content, 100);
    }
}
```

### Model Conventions
- Use singular names for models (User, Post, Comment)
- Define fillable or guarded properties
- Use appropriate casts for data types
- Define relationships with proper return types
- Use scopes for common query patterns

## Seeder Standards

### DatabaseSeeder Structure
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            PostSeeder::class,
        ]);
    }
}
```

### Role and Permission Seeding
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $developerRole = Role::create(['name' => 'developer']);
        $developerRole->givePermissionTo([
            'view users', 'view posts', 'create posts', 'edit posts'
        ]);

        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo(['view posts']);

        $demoRole = Role::create(['name' => 'demo']);
        $demoRole->givePermissionTo(['view posts']);
    }
}
```

### User Seeding
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default users
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin'
            ],
            [
                'name' => 'Developer User',
                'email' => 'developer@example.com',
                'password' => Hash::make('password'),
                'role' => 'developer'
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user'
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => Hash::make('password'),
                'role' => 'demo'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
            ]);

            $user->assignRole($userData['role']);
        }

        // Create additional test users
        User::factory(10)->create()->each(function ($user) {
            $user->assignRole('user');
        });
    }
}
```

## Factory Standards

### Factory Structure
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'user_id' => User::factory(),
            'is_published' => $this->faker->boolean(70),
            'published_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
```

## Query Optimization

### Eager Loading
```php
// Good - Eager load relationships
$posts = Post::with(['user', 'comments'])->get();

// Bad - N+1 query problem
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // This creates N+1 queries
}
```

### Query Scopes
```php
// In Model
public function scopePublished($query)
{
    return $query->where('is_published', true);
}

public function scopeByUser($query, User $user)
{
    return $query->where('user_id', $user->id);
}

// Usage
$posts = Post::published()->byUser($user)->get();
```

### Database Indexes
```php
// In migration
$table->index('user_id');
$table->index(['is_published', 'published_at']);
$table->unique(['user_id', 'slug']);
```

## Relationship Standards

### Relationship Types
```php
// One-to-Many
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}

// Many-to-One
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Many-to-Many
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}

// One-to-One
public function profile(): HasOne
{
    return $this->hasOne(Profile::class);
}
```

### Polymorphic Relationships
```php
// Polymorphic One-to-Many
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}

// Inverse Polymorphic
public function commentable(): MorphTo
{
    return $this->morphTo();
}
```

---

*These database guidelines ensure consistent, efficient, and maintainable database operations across all Laravel projects.*
