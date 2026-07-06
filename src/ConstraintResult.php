<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Ergebnis eines {@see OutputConstraintInterface}-Checks.
 *
 * @since 0.1.0
 */
final readonly class ConstraintResult
{
    /**
     * @param list<ConstraintViolation> $violations
     */
    public function __construct(
        public bool $passed,
        public array $violations = [],
    ) {
    }
}
