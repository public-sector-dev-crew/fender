<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Fixture;

use Lotse\Fender\Attribute\Description;

final readonly class QualityIssue
{
    public function __construct(
        #[Description('Beschreibung des Problems')]
        public string $description,
        public QualityIssueSeverity $severity,
    ) {
    }
}
