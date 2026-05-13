<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

abstract class BaseModel extends Model
{
    use HasTranslations;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Per Spatie idiom: trait is on every model. Models that want translatable
     * columns set this property; default empty = no-op.
     *
     * @var array<int, string>
     */
    public array $translatable = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logFillable();
    }
}
