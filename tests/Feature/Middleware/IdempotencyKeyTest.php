<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Cache::flush();

    Route::middleware([IdempotencyKey::class])->post('/test/idempotent', function () {
        return response()->json([
            'value' => random_int(1, PHP_INT_MAX),
        ]);
    });
});

it('replays the cached response when the same idempotency key is sent twice', function () {
    $key = 'test-key-1';

    $first = $this->postJson('/test/idempotent', [], ['Idempotency-Key' => $key]);
    $first->assertStatus(200);
    $firstValue = $first->json('value');

    $second = $this->postJson('/test/idempotent', [], ['Idempotency-Key' => $key]);
    $second->assertStatus(200);
    $second->assertHeader('X-Idempotent-Replayed', '1');

    expect($second->json('value'))->toBe($firstValue);
});

it('returns a fresh response when a different idempotency key is sent', function () {
    $first = $this->postJson('/test/idempotent', [], ['Idempotency-Key' => 'key-a']);
    $second = $this->postJson('/test/idempotent', [], ['Idempotency-Key' => 'key-b']);

    expect($first->json('value'))->not->toBe($second->json('value'));
    $second->assertHeaderMissing('X-Idempotent-Replayed');
});

it('does not cache requests without an idempotency key header', function () {
    $first = $this->postJson('/test/idempotent');
    $second = $this->postJson('/test/idempotent');

    expect($first->json('value'))->not->toBe($second->json('value'));
});

it('passes GET requests through without caching', function () {
    Route::middleware([IdempotencyKey::class])->get('/test/idempotent-get', function () {
        return response()->json(['value' => random_int(1, PHP_INT_MAX)]);
    });

    $first = $this->getJson('/test/idempotent-get', ['Idempotency-Key' => 'g1']);
    $second = $this->getJson('/test/idempotent-get', ['Idempotency-Key' => 'g1']);

    expect($first->json('value'))->not->toBe($second->json('value'));
});
