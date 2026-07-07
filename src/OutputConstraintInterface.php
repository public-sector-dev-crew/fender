<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Prüft eine bereits schema-valide, geparste Ausgabe gegen eine Zusatzregel
 * jenseits der reinen Struktur-Konformität (z. B. Policy-Konformität des Inhalts).
 * Schema-Konformität allein garantiert das nicht.
 *
 * Läuft in {@see RetryableOutputExtractor} nur nach erfolgreicher Schema-
 * Validierung. Die konkrete Regel ist Konsumenten-Konfiguration; fender kennt
 * sie nicht.
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
