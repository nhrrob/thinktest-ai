<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user has super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if the user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user has demo role.
     */
    public function isDemo(): bool
    {
        return $this->hasRole('demo');
    }

    /**
     * Check if the user has regular user role.
     */
    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Get the user's primary role name.
     */
    public function getPrimaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this->can('access admin panel');
    }

    /**
     * Check if user can generate tests.
     */
    public function canGenerateTests(): bool
    {
        return $this->can('generate tests') || $this->can('limited test generation');
    }

    /**
     * Get all permission groups.
     */
    public static function getPermissionGroups()
    {
        $permissionGroups = DB::table('permissions')
            ->select('group_name')
            ->groupBy('group_name')
            ->get();
        return $permissionGroups;
    }

    /**
     * Get permissions by group name.
     */
    public static function getPermissionsByGroupName($groupName)
    {
        $permissions = DB::table('permissions')
            ->where('group_name', '=', $groupName)
            ->get();
        return $permissions;
    }

    /**
     * Check if role has all specified permissions.
     */
    public static function roleHasPermissions($role, $permissions)
    {
        $hasPermission = true;
        foreach ($permissions as $permission) {
            if (!$role->hasPermissionTo($permission->name)) {
                $hasPermission = false;
                return $hasPermission;
            }
        }
        return $hasPermission;
    }
}
