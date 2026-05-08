# Experiment Web App

Dieses Verzeichnis ist eine separat deploybare PHP/MySQL-Webanwendung fuer die Vergabe von einmaligen Experiment-Zugaengen.

## Struktur

- `index.html`: Bootstrap-Oberflaeche fuer Teilnehmende
- `assets/`: CSS und Vanilla-JS
- `api/`: PHP-Endpunkte
- `manage/`: Admin-Seite fuer Versuchsleitung
- `config/config.php`: Datenbankkonfiguration via Umgebungsvariablen
- `tests/validation_test.php`: kleiner CLI-Smoketest fuer Hilfslogik
- `tests/api_smoke_test.php`: API-Smoketest fuer zentrale End-to-End-Pfade

## Datenbank

SQL-Dateien liegen ausserhalb des Deploy-Ordners unter:

- `experiment/database/schema.sql`
- `experiment/database/seed.sql`

## Umgebungsvariablen

- `EXPERIMENT_DB_HOST`
- `EXPERIMENT_DB_PORT`
- `EXPERIMENT_DB_NAME`
- `EXPERIMENT_DB_USER`
- `EXPERIMENT_DB_PASSWORD`
- `EXPERIMENT_DB_CHARSET` optional, Standard `utf8mb4`

## Ablauf

1. Teilnehmende geben ihre ZHAW-Studierenden-E-Mail-Adresse ein.
2. Die App vergibt genau einen zufaelligen, noch freien Datensatz aus dem gewaehlten Pool.
3. Nur E-Mails aus `allowed_students` duerfen teilnehmen.
4. Die E-Mail-Adresse wird in `participant_credits` gespeichert.
5. Fuer Admin-Reset wird die aktuelle Zuordnung in `participant_assignment_links` gespeichert.
6. Die eigentliche Browser-Wiederherstellung bleibt ueber Token in `browser_tokens`.
7. Die Seite `manage/index.html` bietet Suche/Reset und das Hinzufuegen neuer erlaubter E-Mails.

## Deployment

1. `experiment/database/schema.sql` in MySQL importieren.
2. `experiment/database/seed.sql` importieren.
3. Datenbank-Zugangsdaten per Umgebungsvariablen setzen oder `config/config.php` anpassen.
4. Den Inhalt von `experiment/web/` auf den Webhost kopieren.

## Tests

- `php tests/validation_test.php`
- `php tests/api_smoke_test.php`
- `php tests/text_quality_test.php`
