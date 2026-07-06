<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Fixture;

use Lotse\Fender\Attribute\ArrayOf;
use Lotse\Fender\Attribute\Description;
use Lotse\Fender\Attribute\NumberRange;

/**
 * Testfixtur, angelehnt an das ARCH-03 §3.11-Beispiel (bewusst derselbe Zuschnitt
 * wie der spätere reale Klartext-Anwendungsfall, s. Mission-Brief Block A).
 */
final readonly class TranslationOutputFixture
{
    /**
     * @param list<TerminologyEntry> $terminology
     * @param list<QualityIssue>     $issues
     */
    public function __construct(
        #[Description('Die Übersetzung in Leichter Sprache')]
        public string $translation,

        #[ArrayOf(TerminologyEntry::class)]
        public array $terminology,

        #[Description('Konfidenz-Score zwischen 0.0 und 1.0')]
        #[NumberRange(min: 0.0, max: 1.0)]
        public float $confidence,

        #[ArrayOf(QualityIssue::class)]
        public array $issues = [],

        public ?string $note = null,
    ) {
    }
}
