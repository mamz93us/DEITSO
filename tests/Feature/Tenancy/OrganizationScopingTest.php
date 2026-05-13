<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Organization;

it('only returns branches belonging to the active organization', function () {
    $orgA = Organization::create([
        'slug' => 'org-a',
        'name' => ['en' => 'Org A'],
        'status' => 'active',
    ]);
    $orgB = Organization::create([
        'slug' => 'org-b',
        'name' => ['en' => 'Org B'],
        'status' => 'active',
    ]);

    // Insert branches under each org bypassing scopes for setup.
    app()->forgetInstance('current.organization');
    Branch::create([
        'organization_id' => $orgA->id,
        'name' => ['en' => 'A Cairo'],
        'code' => 'A-CAI',
    ]);
    Branch::create([
        'organization_id' => $orgB->id,
        'name' => ['en' => 'B Cairo'],
        'code' => 'B-CAI',
    ]);

    // Set Org A as active and query.
    app()->instance('current.organization', $orgA);
    $branchesA = Branch::all();

    expect($branchesA)->toHaveCount(1)
        ->and($branchesA->first()->code)->toBe('A-CAI');

    // Switch to Org B.
    app()->instance('current.organization', $orgB);
    $branchesB = Branch::all();

    expect($branchesB)->toHaveCount(1)
        ->and($branchesB->first()->code)->toBe('B-CAI');
});

it('auto-fills organization_id on Branch create from current.organization', function () {
    $org = Organization::create([
        'slug' => 'autofill-co',
        'name' => ['en' => 'Autofill Co'],
        'status' => 'active',
    ]);

    app()->instance('current.organization', $org);

    $branch = Branch::create([
        'name' => ['en' => 'Auto Branch'],
        'code' => 'AUTO',
    ]);

    expect($branch->organization_id)->toBe($org->id);
});
