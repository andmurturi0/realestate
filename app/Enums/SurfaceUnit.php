<?php

namespace App\Enums;

/**
 * Display-only unit for property requests. Surfaces are always stored
 * normalized to m² (1 ari = 100 m², 1 ha = 10000 m²).
 */
enum SurfaceUnit: string
{
    case SquareMetre = 'm2';
    case Ari = 'ari';
    case Hectare = 'ha';

    public function toSquareMetres(float $value): float
    {
        return match ($this) {
            self::SquareMetre => $value,
            self::Ari => $value * 100,
            self::Hectare => $value * 10000,
        };
    }
}
