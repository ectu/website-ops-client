=== Website Ops Client ===

Contributors: symforma
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.7
Version: 1.0.7
License: GPLv2 or later

== Description ==

Verbindet Kundenwebsites mit dem Website Ops Master Dashboard.

== Changelog ==

= 1.0.7 =
* Heartbeats erst nach Bestätigung durch den Master als erfolgreich behandeln.
* Manuellen Heartbeat und verständliche HTTP-Fehlermeldungen ergänzen.
* Verbindungstest um Heartbeat erweitern und Wartezeit bei Konfigurationswechsel zurücksetzen.
* Tokens im Authorization-Header übertragen; HTTPS erforderlich, keine Weiterleitungen mit Zugangsdaten.
* Unsichere automatische Konfiguration über GET-Parameter entfernen.
* Kompatibilität: Website Ops Master Dashboard ab Version 2.0.0 erforderlich.


= 1.0.6 =
* Übermittlung von Website-Zustand korrigiert


= 1.0.4 =
* Übermittlung von PHP Version, Seitenzustand und Favicon an Master ergänzt

= 1.0.3 =
* WP Kompatibilität ergänzt

= 1.0.1 =
* GitHub Update-System integriert
* Heartbeat Client-Status ergänzt
* Prioritäts-Badges ergänzt

= 1.0.0 =
* Initial Release