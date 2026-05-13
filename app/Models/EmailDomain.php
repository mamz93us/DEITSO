<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailDomain extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['mail_server_id', 'organization_id', 'domain', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function mailServer(): BelongsTo
    {
        return $this->belongsTo(MailServer::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }
}
