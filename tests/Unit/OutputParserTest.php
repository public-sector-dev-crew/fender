<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Unit;

use Lotse\Fender\Exception\ParseException;
use Lotse\Fender\OutputParser;
use Lotse\Fender\Tests\Fixture\QualityIssueSeverity;
use Lotse\Fender\Tests\Fixture\TranslationOutputFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputParser::class)]
final class OutputParserTest extends TestCase
{
    public function testParseHydriertSkalareUndVerschachtelteArrays(): void
    {
        $json = json_encode([
            'translation' => 'Das ist Leichte Sprache.',
            'terminology' => [
                ['term' => 'Widerspruch', 'explanation' => 'Wenn man nicht einverstanden ist.'],
            ],
            'confidence' => 0.75,
            'issues' => [
                ['description' => 'zu lang', 'severity' => 'high'],
            ],
        ], \JSON_THROW_ON_ERROR);

        $result = OutputParser::parse($json, TranslationOutputFixture::class);

        self::assertSame('Das ist Leichte Sprache.', $result->translation);
        self::assertSame(0.75, $result->confidence);
        self::assertCount(1, $result->terminology);
        self::assertSame('Widerspruch', $result->terminology[0]->term);
        self::assertCount(1, $result->issues);
        self::assertSame(QualityIssueSeverity::HIGH, $result->issues[0]->severity);
    }

    public function testParseWendetDefaultsFuerFehlendeOptionaleFelderAn(): void
    {
        $json = json_encode([
            'translation' => 'Kurzer Text.',
            'terminology' => [],
            'confidence' => 0.5,
        ], \JSON_THROW_ON_ERROR);

        $result = OutputParser::parse($json, TranslationOutputFixture::class);

        self::assertSame([], $result->issues);
        self::assertNull($result->note);
    }

    public function testParseWirftBeiFehlendemPflichtfeld(): void
    {
        $json = json_encode([
            'terminology' => [],
            'confidence' => 0.5,
        ], \JSON_THROW_ON_ERROR);

        $this->expectException(ParseException::class);

        OutputParser::parse($json, TranslationOutputFixture::class);
    }

    public function testParseWirftBeiUngueltigemEnumWert(): void
    {
        $json = json_encode([
            'translation' => 'x',
            'terminology' => [],
            'confidence' => 0.5,
            'issues' => [['description' => 'x', 'severity' => 'katastrophal']],
        ], \JSON_THROW_ON_ERROR);

        $this->expectException(ParseException::class);

        OutputParser::parse($json, TranslationOutputFixture::class);
    }

    public function testParseWirftBeiUngueltigemJson(): void
    {
        $this->expectException(ParseException::class);

        OutputParser::parse('{kaputt', TranslationOutputFixture::class);
    }
}
