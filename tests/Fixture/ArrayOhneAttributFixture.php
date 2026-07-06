<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Fixture;

final readonly class ArrayOhneAttributFixture
{
    /** @param array<int, string> $items */
    public function __construct(public array $items)
    {
    }
}
