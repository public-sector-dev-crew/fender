<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Ergebnis einer Schema-Validierung ({@see OutputValidator}). Bewusst kein Wurf
 * bei erwartbaren Konformitätsfehlern (analog `Lotse\Rigg\Guardrail\GuardrailResult`)
 * — nur echte Aufrufsfehler (malformed Konfiguration) werfen eine Exception.
 *
 * @since 0.1.0
 */
final readonly class SchemaValidationResult
{
    /**
     * @param list<string> $errors menschenlesbare Pfad+Grund-Meldungen, PII-frei
     *                             (tragen nur Schema-Pfade/-Typen, nie Werte)
     */
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {
    }
}
