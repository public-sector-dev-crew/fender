<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

use Lotse\Fender\Attribute\ArrayOf;
use Lotse\Fender\Attribute\Description;
use Lotse\Fender\Attribute\NumberRange;
use Lotse\Fender\Exception\SchemaGenerationException;

/**
 * Generiert ein JSON-Schema (als PHP-Array) aus einer Readonly-Klasse mit
 * Konstruktor-Property-Promotion (ARCH-03 §3.11-Instructor-Ansatz): die PHP-Klasse
 * IST das Schema, kein manuell gepflegtes JSON-Schema-Dokument nötig.
 *
 * Unterstützt: `string`/`int`/`float`/`bool`, backed Enums, verschachtelte
 * Readonly-Klassen (Rekursion), `array`-Parameter mit {@see ArrayOf}-Attribut
 * (Pflicht — natives PHP kennt keinen generischen Element-Typ für `array`),
 * `?`-Nullability, Pflichtfeld-Ableitung aus `isOptional()`.
 *
 * @since 0.1.0
 */
final class SchemaGenerator
{
    private const array SCALAR_TYPES = ['string', 'int', 'float', 'bool'];

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public static function fromClass(string $class): array
    {
        $reflection = self::reflectSchemaClass($class);

        $properties = [];
        $required = [];

        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $properties[$parameter->getName()] = self::parameterSchema($parameter);
            if (!$parameter->isOptional()) {
                $required[] = $parameter->getName();
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * @param class-string $class
     *
     * @return \ReflectionClass<object>
     */
    private static function reflectSchemaClass(string $class): \ReflectionClass
    {
        if (!class_exists($class)) {
            throw new SchemaGenerationException(\sprintf('fender: Klasse "%s" existiert nicht.', $class));
        }

        $reflection = new \ReflectionClass($class);

        if (null === $reflection->getConstructor()) {
            throw new SchemaGenerationException(\sprintf('fender: Klasse "%s" hat keinen Konstruktor — Schema-Generierung braucht Konstruktor-Property-Promotion.', $class));
        }

        return $reflection;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parameterSchema(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new SchemaGenerationException(\sprintf('fender: Parameter "$%s" hat keinen oder einen Union-/Intersection-Typ — nicht unterstützt.', $parameter->getName()));
        }

        $schema = self::typeSchema($type, $parameter);

        foreach ($parameter->getAttributes(Description::class) as $attribute) {
            $schema['description'] = $attribute->newInstance()->text;
        }

        foreach ($parameter->getAttributes(NumberRange::class) as $attribute) {
            $range = $attribute->newInstance();
            $schema['minimum'] = $range->min;
            $schema['maximum'] = $range->max;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function typeSchema(\ReflectionNamedType $type, \ReflectionParameter $parameter): array
    {
        $name = $type->getName();

        $schema = match (true) {
            \in_array($name, self::SCALAR_TYPES, true) => self::scalarSchema($name),
            'array' === $name => self::arraySchema($parameter),
            is_a($name, \UnitEnum::class, true) => self::enumSchema($name),
            class_exists($name) => self::fromClass($name),
            default => throw new SchemaGenerationException(\sprintf('fender: Typ "%s" (Parameter "$%s") wird nicht unterstützt.', $name, $parameter->getName())),
        };

        if ($type->allowsNull()) {
            $schema = self::withNullable($schema);
        }

        return $schema;
    }

    /**
     * @return array{type: string}
     */
    private static function scalarSchema(string $name): array
    {
        return ['type' => match ($name) {
            'string' => 'string',
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            default => throw new SchemaGenerationException(\sprintf('fender: unbekannter Skalar-Typ "%s".', $name)),
        }];
    }

    /**
     * @return array{type: string, items: array<string, mixed>}
     */
    private static function arraySchema(\ReflectionParameter $parameter): array
    {
        $arrayOfAttributes = $parameter->getAttributes(ArrayOf::class);
        if ([] === $arrayOfAttributes) {
            throw new SchemaGenerationException(\sprintf('fender: Parameter "$%s" ist ein array ohne #[ArrayOf]-Attribut — Element-Typ nicht ermittelbar.', $parameter->getName()));
        }

        $itemType = $arrayOfAttributes[0]->newInstance()->itemType;

        $itemSchema = match (true) {
            \in_array($itemType, self::SCALAR_TYPES, true) => self::scalarSchema($itemType),
            is_a($itemType, \UnitEnum::class, true) => self::enumSchema($itemType),
            class_exists($itemType) => self::fromClass($itemType),
            default => throw new SchemaGenerationException(\sprintf('fender: #[ArrayOf]-Element-Typ "%s" (Parameter "$%s") wird nicht unterstützt.', $itemType, $parameter->getName())),
        };

        return ['type' => 'array', 'items' => $itemSchema];
    }

    /**
     * @param class-string<\UnitEnum> $enumClass
     *
     * @return array{enum: list<int|string>}
     */
    private static function enumSchema(string $enumClass): array
    {
        /** @var list<\UnitEnum> $cases */
        $cases = $enumClass::cases();

        return ['enum' => array_map(
            static fn (\UnitEnum $case): int|string => $case instanceof \BackedEnum ? $case->value : $case->name,
            $cases,
        )];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private static function withNullable(array $schema): array
    {
        if (isset($schema['type']) && \is_string($schema['type'])) {
            $schema['type'] = [$schema['type'], 'null'];

            return $schema;
        }

        // enumSchema() liefert keinen "type"-Schlüssel (nur "enum") — ohne diesen Zweig bliebe
        // ein nullable Enum-Parameter (?SomeEnum) nie-null-fähig im Schema.
        if (isset($schema['enum']) && \is_array($schema['enum'])) {
            $schema['enum'] = [...$schema['enum'], null];
        }

        return $schema;
    }
}
