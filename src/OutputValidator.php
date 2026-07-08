<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

/**
 * Validiert einen JSON-String gegen ein {@see SchemaGenerator}-Schema.
 *
 * Fehlermeldungen tragen ausschließlich Schema-Pfade + Typnamen, NIE den
 * tatsächlichen Wert (R3-analog: die Ausgabe kann pseudonymisierten oder
 * PII-nahen Inhalt tragen, Fehlerlogs dürfen ihn nicht wiederholen).
 *
 * Zusätzliche, im Schema nicht deklarierte Objekt-Eigenschaften werden toleriert
 * (kein `additionalProperties: false`) — LLM-Ausgaben tragen gelegentlich
 * Kommentarfelder, die die Struktur nicht kompromittieren.
 *
 * Dekodiert bewusst nicht-assoziativ: sonst kollabiert `json_decode` ein JSON-Objekt
 * mit rein numerischen Schlüsseln (`{"0":…}`) und eine JSON-Liste (`[…]`) auf dieselbe
 * PHP-Array-Form, und die Struktur-Unterscheidung „Liste vs. Objekt" wäre nach dem
 * Dekodieren nicht mehr rekonstruierbar. Objekte kommen daher als `stdClass`, Listen
 * als Array.
 *
 * @since 0.1.0
 */
final class OutputValidator
{
    /**
     * @param array<string, mixed> $schema
     */
    public static function validate(string $json, array $schema): SchemaValidationResult
    {
        try {
            $decoded = json_decode($json, associative: false, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new SchemaValidationResult(valid: false, errors: ['$: ungültiges JSON.']);
        }

        $errors = self::validateValue($decoded, $schema, '$');

        return new SchemaValidationResult(valid: [] === $errors, errors: $errors);
    }

    /**
     * @param array<mixed, mixed> $schema
     *
     * @return list<string>
     */
    private static function validateValue(mixed $value, array $schema, string $path): array
    {
        if (isset($schema['enum']) && \is_array($schema['enum'])) {
            return self::validateEnum($value, $schema['enum'], $path);
        }

        $type = $schema['type'] ?? null;

        if (\is_array($type)) {
            return self::validateNullableType($value, $type, $schema, $path);
        }

        if (!\is_string($type)) {
            return [\sprintf('%s: Schema ohne "type"-Angabe.', $path)];
        }

        return match ($type) {
            'string' => \is_string($value) ? [] : [\sprintf('%s: Typ "string" erwartet.', $path)],
            'integer' => \is_int($value) ? [] : [\sprintf('%s: Typ "integer" erwartet.', $path)],
            'number' => self::validateNumber($value, $schema, $path),
            'boolean' => \is_bool($value) ? [] : [\sprintf('%s: Typ "boolean" erwartet.', $path)],
            'array' => self::validateArray($value, $schema, $path),
            'object' => self::validateObject($value, $schema, $path),
            default => [\sprintf('%s: unbekannter Schema-Typ "%s".', $path, $type)],
        };
    }

    /**
     * @param array<mixed, mixed> $types
     * @param array<mixed, mixed> $schema
     *
     * @return list<string>
     */
    private static function validateNullableType(mixed $value, array $types, array $schema, string $path): array
    {
        if (null === $value && \in_array('null', $types, true)) {
            return [];
        }

        $nonNullType = null;
        foreach ($types as $candidate) {
            if (\is_string($candidate) && 'null' !== $candidate) {
                $nonNullType = $candidate;
                break;
            }
        }

        if (null === $nonNullType) {
            return [];
        }

        $narrowed = $schema;
        $narrowed['type'] = $nonNullType;

        return self::validateValue($value, $narrowed, $path);
    }

    /**
     * @param array<mixed, mixed> $schema
     *
     * @return list<string>
     */
    private static function validateNumber(mixed $value, array $schema, string $path): array
    {
        if (!\is_int($value) && !\is_float($value)) {
            return [\sprintf('%s: Typ "number" erwartet.', $path)];
        }

        $errors = [];

        $minimum = $schema['minimum'] ?? null;
        if ((\is_int($minimum) || \is_float($minimum)) && $value < $minimum) {
            $errors[] = \sprintf('%s: Wert unterschreitet das Minimum.', $path);
        }

        $maximum = $schema['maximum'] ?? null;
        if ((\is_int($maximum) || \is_float($maximum)) && $value > $maximum) {
            $errors[] = \sprintf('%s: Wert überschreitet das Maximum.', $path);
        }

        return $errors;
    }

    /**
     * @param array<mixed, mixed> $schema
     *
     * @return list<string>
     */
    private static function validateArray(mixed $value, array $schema, string $path): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            return [\sprintf('%s: Typ "array" (Liste) erwartet.', $path)];
        }

        $itemSchema = $schema['items'] ?? [];
        if (!\is_array($itemSchema)) {
            return [\sprintf('%s: Schema ohne gültiges "items"-Element-Schema.', $path)];
        }

        $errors = [];
        foreach ($value as $index => $item) {
            foreach (self::validateValue($item, $itemSchema, \sprintf('%s[%d]', $path, $index)) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param array<mixed, mixed> $schema
     *
     * @return list<string>
     */
    private static function validateObject(mixed $value, array $schema, string $path): array
    {
        if (!$value instanceof \stdClass) {
            return [\sprintf('%s: Typ "object" erwartet.', $path)];
        }

        $data = get_object_vars($value);
        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];
        $errors = [];

        if (\is_array($required)) {
            foreach ($required as $name) {
                if (\is_string($name) && !\array_key_exists($name, $data)) {
                    $errors[] = \sprintf('%s.%s: Pflichtfeld fehlt.', $path, $name);
                }
            }
        }

        if (\is_array($properties)) {
            foreach ($data as $key => $propertyValue) {
                if (!isset($properties[$key]) || !\is_array($properties[$key])) {
                    continue;
                }

                foreach (self::validateValue($propertyValue, $properties[$key], \sprintf('%s.%s', $path, $key)) as $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<mixed, mixed> $enumValues
     *
     * @return list<string>
     */
    private static function validateEnum(mixed $value, array $enumValues, string $path): array
    {
        return \in_array($value, $enumValues, true) ? [] : [\sprintf('%s: Wert nicht in der erlaubten enum-Liste.', $path)];
    }
}
