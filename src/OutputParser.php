<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

use Lotse\Fender\Attribute\ArrayOf;
use Lotse\Fender\Exception\ParseException;

/**
 * Hydriert einen JSON-String in eine typisierte PHP-Zielklasse — das Gegenstück zu
 * {@see SchemaGenerator}: dieselbe Reflection-Traversierung, aber Objekt-Aufbau
 * statt Schema-Beschreibung. Erwartet, dass der JSON-String bereits gegen
 * {@see OutputValidator} geprüft wurde (Defense-in-Depth-Fehler werfen
 * {@see ParseException} statt still zu tolerieren).
 *
 * @since 0.1.0
 */
final class OutputParser
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function parse(string $json, string $class): object
    {
        try {
            $decoded = json_decode($json, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ParseException('fender: ungültiges JSON — '.$exception->getMessage());
        }

        if (!\is_array($decoded)) {
            throw new ParseException('fender: JSON-Wurzelwert ist kein Objekt.');
        }

        return self::hydrate($decoded, $class);
    }

    /**
     * @template T of object
     *
     * @param array<mixed, mixed> $data
     * @param class-string<T>     $class
     *
     * @return T
     */
    private static function hydrate(array $data, string $class): object
    {
        if (!class_exists($class)) {
            throw new ParseException(\sprintf('fender: Klasse "%s" existiert nicht.', $class));
        }

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            throw new ParseException(\sprintf('fender: Klasse "%s" hat keinen Konstruktor.', $class));
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!\array_key_exists($name, $data)) {
                if ($parameter->isDefaultValueAvailable()) {
                    continue;
                }

                throw new ParseException(\sprintf('fender: Pflichtfeld "%s" fehlt beim Hydrieren von "%s".', $name, $class));
            }

            $arguments[$name] = self::hydrateValue($data[$name], $parameter);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private static function hydrateValue(mixed $value, \ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new ParseException(\sprintf('fender: Parameter "$%s" hat keinen unterstützten Typ.', $parameter->getName()));
        }

        if (null === $value) {
            if (!$type->allowsNull()) {
                throw new ParseException(\sprintf('fender: Parameter "$%s" erlaubt kein null.', $parameter->getName()));
            }

            return null;
        }

        $name = $type->getName();

        return match (true) {
            'string' === $name, 'int' === $name, 'float' === $name, 'bool' === $name => self::hydrateScalar($value, $name, $parameter),
            'array' === $name => self::hydrateArray($value, $parameter),
            is_a($name, \UnitEnum::class, true) => self::hydrateEnumValue($value, $name, $parameter),
            class_exists($name) => \is_array($value)
                ? self::hydrate($value, $name)
                : throw new ParseException(\sprintf('fender: Parameter "$%s" erwartet ein Objekt.', $parameter->getName())),
            default => throw new ParseException(\sprintf('fender: Typ "%s" (Parameter "$%s") wird nicht unterstützt.', $name, $parameter->getName())),
        };
    }

    private static function hydrateScalar(mixed $value, string $expectedType, \ReflectionParameter $parameter): string|int|float|bool
    {
        $matches = match ($expectedType) {
            'string' => \is_string($value),
            'int' => \is_int($value),
            'float' => \is_float($value) || \is_int($value),
            'bool' => \is_bool($value),
            default => false,
        };

        if (!$matches || !(\is_string($value) || \is_int($value) || \is_float($value) || \is_bool($value))) {
            throw new ParseException(\sprintf('fender: Parameter "$%s" erwartet Typ "%s".', $parameter->getName(), $expectedType));
        }

        return 'float' === $expectedType ? (float) $value : $value;
    }

    /**
     * @return list<mixed>
     */
    private static function hydrateArray(mixed $value, \ReflectionParameter $parameter): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new ParseException(\sprintf('fender: Parameter "$%s" erwartet eine Liste.', $parameter->getName()));
        }

        $arrayOfAttributes = $parameter->getAttributes(ArrayOf::class);
        if ([] === $arrayOfAttributes) {
            throw new ParseException(\sprintf('fender: Parameter "$%s" ist ein array ohne #[ArrayOf]-Attribut.', $parameter->getName()));
        }

        $itemType = $arrayOfAttributes[0]->newInstance()->itemType;

        return array_map(
            static fn (mixed $item): mixed => match (true) {
                \in_array($itemType, ['string', 'int', 'float', 'bool'], true) => self::hydrateScalar($item, $itemType, $parameter),
                is_a($itemType, \UnitEnum::class, true) => self::hydrateEnumValue($item, $itemType, $parameter),
                class_exists($itemType) && \is_array($item) => self::hydrate($item, $itemType),
                default => throw new ParseException(\sprintf('fender: #[ArrayOf]-Element-Typ "%s" (Parameter "$%s") passt nicht auf den Wert.', $itemType, $parameter->getName())),
            },
            $value,
        );
    }

    /**
     * @param class-string<\UnitEnum> $enumClass
     */
    private static function hydrateEnumValue(mixed $value, string $enumClass, \ReflectionParameter $parameter): \UnitEnum
    {
        if (is_a($enumClass, \BackedEnum::class, true)) {
            if (!\is_string($value) && !\is_int($value)) {
                throw new ParseException(\sprintf('fender: Parameter "$%s" erwartet einen Enum-Backing-Wert.', $parameter->getName()));
            }

            try {
                return $enumClass::from($value);
            } catch (\ValueError) {
                throw new ParseException(\sprintf('fender: Wert passt zu keinem Case von "%s" (Parameter "$%s").', $enumClass, $parameter->getName()));
            }
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new ParseException(\sprintf('fender: Wert passt zu keinem Case von "%s" (Parameter "$%s").', $enumClass, $parameter->getName()));
    }
}
