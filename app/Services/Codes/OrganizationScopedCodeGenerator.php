<?php

declare(strict_types=1);

namespace App\Services\Codes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Generates per-organization-scoped sequential codes (EMP-0001, TCK-2026-00001, …).
 *
 * Strategy: read the current max sequence for the (org, prefix, year) tuple
 * from the target table and increment within a Cache::lock to avoid races.
 *
 * The blueprint specifies a Redis lock; on Windows dev where Redis may not be
 * available, Cache::lock falls back to the configured cache store (file in dev).
 * The lock TTL is short — code generation is a sub-second operation.
 */
class OrganizationScopedCodeGenerator
{
    public function __construct(private CodeTemplate $template = new CodeTemplate) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $extras  Extra placeholders, e.g. ['BRANCH_PREFIX' => 'CAI'].
     */
    public function next(
        string $modelClass,
        string $organizationId,
        string $prefix,
        ?int $padding = 4,
        ?int $year = null,
        bool $yearReset = false,
        array $extras = [],
    ): string {
        $lockKey = sprintf('code-gen:%s:%s:%s:%s', $organizationId, $modelClass, $prefix, $year ?? 'all');

        return Cache::lock($lockKey, 5)->block(3, function () use (
            $modelClass, $organizationId, $prefix, $padding, $year, $yearReset, $extras
        ) {
            /** @var Model $instance */
            $instance = new $modelClass;

            $query = DB::table($instance->getTable())
                ->where('organization_id', $organizationId)
                ->where('code', 'like', $this->renderPattern($prefix, $year, $yearReset, $extras).'%');

            if ($yearReset && $year !== null) {
                // Pattern already pins the year.
            }

            $rows = $query->pluck('code');
            $maxSeq = 0;
            foreach ($rows as $code) {
                if (preg_match('/(\d+)$/', (string) $code, $m)) {
                    $maxSeq = max($maxSeq, (int) $m[1]);
                }
            }

            $next = $maxSeq + 1;
            $sequence = $padding ? str_pad((string) $next, $padding, '0', STR_PAD_LEFT) : (string) $next;

            return $this->renderPattern($prefix, $year, $yearReset, $extras).$sequence;
        });
    }

    /**
     * @param  array<string, string>  $extras
     */
    protected function renderPattern(string $prefix, ?int $year, bool $yearReset, array $extras): string
    {
        $parts = [$prefix];
        foreach ($extras as $val) {
            $parts[] = $val;
        }
        if ($yearReset && $year !== null) {
            $parts[] = (string) $year;
        }

        return implode('-', $parts).'-';
    }
}
