<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Eine einzelne Verletzung eines {@see OutputConstraintInterface}. `message` bleibt
 * PII-/Wert-frei (nur Regel + Grund), analog `Lotse\Rigg\Guardrail\GuardrailViolation`.
 *
 * @since 0.1.0
 */
final readonly class ConstraintViolation
{
    public function __construct(
        public string $rule,
        public string $message,
    ) {
    }
}
