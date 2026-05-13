<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SupplierResource\Pages;

use App\Filament\App\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateSupplier extends CreateRecord
{
    use Translatable;

    protected static string $resource = SupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate SUP-0001 code if the user left it blank — but the
        // form requires a code, so this branch is rarely hit. Defensive.
        if (empty($data['code'])) {
            $orgId = app('current.organization')?->id;
            if ($orgId) {
                $data['code'] = app(OrganizationScopedCodeGenerator::class)->next(
                    Supplier::class,
                    $orgId,
                    prefix: 'SUP',
                    padding: 4,
                );
            }
        }

        return $data;
    }
}
