# Website Ops Client 1.0.7 – Heartbeat-Korrektur

Grundlage: ectu/website-ops-client, Commit 6c4f5bd7ade6f6b8a448fa7df701cb55290cd483 (Version 1.0.6).

## Installation

1. ZIP auf der Client-Website unter Plugins → Installieren → Plugin hochladen installieren und die vorhandene Client-Version ersetzen. Keine zweite Client-Kopie parallel aktivieren.
2. Unter Einstellungen → Website Ops Client die gespeicherten Angaben prüfen.
3. „Heartbeat jetzt senden“ anklicken. Erwartete Antwort: „Heartbeat vom Master bestätigt.“ Danach das Master-Dashboard neu laden.
4. Bei einem Fehler die angezeigte Meldung einschließlich HTTP-Status weitergeben. Keine Tokens weitergeben.

Der Master muss die neue Header-Authentifizierung unterstützen (Website Ops Dashboard ab 2.0.0). Dieser Client benötigt keine Token-in-GET-URL-Kompatibilität mehr. Andere, noch nicht aktualisierte Clients können sie weiterhin benötigen.

## Änderungen

- HTTP-Fehler, ungültiges JSON und fehlende Erfolgsmeldungen gelten nicht mehr als Erfolg.
- Der 15-Minuten-Abstand beginnt erst nach bestätigtem Heartbeat. Fehler erhalten eine kurze Wiederholpause von einer Minute; der manuelle Button kann sofort erneut senden.
- Neue Master-URL, Projekt-ID oder Token setzen den bisherigen Heartbeat-Zustand zurück. Beim Upgrade wird ein möglicherweise fälschlich gespeicherter Erfolg einmalig gelöscht.
- Der Verbindungstest prüft weiterhin den Aufgabenabruf und sendet bei dessen Erfolg zusätzlich den Heartbeat. Beide Ergebnisse sind sichtbar.
- Einstellungen zeigen den letzten Heartbeat-Versuch und die letzte bestätigte Übermittlung. Rohe Antwortinhalte werden nicht mehr als Debug-Option gespeichert.
- Tokens werden im Authorization-Header übertragen. Anfragen folgen keinen Weiterleitungen, damit Zugangsdaten nicht an ein anderes Ziel weitergereicht werden. Bei Weiterleitungen muss die endgültige HTTPS-Master-Adresse eingetragen werden.
- Automatischer Heartbeat bei Administrator-Aufrufen. Kein zusätzlicher Cron eingerichtet; Websites ohne solche Aufrufe senden nicht regelmäßig weiter.
- Die bisherige automatische Änderung der Verbindungseinstellungen allein durch GET-URL-Parameter wurde entfernt, da dabei eine Nonce-Prüfung fehlte. Einstellungen werden über das vorhandene geschützte Formular gespeichert.

Die bisherige Ermittlung des Website-Zustands bleibt bestehen. Ohne verfügbare WordPress-Health-Daten kann nach erfolgreichem Heartbeat weiterhin „Unbekannt“ erscheinen; PHP-Version und Zeitpunkt sollten dann bereits angezeigt werden.

## Prüfung

22 Tests unter WordPress 7.0.2 / PHP 8.3.14 bestanden. Der Client-HTTP-Aufruf wurde lokal an den echten REST-Dispatcher des Master-Plugins weitergereicht. Geprüft wurden Aufgabenabruf, PHP- und Health-Übermittlung, Header-Authentifizierung, HTTP-Fehler, Netzwerkfehler, HTML statt JSON, ausbleibende Bestätigung, Weiterleitungen, Zeitintervalle, Konfigurationswechsel und die beiden Schaltflächen der Einstellungsseite.

PHP-Syntax der vier Client-Dateien unter PHP 7.4.33, 8.1.31, 8.2.26, 8.3.14 und 8.4.1 geprüft. Die mitgelieferte Update-Checker-Bibliothek ist unverändert.

Die konkrete Antwort des produktiven Masters ist bisher nicht bekannt. Die Fehlerbehandlung ist korrigiert und die Kommunikation mit dem Master im Test bestätigt; eine mögliche Hosting-/Firewall-Ablehnung lässt sich nun über den manuellen Heartbeat erkennen.

Der vorhandene GitHub-Update-Checker bleibt enthalten.
