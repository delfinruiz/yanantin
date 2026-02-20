<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use App\Models\EmailAccount;

use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;
use Wirechat\Wirechat\Panel;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne as RelationsHasOne;

class User extends Authenticatable implements HasAvatar, WirechatUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithWirechat;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'is_internal',
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
            'is_internal' => 'boolean',
        ];
    }

    public function medicalLicenses()
    {
        return $this->hasMany(MedicalLicense::class);
    }

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            $hasEmailAccount = $user->emailAccount()->exists();
            
            if (($user->is_internal || $hasEmailAccount) && !$user->employeeProfile()->exists()) {
                if ($hasEmailAccount && !$user->is_internal) {
                    $user->is_internal = true;
                    $user->saveQuietly();
                }
                $user->employeeProfile()->create([]);
            }
        });
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');
        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }

    /**
     * Get Wirechat name
     */
    public function getWirechatNameAttribute(): ?string
    {
        return $this->name;
    }

    /**
     * Get Wirechat avatar URL
     */
    public function getWirechatAvatarUrlAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get Wirechat profile URL
     */
    public function getWirechatProfileUrlAttribute(): ?string
    {
        // Return null to disable profile links for now, or add a route if available
        return null;
    }

    public function canCreateGroups(): bool
    {
        return true;
    }

    public function canCreateChats(): bool
    {
        return true;
    }

    public function sharedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user');
    }

    public function emailAccount()
    {
        return $this->hasOne(EmailAccount::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function supervisedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_supervisor');
    }

    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'survey_user')->withTimestamps();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function hasVerifiedEmail()
    {
        if ($this->emailAccount()->exists()) {
            return true;
        }

        return $this->email_verified_at !== null;
    }

    public function canAccessWirechatPanel(Panel $panel): bool
    {
        return true;
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function birthdayGreeting(): RelationsHasOne
    {
        return $this->hasOne(BirthdayGreeting::class);
    }
}
