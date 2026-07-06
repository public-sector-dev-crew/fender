<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Exception;

/**
 * Die Zielklasse ist nicht schema-fähig (kein Konstruktor, nicht-unterstützter
 * Parametertyp, fehlendes {@see \Lotse\Fender\Attribute\ArrayOf} bei einem
 * `array`-Parameter). Wirft beim Schema-Bau, nicht erst zur Laufzeit gegen echte
 * LLM-Ausgaben — ein Konfigurationsfehler soll sofort auffallen, fail-fast.
 *
 * @since 0.1.0
 */
final class SchemaGenerationException extends FenderException
{
}
