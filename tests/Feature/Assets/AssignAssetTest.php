<?php

declare(strict_types=1);

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\RevokeAssetFromEmployee;
use App\Actions\Employees\CreateEmployee;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Organization;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapAssetEnv(): array
{
    $org = Organization::create([
        'slug' => 'assignco',
        'name' => ['en' => 'Assign Co'],
        'status' => 'active',
    ]);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $branch = Branch::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'HQ'],
        'code' => 'HQ',
    ]);
    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);

    $asset = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'branch_id' => $branch->id,
    ]);

    $alice = app(CreateEmployee::class)($org->id, [
        'email' => 'alice@assignco.test', 'first_name' => 'Alice', 'branch_id' => $branch->id,
    ])['employee'];

    $bob = app(CreateEmployee::class)($org->id, [
        'email' => 'bob@assignco.test', 'first_name' => 'Bob', 'branch_id' => $branch->id,
    ])['employee'];

    return ['org' => $org, 'branch' => $branch, 'asset' => $asset, 'alice' => $alice, 'bob' => $bob];
}

it('assigns an asset to an employee and opens an assignment row', function () {
    ['asset' => $asset, 'alice' => $alice] = bootstrapAssetEnv();

    app(AssignAssetToEmployee::class)($asset, $alice, 'initial setup');

    $asset->refresh();
    expect($asset->assigned_employee_id)->toBe($alice->id)
        ->and($asset->status)->toBe(Asset::STATUS_DEPLOYED);

    $current = AssetAssignment::query()->where('asset_id', $asset->id)->whereNull('to_at')->first();
    expect($current)->not->toBeNull()
        ->and($current->assigned_to_type)->toBe(AssetAssignment::TYPE_EMPLOYEE)
        ->and($current->assigned_to_id)->toBe($alice->id);
});

it('closes the previous assignment when re-assigning to someone else', function () {
    ['asset' => $asset, 'alice' => $alice, 'bob' => $bob] = bootstrapAssetEnv();

    app(AssignAssetToEmployee::class)($asset, $alice);
    app(AssignAssetToEmployee::class)($asset, $bob, 'team transfer');

    $rows = AssetAssignment::query()->where('asset_id', $asset->id)->orderBy('from_at')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->to_at)->not->toBeNull()              // previous, closed
        ->and($rows[0]->assigned_to_id)->toBe($alice->id)
        ->and($rows[1]->to_at)->toBeNull()                   // new, open
        ->and($rows[1]->assigned_to_id)->toBe($bob->id);

    expect($asset->fresh()->assigned_employee_id)->toBe($bob->id);
});

it('revokes the current assignment and returns the asset to stock', function () {
    ['asset' => $asset, 'alice' => $alice] = bootstrapAssetEnv();

    app(AssignAssetToEmployee::class)($asset, $alice);
    app(RevokeAssetFromEmployee::class)($asset, 'no longer needed');

    $asset->refresh();
    expect($asset->assigned_employee_id)->toBeNull()
        ->and($asset->status)->toBe(Asset::STATUS_IN_STOCK);

    expect(AssetAssignment::query()->where('asset_id', $asset->id)->whereNull('to_at')->exists())->toBeFalse();
});

it('refuses cross-tenant assignment', function () {
    ['asset' => $asset] = bootstrapAssetEnv();

    $otherOrg = Organization::create([
        'slug' => 'other',
        'name' => ['en' => 'Other'],
        'status' => 'active',
    ]);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $otherOrg);
    $stranger = app(CreateEmployee::class)($otherOrg->id, [
        'email' => 'x@other.test', 'first_name' => 'X',
    ])['employee'];

    expect(fn () => app(AssignAssetToEmployee::class)($asset, $stranger))
        ->toThrow(RuntimeException::class, 'Cross-tenant');
});
