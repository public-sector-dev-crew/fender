<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Unit;

use Lotse\Fender\Exception\MaxRetriesExceededException;
use Lotse\Fender\RetryableOutputExtractor;
use Lotse\Fender\Tests\Fixture\CountingFailOnceConstraint;
use Lotse\Fender\Tests\Fixture\TranslationOutputFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetryableOutputExtractor::class)]
final class RetryableOutputExtractorTest extends TestCase
{
    private function validJson(): string
    {
        return json_encode([
            'translation' => 'Das ist Leichte Sprache.',
            'terminology' => [],
            'confidence' => 0.8,
        ], \JSON_THROW_ON_ERROR);
    }

    public function testExtractGibtGeparstesObjektZurueckWennAllesBeimErstenVersuchPasst(): void
    {
        $extractor = new RetryableOutputExtractor();

        $result = $extractor->extract(
            TranslationOutputFixture::class,
            'Basis-Prompt',
            fn (string $prompt): string => $this->validJson(),
        );

        self::assertInstanceOf(TranslationOutputFixture::class, $result);
        self::assertSame('Das ist Leichte Sprache.', $result->translation);
    }

    public function testExtractWiederholtBeiSchemaInvaliderErstenAntwortUndInjiziertFeedback(): void
    {
        $prompts = [];
        $responses = ['{kaputt', $this->validJson()];
        $attempt = 0;

        $complete = function (string $prompt) use (&$prompts, &$responses, &$attempt): string {
            $prompts[] = $prompt;

            return $responses[$attempt++];
        };

        $extractor = new RetryableOutputExtractor();
        $result = $extractor->extract(TranslationOutputFixture::class, 'Basis-Prompt', $complete, maxAttempts: 2);

        self::assertInstanceOf(TranslationOutputFixture::class, $result);
        self::assertCount(2, $prompts);
        self::assertSame('Basis-Prompt', $prompts[0]);
        self::assertStringContainsString('ungültig', $prompts[1]);
    }

    public function testExtractWiederholtBeiConstraintVerletzungUndGibtDannErfolgreichZurueck(): void
    {
        $constraint = new CountingFailOnceConstraint();
        $extractor = new RetryableOutputExtractor();

        $result = $extractor->extract(
            TranslationOutputFixture::class,
            'Basis-Prompt',
            fn (string $prompt): string => $this->validJson(),
            constraints: [$constraint],
            maxAttempts: 2,
        );

        self::assertInstanceOf(TranslationOutputFixture::class, $result);
        self::assertSame(2, $constraint->calls());
    }

    public function testExtractWirftMaxRetriesExceededExceptionNachErschoepfenAllerVersuche(): void
    {
        $extractor = new RetryableOutputExtractor();

        try {
            $extractor->extract(
                TranslationOutputFixture::class,
                'Basis-Prompt',
                static fn (string $prompt): string => '{kaputt',
                maxAttempts: 2,
            );
            self::fail('Erwartete MaxRetriesExceededException wurde nicht geworfen.');
        } catch (MaxRetriesExceededException $exception) {
            self::assertSame(2, $exception->attempts);
            self::assertNotSame([], $exception->lastSchemaErrors);
        }
    }

    public function testExtractRuftConstraintsNieAufWennSchemaNieValideWird(): void
    {
        $constraint = new CountingFailOnceConstraint();
        $extractor = new RetryableOutputExtractor();

        try {
            $extractor->extract(
                TranslationOutputFixture::class,
                'Basis-Prompt',
                static fn (string $prompt): string => '{kaputt',
                constraints: [$constraint],
                maxAttempts: 2,
            );
            self::fail('Erwartete MaxRetriesExceededException wurde nicht geworfen.');
        } catch (MaxRetriesExceededException) {
            self::assertSame(0, $constraint->calls());
        }
    }
}
