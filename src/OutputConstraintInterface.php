<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Prüft eine bereits schema-valide, geparste Ausgabe gegen eine Zusatzregel
 * JENSEITS der reinen Struktur-Konformität — der generische Erweiterungspunkt für
 * Konsumenten, deren Struktur zusätzlich einer Policy genügen muss (z. B. „darf
 * keine unzulässigen Inhalte tragen"). Schema-Konformität allein garantiert das
 * nicht (Herstellerdokumentation OpenAI/Google, verifiziert in Run-09-Recherche).
 *
 * Läuft in {@see RetryableOutputExtractor} NACH erfolgreicher Schema-Validierung,
 * nie davor — eine strukturell ungültige Ausgabe wird nie gegen Constraints
 * geprüft. Die konkrete Regel (was "unzulässig" bedeutet) ist reine
 * Konsumenten-Konfiguration; fender kennt sie nicht.
 *
 * @since 0.1.0
 */
interface OutputConstraintInterface
{
    /**
     * @param object $output die geparste Zielstruktur (konsumentenspezifischer Typ)
     */
    public function check(object $output): ConstraintResult;
}
