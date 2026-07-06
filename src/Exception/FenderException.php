<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Exception;

/**
 * Basis-Exception für fender (kein rigg-Vertrag — {@see \Lotse\Fender} hat keine
 * eigene rigg-Vertragsgruppe, ARCH-03 §3.15: „— Lotse-intern"). Konsumenten, die
 * nur an "irgendetwas ging in fender schief" interessiert sind, fangen diese Klasse.
 *
 * @since 0.1.0
 */
class FenderException extends \RuntimeException
{
}
