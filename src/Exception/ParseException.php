<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Exception;

/**
 * Ein bereits schema-valider JSON-String konnte nicht in die Zielklasse hydriert
 * werden (z. B. Enum-Wert ohne passenden Case). Unter regulärem Betrieb sollte das
 * nicht auftreten, da {@see \Lotse\Fender\OutputValidator} vorgeschaltet ist —
 * Defense-in-Depth, kein primärer Kontrollpfad.
 *
 * @since 0.1.0
 */
final class ParseException extends FenderException
{
}
