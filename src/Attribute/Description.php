<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Attribute;

/**
 * Trägt die JSON-Schema-`description` eines Konstruktor-Parameters (ARCH-03 §3.11:
 * Instructor-Ansatz — die PHP-Klasse IST das Schema, {@see \Lotse\Fender\SchemaGenerator}
 * liest dieses Attribut per Reflection statt eines manuell gepflegten Schemas).
 *
 * @since 0.1.0
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Description
{
    public function __construct(
        public string $text,
    ) {
    }
}
