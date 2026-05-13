<?php

declare(strict_types=1);

use App\Actions\Employees\CreateEmployee;
use App\Actions\Employees\TerminateEmployee;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('terminates an employee and soft-deletes the linked user', function () {
    $org = Organization::create([
        'slug' => 'term',
        'name' => ['en' => 'Term Inc'],
        'status' => 'active',
    ]);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $emp = app(CreateEmployee::class)($org->id, [
        'email' => 'bob@term.test',
        'first_name' => 'Bob',
    ])['employee'];

    $userId = $emp->user_id;

    app(TerminateEmployee::class)($emp, null, 'restructuring');

    $emp->refresh();
    expect($emp->status)->toBe(Employee::STATUS_TERMINATED)
        ->and($emp->termination_date)->not->toBeNull()
        ->and($emp->custom_fields['termination_reason'] ?? null)->toBe('restructuring');

    // Linked user is soft-deleted (not visible by default).
    expect(User::find($userId))->toBeNull()
        ->and(User::withTrashed()->find($userId)->trashed())->toBeTrue();

    // Original email is freed up — the soft-deleted row was renamed.
    expect(User::withTrashed()->where('email', 'bob@term.test')->exists())->toBeFalse();
});

it('frees up the email address for re-hiring after termination', function () {
    $org = Organization::create([
        'slug' => 'rehire',
        'name' => ['en' => 'Rehire Co'],
        'status' => 'active',
    ]);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    // First hire.
    $first = app(CreateEmployee::class)($org->id, [
        'email' => 'returning@rehire.test',
        'first_name' => 'Returning',
    ])['employee'];

    app(TerminateEmployee::class)($first);

    // Re-hire with the same email — should not collide with the soft-deleted user.
    $second = app(CreateEmployee::class)($org->id, [
        'email' => 'returning@rehire.test',
        'first_name' => 'Returning',
        'last_name' => 'Reborn',
    ])['employee'];

    expect($second)->toBeInstanceOf(Employee::class)
        ->and($second->user->email)->toBe('returning@rehire.test')
        ->and($second->id)->not->toBe($first->id);
});
