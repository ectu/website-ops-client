# WordPress-E-Mail-Regeln

Client 1.1.0 und Master Dashboard 2.2.0

## Installation

1. Master mit `website-ops-dashboard-2.2.0.zip` aktualisieren.
2. Client-Websites mit `website-ops-client-1.1.0.zip` aktualisieren.
3. Im Master unter Website Ops → Einstellungen die drei Standardregeln festlegen.
4. Bei Bedarf unter Projekte → Projekt bearbeiten eine Ausnahme für Plugin-, Theme- oder Core-Erfolgsmeldungen wählen. „Zentrale Standardregel“ übernimmt die globale Einstellung.
5. Im Client unter Einstellungen → Website Ops Client → Heartbeat jetzt senden die Regel sofort übernehmen. Dort werden die wirksame Regel und der letzte erfolgreiche Regelabgleich angezeigt.

Vorhandene Projekte, Verbindungen und Aufgaben bleiben erhalten. SCF/ACF ist nicht erforderlich.

## Verhalten

Standardmäßig unterdrückt der Client reine Erfolgsmeldungen zu automatischen Plugin-, Theme- und WordPress-Core-Updates. Ein Plugin-/Theme-Ergebnis gilt nur dann als Erfolg, wenn WordPress explizit `true` meldet. Fehler, leere oder unklare Ergebnisse werden nicht durch Website Ops blockiert. Gemischte Ergebnisse innerhalb einer Update-Art bleiben erhalten; bei einem Fehler in einer anderen Update-Art bleibt deren Fehlermeldung erhalten.

Core-Fehler, kritische Core-Updates, Hinweise auf manuell erforderliche Updates, Wiederherstellungsmodus, Passwort- und Konto-E-Mails bleiben unverändert. Es gibt keinen pauschalen `wp_mail`-Filter. Empfänger werden nicht geändert. Die Master-Adresse für Aufgabenbenachrichtigungen ist davon unabhängig. Andere Plugins können den Mailversand weiterhin beeinflussen; die Funktion bestätigt keine Zustellung.

Die Regel wird über die authentifizierte HTTPS-Heartbeat-Antwort übertragen. Der Client akzeptiert nur eine vollständige, versionierte Regel mit booleschen Werten. Bei Verbindungsfehlern oder ungültigen Antworten bleibt die letzte gültige Regel gespeichert. Nach Änderung von Master-Adresse, Projekt-ID oder Token wird sie zurückgesetzt. Vor dem ersten Abgleich greift die Standardregel (Erfolgsmeldungen unterdrücken). Ein älterer Master kann weiterhin mit dem Client kommunizieren, liefert aber keine zentrale E-Mail-Konfiguration.

Zusätzlich zu den bisherigen Heartbeats wird ein stündlicher WP-Cron-Abgleich eingerichtet. WP-Cron hängt von Website-Aufrufen bzw. einer externen Cron-Konfiguration ab; die Übernahme ist deshalb nicht minutengenau garantiert. Es gibt keinen Netzwerkaufruf während der E-Mail-Filterung. Deaktivieren des Clients entfernt seinen neuen Cron-Termin und die Filter.

## Umfang

Diese Version unterstützt die Filterung auf einzelnen WordPress-Installationen. Bei Multisite-Netzwerken sind die Filter ausdrücklich deaktiviert, damit keine Unterwebsite netzwerkweite Mails unterdrückt. Plugin-eigene Backup-, Shop- oder Security-Mails sowie Entwickler-Debug-Mails werden nicht gefiltert. Es gibt noch keinen E-Mail-Verlauf oder Sammelbericht.

Die E-Mail-Regeln sind auch im Client-Release 1.2.0 enthalten.

## Prüfungen

- 43 neue E-Mail-Regel-Prüfungen: WordPress-eigene Update-Gruppierung, Erfolg/Fehler/gemischt, zentrale Standards und Projekt-Ausnahmen, authentifizierte API, fehlerhafte Regeln, Offline-Cache, Konfigurationswechsel, Nonce, Cron und Einstellungs-Ausgabe.
- 22 bestehende Client-/Master-Prüfungen und 79 bestehende Master-Integrationstests bestanden.
- Testumgebung: WordPress 7.0.2, PHP 8.3.14, isolierte lokale Datenbank. HTTP wurde zum echten lokalen REST-Dispatcher geleitet. Es wurden keine echten Update-E-Mails verschickt und keine Kunden-Websites verändert.
- 77 PHP-Syntaxprüfungen: Client unter PHP 7.4 und 8.1–8.4; Master unter PHP 8.1–8.4.

WordPress-Schnittstellen:
- https://developer.wordpress.org/reference/hooks/auto_plugin_update_send_email/
- https://developer.wordpress.org/reference/hooks/auto_theme_update_send_email/
- https://developer.wordpress.org/reference/hooks/auto_core_update_send_email/
