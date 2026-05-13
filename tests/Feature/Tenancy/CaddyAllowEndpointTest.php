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
        'dns_status' => OrganizationDomain::DNS_PENDING,
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

    OrganizationDomain::create([
        'organization_id' => $org->id,
        'host' => 'verified.example.com',
        'type' => OrganizationDomain::TYPE_CUSTOM,
        'dns_status' => OrganizationDomain::DNS_VERIFIED,
        'tls_status' => OrganizationDomain::TLS_ACTIVE,
    ]);

    $response = $this->get('/internal/domains/allow?domain=verified.example.com');

    $response->assertStatus(200);
});
