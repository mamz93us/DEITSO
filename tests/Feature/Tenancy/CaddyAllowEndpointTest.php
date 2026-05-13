<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrganizationDomain;

it('returns 400 when the domain query param is missing', function () {
    $response = $this->get('/internal/domains/allow');

    $response->assertStatus(400);
});

it('returns 404 for an unknown host', function () {
    $response = $this->get('/internal/domains/allow?domain=unknown.example.com');

    $response->assertStatus(404);
});

it('returns 404 for a domain that is only pending verification', function () {
    $org = Organization::create([
        'slug' => 'pending',
        'name' => ['en' => 'Pending'],
        'status' => 'active',
    ]);

    OrganizationDomain::create([
        'organization_id' => $org->id,
        'host' => 'pending.example.com',
        'type' => OrganizationDomain::TYPE_CUSTOM,
    ]);

    $response = $this->get('/internal/domains/allow?domain=pending.example.com');

    $response->assertStatus(404);
});

it('returns 200 for a verified host (Caddy will issue a cert)', function () {
    $org = Organization::create([
        'slug' => 'verified',
        'name' => ['en' => 'Verified'],
        'status' => 'active',
    ]);

    // dns_status and tls_status are NOT fillable — system-controlled fields.
    // The verification job is the only legit writer; tests must use forceFill.
    $domain = OrganizationDomain::create([
        'organization_id' => $org->id,
        'host' => 'verified.example.com',
        'type' => OrganizationDomain::TYPE_CUSTOM,
    ]);
    $domain->forceFill([
        'dns_status' => OrganizationDomain::DNS_VERIFIED,
        'tls_status' => OrganizationDomain::TLS_ACTIVE,
    ])->save();

    $response = $this->get('/internal/domains/allow?domain=verified.example.com');

    $response->assertStatus(200);
});

it('returns 403 when not coming from an allowed IP and no token', function () {
    // Override env so 127.0.0.1 is NOT in the allowlist.
    config()->set('app.env', 'testing');
    $oldAllowed = $_ENV['INTERNAL_ALLOWED_IPS'] ?? null;
    $_ENV['INTERNAL_ALLOWED_IPS'] = '10.99.99.99';
    $_ENV['INTERNAL_ROUTES_TOKEN'] = 'secret-token';

    try {
        $response = $this->get('/internal/domains/allow?domain=any');
        $response->assertStatus(403);

        // With the token, request goes through (and then 404s on missing host).
        $response = $this->withHeaders(['X-Internal-Token' => 'secret-token'])
            ->get('/internal/domains/allow?domain=any');
        // 'any' doesn't match any domain → 404, but we passed the gate.
        $response->assertStatus(404);
    } finally {
        $_ENV['INTERNAL_ALLOWED_IPS'] = $oldAllowed;
        unset($_ENV['INTERNAL_ROUTES_TOKEN']);
    }
});
