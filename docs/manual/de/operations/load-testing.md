# Lasttests (Belegungsspitze)

Bildet mit k6 die Situation nach, in der viele Studierende gleichzeitig dieselben Kurse ansteuern, wie am ersten Morgen der Belegung. Das Skript ist `tests/load/registration.js`.

## Vorbereitung

1. Erzeugen Sie einen großen Demodatensatz und hängen Sie Studierendenkonten an (die CSV-Liste der Konten wird nach `storage/app/private/load-accounts.csv` geschrieben).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Ein Bearbeitungszeitraum für die Belegung muss geöffnet sein (die Demo öffnet den Belegungszeitraum des Kurszeitraums im aktuellen Semester).
3. Der Queue-Worker (Horizon) und Redis müssen laufen. Platzreservierung und Nachrücken von der Warteliste laufen über die Queue.

## Ausführung

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Erhöhen Sie `--vus` von 100 über 200 auf 500 und beobachten Sie das p95 von `http_req_duration` und die Fehlerquote. Ziele: Belegungsseite p95 < 800 ms, Belegungsanfrage p95 < 1,5 s, Fehlerquote < 1 %.
- Beobachten Sie auf der Serverseite außerdem die Anzahl wartender (Sperr-)Sitzungen in `pg_stat_activity`, die Verarbeitungsverzögerung in Horizon und die Auslastung der php-fpm-Worker.
- Beim Test gegen Sail erhöhen Sie die Anzahl der Worker des eingebauten Servers (`PHP_CLI_SERVER_WORKERS` in `.env`, Standard 4). Die Worker-Anzahl begrenzt den Durchsatz.

## Was das Design garantiert

- Die Kapazität wird durch DB-Zeilensperren geschützt (`SeatAllocator::lock`), sodass selbst eine Welle gleichzeitiger Anfragen einen Kurs nicht überbuchen kann (`ConcurrentRegistrationTest`).
- Die Platzreservierung ist allein in der DB korrekt, ohne Redis oder Queue; das Nachrücken von der Warteliste und Benachrichtigungen werden jedoch über die Queue abgewickelt.

## Entscheidung über Octane

Überschreitet das p95 auf einem einzelnen php-fpm unter Sail die Ziele, optimieren Sie zuerst die Anzahl der php-fpm-Worker und `max_connections` von PostgreSQL oder setzen Sie PgBouncer ein. Reicht das noch nicht aus, ziehen Sie Laravel Octane (FrankenPHP oder Swoole) in Betracht. Prüfen Sie vor dem Umstieg auf Octane: Zustand, der je Request zurückgesetzt werden muss (die Sitzungsvariablen von `DatabaseSessionContext` werden am Ende des Requests durch `reset()` zurückgesetzt und sind daher auch unter Octane sicher), in Singletons verbliebene Benutzerdaten, statische Caches. Die Entscheidung wird nach Auswertung realer Lastzahlen im Bericht zu Phase 13 festgehalten.

## Ergebnisse vom 23.10.2026

Sail-Entwicklungscontainer (eingebauter Server, ohne opcache), 100 VUs über eine Minute: 24,7 req/s mit 4 Workern, 34,5 req/s mit 16 Workern, 0 HTTP-Fehler, 0 Überbuchungen, 4 PostgreSQL-Verbindungen. Die Serverzeit je Request beträgt etwa 120 ms für die Seite und 190 ms für eine Belegung; die Latenz von mehreren Sekunden bei 100 VUs entsteht durch Warteschlangen vor den Workern. Der Durchsatz skaliert mit der Anzahl der PHP-Worker, und die Datenbank ist weit von ihrer Grenze entfernt; daher wird **Octane nicht eingeführt**: Die Anzahl der php-fpm-Worker und eine horizontale Skalierung der Anwendungsserver genügen. Wiederholen Sie den Lauf vor dem Produktivstart mit 200 und 500 VUs in einer Staging-Umgebung mit php-fpm und opcache. Vollständige Zahlen: [Bericht zu Phase 13](../../../../porting/phase-13-report.md) Abschnitt 7.
