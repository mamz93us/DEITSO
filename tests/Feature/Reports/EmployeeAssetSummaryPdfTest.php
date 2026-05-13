<?php

declare(strict_types=1);

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\CreateAsset;
use App\Actions\Employees\CreateEmployee;
use App\Models\AssetCategory;
use App\Models\Organization;
use App\Services\Reports\GenerateEmployeeAssetSummaryPdf;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('renders a PDF listing each asset assigned to the employee', function () {
    $org = Organization::create(['slug' => 'sumco', 'name' => ['en' => 'Sum'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'L'],
        'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);

    $emp = app(CreateEmployee::class)($org->id, [
        'email' => 'pdf@sumco.test',
        'first_name' => 'Alice',
        'last_name' => 'Smith',
    ])['employee'];

    $a1 = app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'serial_number' => 'SN-A']);
    $a2 = app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'serial_number' => 'SN-B']);
    app(AssignAssetToEmployee::class)($a1, $emp);
    app(AssignAssetToEmployee::class)($a2, $emp);

    $pdf = app(GenerateEmployeeAssetSummaryPdf::class)($emp->fresh(['organization']));
    $bytes = $pdf->output();

    expect(substr($bytes, 0, 4))->toBe('%PDF')
        ->and(strlen($bytes))->toBeGreaterThan(2000);
});
