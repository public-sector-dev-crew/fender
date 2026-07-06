<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Unit;

use Lotse\Fender\Exception\SchemaGenerationException;
use Lotse\Fender\SchemaGenerator;
use Lotse\Fender\Tests\Fixture\ArrayOhneAttributFixture;
use Lotse\Fender\Tests\Fixture\TranslationOutputFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaGenerator::class)]
final class SchemaGeneratorTest extends TestCase
{
    public function testFromClassBautObjektSchemaMitPflichtfeldern(): void
    {
        $schema = SchemaGenerator::fromClass(TranslationOutputFixture::class);

        self::assertSame('object', $schema['type']);
        self::assertSame(['translation', 'terminology', 'confidence'], $schema['required']);
    }

    public function testFromClassUebernimmtDescriptionAttribut(): void
    {
        $properties = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['properties']);
        $translation = self::asArray($properties['translation']);

        self::assertSame('Die Übersetzung in Leichter Sprache', $translation['description']);
    }

    public function testFromClassUebernimmtNumberRangeAttribut(): void
    {
        $properties = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['properties']);
        $confidence = self::asArray($properties['confidence']);

        self::assertSame('number', $confidence['type']);
        self::assertSame(0.0, $confidence['minimum']);
        self::assertSame(1.0, $confidence['maximum']);
    }

    public function testFromClassBautArrayOfNestedObjectSchema(): void
    {
        $properties = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['properties']);
        $terminology = self::asArray($properties['terminology']);
        $items = self::asArray($terminology['items']);

        self::assertSame('array', $terminology['type']);
        self::assertSame('object', $items['type']);
        self::assertSame(['term', 'explanation'], $items['required']);
    }

    public function testFromClassBautEnumSchemaFuerBackedEnum(): void
    {
        $properties = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['properties']);
        $issues = self::asArray($properties['issues']);
        $items = self::asArray($issues['items']);
        $itemProperties = self::asArray($items['properties']);
        $severity = self::asArray($itemProperties['severity']);

        self::assertSame(['low', 'medium', 'high'], $severity['enum']);
    }

    public function testFromClassMarkiertOptionaleFelderAlsNichtErforderlich(): void
    {
        $required = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['required']);

        self::assertNotContains('issues', $required);
        self::assertNotContains('note', $required);
    }

    public function testFromClassMarkiertNullableFeldAlsNullableTyp(): void
    {
        $properties = self::asArray(SchemaGenerator::fromClass(TranslationOutputFixture::class)['properties']);
        $note = self::asArray($properties['note']);

        self::assertSame(['string', 'null'], $note['type']);
    }

    public function testFromClassWirftBeiKlasseOhneKonstruktor(): void
    {
        $this->expectException(SchemaGenerationException::class);

        SchemaGenerator::fromClass(\stdClass::class);
    }

    public function testFromClassWirftBeiArrayOhneArrayOfAttribut(): void
    {
        $this->expectException(SchemaGenerationException::class);

        SchemaGenerator::fromClass(ArrayOhneAttributFixture::class);
    }

    /**
     * @return array<mixed, mixed>
     */
    private static function asArray(mixed $value): array
    {
        if (!\is_array($value)) {
            throw new \RuntimeException('Erwartete ein Array-Schema-Fragment.');
        }

        return $value;
    }
}
