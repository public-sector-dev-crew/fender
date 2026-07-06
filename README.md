# fender

`lotse/fender` ist das **Structured-Output**-Package der Lotse-Plattform (ARCH-03 §3.11): Parsing,
Validierung und Retry-Logik für LLM-Ausgaben gegen ein Zielschema. **Framework-frei** (pure PHP, hängt nur
von `psr/log` ab — **keine** `lotse/rigg`-Abhängigkeit) und **domänenneutral** — keine Klasse kennt einen
Übersetzungs-, Auftrags- oder sonstigen Anwendungskontext.

## Kein rigg-Vertrag (bewusste Entscheidung)

Anders als `wache` (Guardrail-Verträge in `Lotse\Rigg\Guardrail\*`) hat `fender` **keinen** eigenen
rigg-Block. ARCH-03 §3.15 (Dual-Provider-Tabelle) führt Structured Output explizit als „— (Lotse-intern)":
es gibt keine Cross-Package-Schnittstelle, die ein zweiter Konsument gegen einen austauschbaren Adapter
bräuchte — jeder Konsument bindet `SchemaGenerator`/`OutputValidator`/`RetryableOutputExtractor` direkt
gegen seine eigene Zielklasse. Sollte sich das ändern (ein zweiter, fachfremder Konsument braucht eine
austauschbare Structured-Output-Implementierung), ist das der Auslöser für einen rigg-Vertrag — nicht vorher
(Beobachtungsliste-Prinzip, `AGENTS.md` §6).

## Inhalt

| Block | Inhalt |
|---|---|
| `Lotse\Fender\` | `SchemaGenerator` (PHP-Readonly-Klassen + Attribute → JSON-Schema), `OutputValidator` (Schema-Konformität), `OutputParser` (JSON → typisiertes PHP-Objekt), `RetryableOutputExtractor` (Call→Validate→Constraints→Retry-Orchestrierung), `SchemaValidationResult`, `ConstraintResult`, `ConstraintViolation` |
| `Lotse\Fender\Attribute\` | `Description`, `NumberRange`, `ArrayOf` — Custom-Attribute für die Schema-Generierung (Instructor-Ansatz: die PHP-Klasse IST das Schema) |
| `Lotse\Fender\Exception\` | `FenderException` (Basis), `SchemaGenerationException`, `ParseException`, `MaxRetriesExceededException` |

`OutputConstraintInterface` ist der generische Erweiterungspunkt für Prüfungen **jenseits** der reinen
Schema-Konformität (z. B. „diese Struktur darf keine unzulässigen Inhalte tragen") — Schema-Konformität
garantiert laut Herstellerdokumentation (OpenAI, Google) ausdrücklich **nicht** semantische oder rechtliche
Korrektheit. `fender` liefert die Verkettungs-Mechanik (`RetryableOutputExtractor` akzeptiert eine Liste von
Constraints, die nach erfolgreicher Schema-Validierung laufen); die konkrete Regel bleibt beim Konsumenten
(Anwendungs-Konfiguration, analog der Detektor-Auswahl in `wache`).

## Was NICHT hierhin gehört

Textqualitätsregeln, Sprachnormen oder Domänenvalidierung (Satzlänge, Wortlisten, Stilheuristiken) gehören
**niemals** in `fender` (`AGENTS.md`, kanonisch). `fender` prüft ausschließlich Struktur (Format + optionale
Zusatz-Constraints), nie Sprachqualität.

## Konfiguration bleibt beim Konsumenten

Die konkreten Zielschemata (welche Klasse, welche Felder), die Retry-Obergrenze und alle
`OutputConstraintInterface`-Implementierungen sind Anwendungs-Konfiguration und bleiben beim Konsumenten,
nicht im Package.
