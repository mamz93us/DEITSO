<?php

declare(strict_types=1);

use App\Actions\Employees\CreateEmployee;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('creates a paired user + employee in a single transaction', function () {
    $org = Organization::create([
        'slug' => 'acme',
        'name' => ['en' => 'Acme'],
        'status' => 'active',
    ]);

    // RoleSeeder needs the org to scope its role inserts.
    (new RoleSeeder)->setContainer(app())->run();

    app()->instance('current.organization', $org);

    $branch = Branch::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'HQ'],
        'code' => 'HQ',
    ]);

    $result = app(CreateEmployee::class)($org->id, [
        'email' => 'alice@acme.test',
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'position' => 'Developer',
        'phone' => '+201111111111',
        'branch_id' => $branch->id,
    ]);

    expect($result['one_time_password'])->toBeString()->and(strlen($result['one_time_password']))->toBeGreaterThanOrEqual(12);

    $emp = $result['employee'];
    expect($emp)->toBeInstanceOf(Employee::class)
        ->and($emp->code)->toStartWith('EMP-')
        ->and($emp->first_name)->toBe('Alice')
        ->and($emp->status)->toBe(Employee::STATUS_ACTIVE)
        ->and($emp->user)->toBeInstanceOf(User::class)
        ->and($emp->user->email)->toBe('alice@acme.test')
        ->and($emp->user->is_system_admin)->toBeFalse();
});

it('generates sequential per-org employee codes', function () {
    $org = Organization::create([
        'slug' => 'seq',
        'name' => ['en' => 'Seq'],
        'status' => 'active',
    ]);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $first = app(CreateEmployee::class)($org->id, [
        'email' => 'a@seq.test', 'first_name' => 'A',
    ]);
    $second = app(CreateEmployee::class)($org->id, [
        'email' => 'b@seq.test', 'first_name' => 'B',
    ]);

    expect($first['employee']->code)->toBe('EMP-0001')
        ->and($second['employee']->code)->toBe('EMP-0002');
});

it('isolates employee codes between organizations', function () {
    $orgA = Organization::create(['slug' => 'a', 'name' => ['en' => 'A'], 'status' => 'active']);
    $orgB = Organization::create(['slug' => 'b', 'name' => ['en' => 'B'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();

    app()->instance('current.organization', $orgA);
    $a = app(CreateEmployee::class)($orgA->id, ['email' => 'a@a.test', 'first_name' => 'A']);

    app()->instance('current.organization', $orgB);
    $b = app(CreateEmployee::class)($orgB->id, ['email' => 'b@b.test', 'first_name' => 'B']);

    expect($a['employee']->code)->toBe('EMP-0001')
        ->and($b['employee']->code)->toBe('EMP-0001');
});
