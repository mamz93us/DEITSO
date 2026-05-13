<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AssetScrap extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;

    public const REASON_END_OF_LIFE = 'end_of_life';

    public const REASON_DAMAGED = 'damaged';

    public const REASON_LOST = 'lost';

    public const REASON_SOLD = 'sold';

    public const REASON_DONATED = 'donated';

    public const REASON_OTHER = 'other';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'organization_id',
        'reason',
        'disposal_method',
        'residual_value_minor',
        'currency',
        'approved_by_user_id',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'residual_value_minor' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        // Photos, certificates of destruction, etc.
        $this->addMediaCollection('evidence');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
