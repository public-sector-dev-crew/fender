<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Attribute;

/**
 * Deklariert den Element-Typ eines `array`-Konstruktor-Parameters (z. B.
 * `list<TerminologyEntry>` oder `list<string>`) für {@see \Lotse\Fender\SchemaGenerator}.
 *
 * Natives PHP kann den generischen Element-Typ eines `array`-Parameters nicht per
 * Reflection ermitteln (anders als Pythons `list[X]`) — ein Custom-Attribut ist
 * die deterministische, nicht-docblock-abhängige Alternative zum ARCH-03-§3.11-
 * Beispiel (`@var TerminologyEntry[]`-Docblock-Parsing wäre fragiler).
 *
 * @since 0.1.0
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class ArrayOf
{
    /**
     * @param string $itemType ein Skalar-Typname (`string`/`int`/`float`/`bool`) oder
     *                         ein `class-string` einer verschachtelten Readonly-Klasse
     */
    public function __construct(
        public string $itemType,
    ) {
    }
}
