<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Role constants
     */
    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if user is a superadmin
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->roleModel && $this->roleModel->name === 'superadmin';
    }

    /**
     * Check if user is an admin (including superadmin)
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->roleModel && in_array($this->roleModel->name, ['superadmin', 'admin']);
    }

    /**
     * Check if user is admin or superadmin (can edit)
     *
     * @return bool
     */
    public function canEdit()
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Check if user can delete
     *
     * @return bool
     */
    public function canDelete()
    {
        return $this->isSuperAdmin();
    }

    /**
     * Get the role that belongs to the user
     */
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission)
    {
        // Superadmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check through role
        if ($this->roleModel) {
            return $this->roleModel->hasPermission($permission);
        }

        return false;
    }

    /**
     * Get all permissions for this user
     */
    public function getAllPermissions()
    {
        if ($this->isSuperAdmin()) {
            return Permission::active()->get();
        }

        if ($this->roleModel) {
            return $this->roleModel->permissions()->where('is_active', true)->get();
        }

        return collect();
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Check if user uses username for login (not superadmin)
     */
    public function usesUsername()
    {
        return !$this->isSuperAdmin() && !empty($this->username);
    }

    /**
     * Check if user uses email for login (superadmin)
     */
    public function usesEmail()
    {
        return $this->isSuperAdmin() || empty($this->username);
    }

    /**
     * Get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
}
