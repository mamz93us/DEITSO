<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class OrganizationBranding extends Model
{
    use HasTranslations;
    use LogsActivity;

    protected $primaryKey = 'organization_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'email_header_html',
        'email_footer_html',
        'pdf_header_html',
        'pdf_footer_html',
        'portal_welcome_text',
    ];

    public array $translatable = ['portal_welcome_text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
