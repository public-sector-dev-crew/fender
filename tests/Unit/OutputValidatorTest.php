<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender\Tests\Unit;

use Lotse\Fender\OutputValidator;
use Lotse\Fender\SchemaGenerator;
use Lotse\Fender\Tests\Fixture\TranslationOutputFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputValidator::class)]
final class OutputValidatorTest extends TestCase
{
    private const array VALID_JSON_ARRAY = [
        'translation' => 'Das ist ein Text in Leichter Sprache.',
        'terminology' => [
            ['term' => 'Widerspruch', 'explanation' => 'Wenn man nicht einverstanden ist.'],
        ],
        'confidence' => 0.9,
        'issues' => [
            ['description' => 'Langer Satz.', 'severity' => 'low'],
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return SchemaGenerator::fromClass(TranslationOutputFixture::class);
    }

    public function testValidatePruefktGueltigeAusgabeAlsValide(): void
    {
        $result = OutputValidator::validate(json_encode(self::VALID_JSON_ARRAY, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertTrue($result->valid);
        self::assertSame([], $result->errors);
    }

    public function testValidateSchlaegtBeiUngueltigemJsonFehl(): void
    {
        $result = OutputValidator::validate('{nicht valides json', $this->schema());

        self::assertFalse($result->valid);
    }

    public function testValidateSchlaegtBeiFehlendemPflichtfeldFehl(): void
    {
        $data = self::VALID_JSON_ARRAY;
        unset($data['translation']);

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertFalse($result->valid);
        self::assertNotSame([], $result->errors);
    }

    public function testValidateSchlaegtBeiFalschemTypFehl(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['confidence'] = 'nicht-numerisch';

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertFalse($result->valid);
    }

    public function testValidateSchlaegtBeiWertAusserhalbDesNumberRangeFehl(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['confidence'] = 1.5;

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertFalse($result->valid);
    }

    public function testValidateSchlaegtBeiUngueltigemEnumWertFehl(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['issues'] = [['description' => 'x', 'severity' => 'katastrophal']];

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertFalse($result->valid);
    }

    public function testValidateAkzeptiertNullBeiNullableFeld(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['note'] = null;

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertTrue($result->valid);
    }

    public function testValidateToleriertZusaetzlicheUnbekannteFelder(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['unbekanntes_kommentarfeld'] = 'irrelevant';

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        self::assertTrue($result->valid);
    }

    public function testValidateFehlermeldungTraegtNieDenTatsaechlichenWert(): void
    {
        $data = self::VALID_JSON_ARRAY;
        $data['translation'] = 'GEHEIME-PII-MARKIERUNG-DIE-NIE-IM-FEHLER-LANDEN-DARF';
        $data['confidence'] = 'kaputt';

        $result = OutputValidator::validate(json_encode($data, \JSON_THROW_ON_ERROR), $this->schema());

        foreach ($result->errors as $error) {
            self::assertStringNotContainsString('GEHEIME-PII-MARKIERUNG-DIE-NIE-IM-FEHLER-LANDEN-DARF', $error);
        }
    }

    public function testValidateWeistJsonObjektMitNumerischenSchluesselnAlsListeAb(): void
    {
        // {"0":…} an der Stelle eines "array"-Schemafelds ist ein Objekt, keine Liste.
        $objektStattListe = '{"translation":"x","terminology":{"0":{"term":"a","explanation":"b"}},"confidence":0.5}';

        $result = OutputValidator::validate($objektStattListe, $this->schema());

        self::assertFalse($result->valid);
        self::assertNotSame([], $result->errors);
    }

    public function testValidateAkzeptiertDieEchteListenformDesGleichenInhalts(): void
    {
        $echteListe = '{"translation":"x","terminology":[{"term":"a","explanation":"b"}],"confidence":0.5}';

        $result = OutputValidator::validate($echteListe, $this->schema());

        self::assertTrue($result->valid);
        self::assertSame([], $result->errors);
    }

    public function testValidateWeistJsonArrayWoObjektErwartetAb(): void
    {
        $result = OutputValidator::validate('[]', $this->schema());

        self::assertFalse($result->valid);
        self::assertNotSame([], $result->errors);
    }
}
