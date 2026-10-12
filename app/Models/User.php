<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\GoogleCalendarToken;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'permission_overrides',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permission_overrides' => 'array',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function allowsRecord(string $permission): bool
    {
        if (! in_array(
            $permission,
            config('access.permissions'),
            true
        )) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        $overrides = $this->permission_overrides ?? [];

        if (array_key_exists($permission, $overrides)) {
            return $overrides[$permission] === true;
        }

        return $this->hasPermissionTo($permission, 'web');
    }

    public function googleCalendarToken(): HasOne
    {
        return $this->hasOne(GoogleCalendarToken::class);
    }
}
