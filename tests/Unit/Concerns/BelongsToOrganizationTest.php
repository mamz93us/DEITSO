<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Tests\Stubs\OrgScopedModel;

beforeEach(function () {
    Schema::dropIfExists('org_scoped_stubs');
    Schema::create('org_scoped_stubs', function ($table) {
        $table->ulid('id')->primary();
        $table->string('organization_id', 26)->index();
        $table->string('label')->nullable();
        $table->softDeletes();
    });

    app()->forgetInstance('current.organization');
});

afterAll(function () {
    Schema::dropIfExists('org_scoped_stubs');
});

it('auto-fills organization_id from current.organization on create', function () {
    app()->instance('current.organization', (object) ['id' => '01HZX-ORG-AAAA']);

    $row = OrgScopedModel::create(['label' => 'autofilled']);

    expect($row->organization_id)->toBe('01HZX-ORG-AAAA');
});

it('does not auto-fill when current.organization is unbound (system context)', function () {
    $row = OrgScopedModel::create(['label' => 'system', 'organization_id' => '01HZX-SYS-BBBB']);

    expect($row->organization_id)->toBe('01HZX-SYS-BBBB');
});

it('applies the global scope to filter by active organization', function () {
    // Insert two rows under different orgs without the scope active.
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-AAAA', 'label' => 'a']);
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-BBBB', 'label' => 'b']);

    app()->instance('current.organization', (object) ['id' => '01HZX-ORG-AAAA']);

    expect(OrgScopedModel::count())->toBe(1)
        ->and(OrgScopedModel::first()->label)->toBe('a');
});

it('lets system context (no binding) see all rows', function () {
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-AAAA', 'label' => 'a']);
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-BBBB', 'label' => 'b']);

    // No binding = system context.
    expect(OrgScopedModel::count())->toBe(2);
});

it('honours a null binding (no active organization) by skipping the scope', function () {
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-AAAA', 'label' => 'a']);
    OrgScopedModel::create(['organization_id' => '01HZX-ORG-BBBB', 'label' => 'b']);

    app()->instance('current.organization', null);

    expect(OrgScopedModel::count())->toBe(2);
});
