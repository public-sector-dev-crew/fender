<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Fixture;

use Lotse\Fender\ConstraintResult;
use Lotse\Fender\ConstraintViolation;
use Lotse\Fender\OutputConstraintInterface;

final class CountingFailOnceConstraint implements OutputConstraintInterface
{
    private int $calls = 0;

    public function check(object $output): ConstraintResult
    {
        ++$this->calls;

        if (1 === $this->calls) {
            return new ConstraintResult(false, [
                new ConstraintViolation('test.fail_once', 'Erster Versuch schlägt absichtlich fehl.'),
            ]);
        }

        return new ConstraintResult(true);
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
