<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Monetary values are stored as integer cents (€114,000 → 11400000).
 * Floats are rejected outright so rounding errors can never reach the
 * database. Euro→cent conversion happens in FormRequests, not here.
 *
 * @implements CastsAttributes<int|null, int|string|null>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) || ! is_numeric($value) || (string) (int) $value !== (string) $value) {
            throw new InvalidArgumentException(
                "The {$key} attribute must be an integer amount of cents, got: ".var_export($value, true)
            );
        }

        return (int) $value;
    }
}
