<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\OrganizationUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_system_admin',
        'is_technician',
        'locale',
        'timezone',
        'last_login_at',
        'last_login_ip',
        'notify_via_mail',
        'notify_via_whatsapp',
        'whatsapp_phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Used by notification classes to decide whether to dispatch on a channel.
     */
    public function wantsChannel(string $channel): bool
    {
        return match ($channel) {
            'mail' => (bool) ($this->notify_via_mail ?? true),
            'whatsapp' => (bool) ($this->notify_via_whatsapp ?? false),
            'database', 'broadcast' => true,
            default => false,
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_system_admin' => 'boolean',
            'is_technician' => 'boolean',
            'notify_via_mail' => 'boolean',
            'notify_via_whatsapp' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_system_admin', 'is_technician', 'locale', 'timezone'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationUser::class)
            ->withPivot('default_role', 'joined_at', 'is_default')
            ->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function getFilamentName(): string
    {
        return (string) $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'system' => (bool) $this->is_system_admin,
            'technician' => (bool) $this->is_technician || (bool) $this->is_system_admin,
            'app' => $this->organizations()->exists() || (bool) $this->is_system_admin,
            'portal' => $this->organizations()->exists() || (bool) $this->is_system_admin,
            default => false,
        };
    }
}
