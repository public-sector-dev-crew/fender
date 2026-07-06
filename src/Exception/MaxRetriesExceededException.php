<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Exception;

use Lotse\Fender\ConstraintViolation;

/**
 * {@see \Lotse\Fender\RetryableOutputExtractor} hat die konfigurierte Höchstzahl an
 * Versuchen erschöpft, ohne eine schema- und constraint-konforme Ausgabe zu
 * erhalten — fail-closed: der Konsument bekommt niemals eine unvalidierte
 * Struktur zurück, sondern diesen Wurf.
 *
 * @since 0.1.0
 */
final class MaxRetriesExceededException extends FenderException
{
    /**
     * @param list<string>              $lastSchemaErrors
     * @param list<ConstraintViolation> $lastConstraintViolations
     */
    public function __construct(
        public readonly int $attempts,
        public readonly array $lastSchemaErrors,
        public readonly array $lastConstraintViolations,
    ) {
        parent::__construct(\sprintf(
            'fender: nach %d Versuch(en) keine schema-/constraint-konforme Ausgabe erhalten.',
            $attempts,
        ));
    }
}
