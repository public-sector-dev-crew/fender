<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Lotse\Fender;

use Lotse\Fender\Exception\MaxRetriesExceededException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orchestriert Call → Schema-Validierung → Constraint-Prüfung → Retry-mit-Feedback
 * (ARCH-03 §3.11). **LLM-Client-agnostisch:** fender ruft kein Gateway/keinen
 * `steg`-Client selbst auf — der Konsument liefert eine Completion-Funktion
 * (`callable(string): string`), fender kennt nur Prompt-Text rein, Antwort-Text
 * raus. Reines Prompt+Parse+Retry — keine LLM-seitige Grammatik-/Schema-Erzwingung.
 *
 * Constraints laufen nur nach erfolgreicher Schema-Validierung — eine strukturell
 * ungültige Ausgabe wird nie gegen {@see OutputConstraintInterface} geprüft.
 *
 * Eine pathologisch große Rohantwort wird vor jedem `json_decode`/Reflection-
 * Aufwand verworfen statt unbegrenzt dekodiert (Schutz gegen Speicher-/Zeit-
 * Erschöpfung bei einem fehlverhaltenden Backend).
 *
 * @since 0.1.0
 */
final class RetryableOutputExtractor
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly int $maxResponseBytes = 1_048_576,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T>                 $class
     * @param callable(string): string        $complete    ruft das LLM auf und liefert den rohen Antworttext
     * @param list<OutputConstraintInterface> $constraints laufen nach erfolgreicher Schema-Validierung, in Reihenfolge
     *
     * @return T
     *
     * @throws MaxRetriesExceededException wenn nach $maxAttempts keine konforme Ausgabe erreicht wurde
     */
    public function extract(
        string $class,
        string $initialPrompt,
        callable $complete,
        array $constraints = [],
        int $maxAttempts = 3,
    ): object {
        $schema = SchemaGenerator::fromClass($class);
        $prompt = $initialPrompt;

        /** @var list<string> $lastSchemaErrors */
        $lastSchemaErrors = [];
        /** @var list<ConstraintViolation> $lastConstraintViolations */
        $lastConstraintViolations = [];

        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            $raw = $complete($prompt);

            if (\strlen($raw) > $this->maxResponseBytes) {
                $lastSchemaErrors = [\sprintf(
                    '$: Antwort überschreitet die Größenobergrenze (%d Bytes > %d Bytes) — verworfen, nicht dekodiert.',
                    \strlen($raw),
                    $this->maxResponseBytes,
                )];
                $lastConstraintViolations = [];
                $this->logger->info('fender: Antwort überschreitet Größenobergrenze.', ['attempt' => $attempt, 'bytes' => \strlen($raw)]);
                $prompt = self::withFeedback($initialPrompt, $lastSchemaErrors);
                continue;
            }

            $validation = OutputValidator::validate($raw, $schema);
            if (!$validation->valid) {
                $lastSchemaErrors = $validation->errors;
                $lastConstraintViolations = [];
                $this->logger->info('fender: Schema-Validierung fehlgeschlagen.', ['attempt' => $attempt, 'errors' => $validation->errors]);
                $prompt = self::withFeedback($initialPrompt, $validation->errors);
                continue;
            }

            $parsed = OutputParser::parse($raw, $class);

            $violations = [];
            foreach ($constraints as $constraint) {
                $result = $constraint->check($parsed);
                if (!$result->passed) {
                    $violations = [...$violations, ...$result->violations];
                }
            }

            if ([] === $violations) {
                return $parsed;
            }

            $lastSchemaErrors = [];
            $lastConstraintViolations = $violations;
            $this->logger->info('fender: Constraint-Verletzung.', [
                'attempt' => $attempt,
                'rules' => array_map(static fn (ConstraintViolation $violation): string => $violation->rule, $violations),
            ]);
            $prompt = self::withFeedback(
                $initialPrompt,
                array_map(static fn (ConstraintViolation $violation): string => $violation->message, $violations),
            );
        }

        throw new MaxRetriesExceededException($maxAttempts, $lastSchemaErrors, $lastConstraintViolations);
    }

    /**
     * @param list<string> $issues
     */
    private static function withFeedback(string $originalPrompt, array $issues): string
    {
        return $originalPrompt
            ."\n\nDeine letzte Antwort war ungültig. Probleme:\n- ".implode("\n- ", $issues)
            ."\n\nBitte antworte erneut, ausschließlich mit validem JSON gemäß der vorgegebenen Struktur.";
    }
}
