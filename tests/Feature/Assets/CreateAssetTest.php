<?php

declare(strict_types=1);

use App\Actions\Assets\CreateAsset;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Supplier;

function makeOrgWithBranchAndCategory(string $slug, string $branchCode, string $catCode, string $tracking = 'serialized'): array
{
    $org = Organization::create([
        'slug' => $slug,
        'name' => ['en' => ucfirst($slug)],
        'status' => 'active',
    ]);
    app()->instance('current.organization', $org);

    $branch = Branch::create([
        'organization_id' => $org->id,
        'name' => ['en' => $branchCode.' Branch'],
        'code' => $branchCode,
    ]);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => $catCode.' category'],
        'code' => $catCode,
        'tracking_mode' => $tracking,
    ]);

    return ['org' => $org, 'branch' => $branch, 'category' => $cat];
}

it('generates asset code combining org/branch/category prefixes + zero-padded sequence', function () {
    ['org' => $org, 'branch' => $branch, 'category' => $cat] = makeOrgWithBranchAndCategory('samirgroup', 'CAI', 'LAP');

    $asset = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'branch_id' => $branch->id,
        'serial_number' => 'SN-001',
        'name' => 'Dell Latitude',
    ]);

    // 'samirgroup' → first 3 letters → SAM
    expect($asset)->toBeInstanceOf(Asset::class)
        ->and($asset->code)->toBe('SAM-CAI-LAP-0001')
        ->and($asset->status)->toBe(Asset::STATUS_IN_STOCK)
        ->and($asset->tracking_mode)->toBe(AssetCategory::TRACKING_SERIALIZED);
});

it('increments per (org, branch, category) — different combos get separate sequences', function () {
    ['org' => $org, 'branch' => $cai, 'category' => $lap] = makeOrgWithBranchAndCategory('samirgroup', 'CAI', 'LAP');
    $alx = Branch::create(['organization_id' => $org->id, 'name' => ['en' => 'Alex'], 'code' => 'ALX']);

    $a1 = app(CreateAsset::class)($org->id, ['category_id' => $lap->id, 'branch_id' => $cai->id]);
    $a2 = app(CreateAsset::class)($org->id, ['category_id' => $lap->id, 'branch_id' => $cai->id]);
    $a3 = app(CreateAsset::class)($org->id, ['category_id' => $lap->id, 'branch_id' => $alx->id]);

    expect($a1->code)->toBe('SAM-CAI-LAP-0001')
        ->and($a2->code)->toBe('SAM-CAI-LAP-0002')
        ->and($a3->code)->toBe('SAM-ALX-LAP-0001');
});

it('inherits tracking_mode from the category when not specified', function () {
    ['org' => $org, 'category' => $cat] = makeOrgWithBranchAndCategory('bulkco', 'HQ', 'CABLE', AssetCategory::TRACKING_BULK);

    $asset = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'quantity' => 50,
    ]);

    expect($asset->tracking_mode)->toBe(AssetCategory::TRACKING_BULK)
        ->and($asset->quantity)->toBe(50);
});

it('uses GEN branch prefix when no branch is supplied', function () {
    ['org' => $org, 'category' => $cat] = makeOrgWithBranchAndCategory('noloc', 'HQ', 'PRT');

    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);

    expect($asset->code)->toBe('NOL-GEN-PRT-0001');
});

it('links assets to a supplier', function () {
    ['org' => $org, 'branch' => $branch, 'category' => $cat] = makeOrgWithBranchAndCategory('linkco', 'HQ', 'SRV');
    $supplier = Supplier::create([
        'organization_id' => $org->id,
        'code' => 'DELL',
        'name' => ['en' => 'Dell Egypt'],
        'status' => Supplier::STATUS_ACTIVE,
    ]);

    $asset = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'purchase_cost_minor' => 1500000,
    ]);

    expect($asset->supplier_id)->toBe($supplier->id)
        ->and($asset->supplier->name)->toBe('Dell Egypt')
        ->and($asset->purchase_cost_minor)->toBe(1500000);
});
