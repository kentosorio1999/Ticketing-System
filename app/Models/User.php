<?php

namespace App\Models;

use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasAvatar
{
    use HasFactory;
    use HasRoles;
    use HasSuperAdmin;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'is_active' => 'bool',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'remember_token',
    ];

    protected $fillable = [
        'unit_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'identity',
        'phone',
        'avatar_url',
        'user_level_id',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                '*',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url
            ? Storage::disk('public')->url($this->avatar_url)
            : null;
    }

    public function preferredLocale(): string
    {
        return app(\App\Settings\GeneralSettings::class)->site_locale;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'owner_id');
    }

    public function ticektResponsibility()
    {
        return $this->hasMany(Ticket::class, 'responsible_id');
    }

    /**
     * Allow all registered users to access the panel.
     * Pending users will only see the Pending Approval dashboard.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function scopeByRole($query)
    {
        if (auth()->user()?->hasRole('Admin Unit')) {
            return $query->where('users.unit_id', auth()->user()->unit_id);
        }

        return $query;
    }

    public function socialiteUsers()
    {
        return $this->hasMany(SocialiteUser::class);
    }
}