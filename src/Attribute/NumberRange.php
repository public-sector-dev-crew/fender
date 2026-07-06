<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Attribute;

/**
 * Trägt `minimum`/`maximum` eines numerischen Konstruktor-Parameters ins generierte
 * JSON-Schema (ARCH-03 §3.11-Beispiel: `confidence` zwischen 0.0 und 1.0).
 *
 * @since 0.1.0
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class NumberRange
{
    public function __construct(
        public float $min,
        public float $max,
    ) {
    }
}
