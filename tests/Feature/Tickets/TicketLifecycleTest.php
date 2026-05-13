<?php

declare(strict_types=1);

use App\Actions\Tickets\AddCommentToTicket;
use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\TransitionTicketState;
use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\InProgress;
use App\Models\States\Ticket\NewState;
use App\Models\States\Ticket\Resolved;
use App\Models\States\Ticket\WaitingCustomer;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapTicketEnv(string $slug = 'tckco'): array
{
    $org = Organization::create(['slug' => $slug, 'name' => ['en' => $slug], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $requester = User::create([
        'name' => 'Customer', 'email' => 'c@'.$slug.'.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $tech = User::create([
        'name' => 'Tech', 'email' => 't@'.$slug.'.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    // Both users belong to the org so AssignTicket's cross-tenant guard passes.
    $org->users()->attach($requester->id, ['joined_at' => now(), 'is_default' => true]);
    $org->users()->attach($tech->id, ['joined_at' => now(), 'is_default' => true]);

    return ['org' => $org, 'requester' => $requester, 'tech' => $tech];
}

it('creates a ticket with auto code TCK-YYYY-00001', function () {
    ['org' => $org, 'requester' => $req] = bootstrapTicketEnv();

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'Mouse broken',
        'description' => 'Right click stuck',
    ]);

    expect($t->code)->toStartWith('TCK-'.now()->format('Y').'-')
        ->and($t->state)->toBeInstanceOf(NewState::class)
        ->and($t->priority)->toBe(Ticket::PRIORITY_NORMAL);
});

it('attaches SLA timers from the matching SlaPolicy', function () {
    ['org' => $org, 'requester' => $req] = bootstrapTicketEnv();

    SlaPolicy::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'High SLA'],
        'priority' => Ticket::PRIORITY_HIGH,
        'response_minutes' => 30,
        'resolution_minutes' => 480, // 8 hours
        'is_default' => false,
    ]);

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'Server down',
        'description' => 'Production down',
        'priority' => Ticket::PRIORITY_HIGH,
    ]);

    expect($t->sla_policy_id)->not->toBeNull()
        ->and($t->sla_response_due_at)->not->toBeNull()
        ->and($t->sla_resolution_due_at)->not->toBeNull();

    // Roughly 30 minutes from opened_at (sign-agnostic).
    $responseDelta = abs((int) $t->opened_at->diffInMinutes($t->sla_response_due_at));
    expect($responseDelta)->toBeGreaterThanOrEqual(29)->toBeLessThanOrEqual(31);
});

it('assigns a ticket, transitioning to Assigned automatically', function () {
    ['org' => $org, 'requester' => $req, 'tech' => $tech] = bootstrapTicketEnv();

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'X', 'description' => 'Y',
    ]);

    app(AssignTicket::class)($t, $tech);

    expect($t->fresh()->state)->toBeInstanceOf(Assigned::class)
        ->and($t->fresh()->assigned_user_id)->toBe($tech->id);
});

it('records first_response_at on first staff public comment', function () {
    ['org' => $org, 'requester' => $req, 'tech' => $tech] = bootstrapTicketEnv();

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'X', 'description' => 'Y',
    ]);

    // Internal note — should NOT count as response.
    app(AddCommentToTicket::class)($t, $tech, 'Looking into it', TicketComment::AUTHOR_STAFF, true);
    expect($t->fresh()->first_response_at)->toBeNull();

    // Public response — counts.
    app(AddCommentToTicket::class)($t, $tech, 'Working on this', TicketComment::AUTHOR_STAFF, false);
    expect($t->fresh()->first_response_at)->not->toBeNull();

    // Second public response should NOT overwrite first_response_at.
    $first = $t->fresh()->first_response_at;
    app(AddCommentToTicket::class)($t, $tech, 'Update', TicketComment::AUTHOR_STAFF, false);
    expect($t->fresh()->first_response_at->equalTo($first))->toBeTrue();
});

it('extends SLA dates when paused on WaitingCustomer and resumed', function () {
    ['org' => $org, 'requester' => $req, 'tech' => $tech] = bootstrapTicketEnv();
    SlaPolicy::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Default'],
        'priority' => Ticket::PRIORITY_NORMAL,
        'response_minutes' => 60,
        'resolution_minutes' => 240,
        'is_default' => true,
    ]);

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'X', 'description' => 'Y',
    ]);
    app(AssignTicket::class)($t, $tech);
    $originalResolutionDue = $t->fresh()->sla_resolution_due_at->copy();

    // Pause: enter WaitingCustomer.
    app(TransitionTicketState::class)($t->fresh(), WaitingCustomer::class);
    $tPaused = $t->fresh();
    expect($tPaused->paused_at)->not->toBeNull();

    // Simulate 5 minutes elapsing while waiting.
    $tPaused->update(['paused_at' => $tPaused->paused_at->subMinutes(5)]);

    // Resume: enter InProgress. Should extend the due date by ~5 minutes.
    app(TransitionTicketState::class)($tPaused->fresh(), InProgress::class);

    $tResumed = $t->fresh();
    expect($tResumed->paused_at)->toBeNull()
        ->and($tResumed->paused_seconds_total)->toBeGreaterThanOrEqual(280); // ≥ 4m40s

    $extension = $originalResolutionDue->diffInMinutes($tResumed->sla_resolution_due_at);
    expect($extension)->toBeGreaterThanOrEqual(4);
});

it('walks the happy path: New → Assigned → InProgress → Resolved → Closed', function () {
    ['org' => $org, 'requester' => $req, 'tech' => $tech] = bootstrapTicketEnv();

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'X', 'description' => 'Y',
    ]);
    app(AssignTicket::class)($t, $tech);
    app(TransitionTicketState::class)($t->fresh(), InProgress::class);
    app(TransitionTicketState::class)($t->fresh(), Resolved::class);
    app(TransitionTicketState::class)($t->fresh(), Closed::class);

    $t = $t->fresh();
    expect($t->state)->toBeInstanceOf(Closed::class)
        ->and($t->resolved_at)->not->toBeNull()
        ->and($t->closed_at)->not->toBeNull();
});

it('detects SLA response breach', function () {
    ['org' => $org, 'requester' => $req] = bootstrapTicketEnv();
    SlaPolicy::create([
        'organization_id' => $org->id, 'name' => ['en' => 'D'],
        'priority' => Ticket::PRIORITY_NORMAL,
        'response_minutes' => 5, 'resolution_minutes' => 60,
        'is_default' => true,
    ]);

    $t = app(CreateTicket::class)($org->id, [
        'requester_user_id' => $req->id,
        'subject' => 'X', 'description' => 'Y',
    ]);

    // Simulate the deadline passing.
    $t->update(['sla_response_due_at' => now()->subMinute()]);

    expect($t->fresh()->is_response_breached)->toBeTrue();
});
