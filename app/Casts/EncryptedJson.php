<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Stores a JSON-castable PHP value as a Laravel-encrypted string on disk and
 * decrypts to its original form on read. Used for vendor credentials (GoDaddy,
 * cPanel, etc.) that must never appear in plaintext in the database.
 */
class EncryptedJson implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }

        $decoded = json_decode($decrypted, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $json = json_encode($value, JSON_THROW_ON_ERROR);

        return [$key => Crypt::encryptString($json)];
    }
}
