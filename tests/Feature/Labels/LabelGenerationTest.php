<?php

declare(strict_types=1);

use App\Actions\Assets\CreateAsset;
use App\Models\AssetCategory;
use App\Models\Organization;
use App\Services\Labels\GenerateAssetLabelPdf;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapAssetForLabels(string $slug = 'labelco'): array
{
    $org = Organization::create(['slug' => $slug, 'name' => ['en' => $slug], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);

    return ['org' => $org, 'category' => $cat];
}

it('renders a single-label PDF containing the asset code', function () {
    ['org' => $org, 'category' => $cat] = bootstrapAssetForLabels();
    $asset = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'serial_number' => 'SN-X-001',
        'name' => 'Test Laptop',
    ]);

    $pdf = app(GenerateAssetLabelPdf::class)->single($asset);
    $bytes = $pdf->output();

    expect(substr($bytes, 0, 4))->toBe('%PDF')
        ->and(strlen($bytes))->toBeGreaterThan(1000);
});

it('renders an A4 label sheet for multiple assets', function () {
    ['org' => $org, 'category' => $cat] = bootstrapAssetForLabels('sheetco');

    $assets = collect();
    for ($i = 0; $i < 5; $i++) {
        $assets->push(app(CreateAsset::class)($org->id, ['category_id' => $cat->id]));
    }

    $pdf = app(GenerateAssetLabelPdf::class)->sheet($assets);
    $bytes = $pdf->output();

    expect(substr($bytes, 0, 4))->toBe('%PDF')
        ->and(strlen($bytes))->toBeGreaterThan(1500);
});
