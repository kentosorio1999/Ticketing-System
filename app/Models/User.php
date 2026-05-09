<?php

namespace App\Models;

use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;
use App\Notifications\SendEmailVerificationCode;
use App\Settings\GeneralSettings;
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

class User extends Authenticatable implements FilamentUser, HasAvatar, HasLocalePreference
{
    use HasFactory;
    use HasRoles;
    use HasSuperAdmin;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_code_expires_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'birthdate' => 'date',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'remember_token',
    ];

    protected $fillable = [
        'unit_id',

        'first_name',
        'middle_name',
        'last_name',
        'birthdate',

        'name',
        'email',
        'email_verified_at',
        'email_verification_code',
        'email_verification_code_expires_at',

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

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            if (! $user->email_verified_at) {
                $code = (string) random_int(100000, 999999);

                $user->forceFill([
                    'email_verification_code' => $code,
                    'email_verification_code_expires_at' => now()->addMinutes(10),
                ])->saveQuietly();

                $user->notify(new SendEmailVerificationCode($code));
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
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
        return app(GeneralSettings::class)->site_locale;
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

    public function ticketResponsibility()
    {
        return $this->hasMany(Ticket::class, 'responsible_id');
    }

    // Keep this old misspelled method in case other parts of your system still use it.
    public function ticektResponsibility()
    {
        return $this->ticketResponsibility();
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