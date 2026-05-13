<?php

declare(strict_types=1);

it('responds with 200 on the root url', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('responds with 200 on the health probe', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
