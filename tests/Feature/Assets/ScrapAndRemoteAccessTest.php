<?php

declare(strict_types=1);

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\ScrapAsset;
use App\Actions\Employees\CreateEmployee;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetRemoteAccess;
use App\Models\AssetScrap;
use App\Models\Organization;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('scraps an asset: status → scrapped, current assignment closed, scrap row written', function () {
    $org = Organization::create(['slug' => 'sc', 'name' => ['en' => 'SC'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $emp = app(CreateEmployee::class)($org->id, ['email' => 'e@sc.test', 'first_name' => 'E'])['employee'];

    app(AssignAssetToEmployee::class)($asset, $emp);

    $scrap = app(ScrapAsset::class)($asset, [
        'reason' => AssetScrap::REASON_DAMAGED,
        'disposal_method' => 'recycled via vendor',
        'residual_value_minor' => 0,
    ]);

    $asset->refresh();
    expect($asset->status)->toBe(Asset::STATUS_SCRAPPED)
        ->and($asset->assigned_employee_id)->toBeNull();

    expect($scrap)->toBeInstanceOf(AssetScrap::class)
        ->and($scrap->reason)->toBe(AssetScrap::REASON_DAMAGED)
        ->and($scrap->approved_at)->not->toBeNull();

    expect(AssetAssignment::query()->where('asset_id', $asset->id)->whereNull('to_at')->exists())->toBeFalse();
});

it('cannot scrap an already-scrapped asset', function () {
    $org = Organization::create(['slug' => 'dbl', 'name' => ['en' => 'DBL'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    app(ScrapAsset::class)($asset, []);

    expect(fn () => app(ScrapAsset::class)($asset->fresh(), []))
        ->toThrow(RuntimeException::class, 'already scrapped');
});

it('encrypts remote-access password at rest, decrypts on read', function () {
    $org = Organization::create(['slug' => 'sec', 'name' => ['en' => 'Sec'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);

    $remote = AssetRemoteAccess::create([
        'asset_id' => $asset->id,
        'type' => AssetRemoteAccess::TYPE_TEAMVIEWER,
        'identifier' => '123 456 789',
        'username' => 'admin',
        'password_encrypted' => 'super-secret-pw',
        'port' => null,
    ]);

    // Read back through the cast — should round-trip.
    expect($remote->fresh()->password_encrypted)->toBe('super-secret-pw');

    // The raw DB column should NOT contain the plaintext.
    $raw = DB::table('asset_remote_access')->where('id', $remote->id)->value('password_encrypted');
    expect($raw)->not->toBeNull()
        ->and($raw)->not->toContain('super-secret-pw');
});
