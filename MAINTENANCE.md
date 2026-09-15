# Website Ops: Wartungsübersicht

Master 2.3.0 / Client 1.2.0. Client-Release 1.2.0. Die Wartungsübersicht benötigt Master 2.3.0.

## Installation und Abgleich

Master-ZIP zuerst installieren, danach Client-ZIP auf einer Testwebsite. Bestehende E-Mail-Regeln, Projekte und Verbindungen bleiben erhalten. Im Client „Heartbeat jetzt senden“ ausführen. Ältere Clients liefern keine Wartungsdaten; dann zeigt der Master „Unbekannt“ statt fälschlich „Aktuell“.

## Übersicht

Die Haupttabelle zeigt Website, Updates, letztes Backup, offene Aufgaben, Vertrag und ein gemeinsames Aktionsmenü. Login und Hosting bleiben Direktlinks. PHP, WordPress-Version, einzelne Update-Versionen und Zeitpunkte stehen in aufklappbaren Details. Logs sind über das Menü erreichbar. Filter berücksichtigen alle Projekte, auch außerhalb der aktuellen Seite.

Update-Daten stammen aus WordPress' vorhandenen Prüf-Ergebnissen. Der Client löst keine Updates aus. Fehlende Ergebnisse oder abweichende installierte Versionen gelten als unbekannt. Im Master gelten Update-Prüfungen nach 48 Stunden als veraltet, übertragene Wartungsdaten nach 24 Stunden. Ein neuer Heartbeat macht alte Update-Ergebnisse nicht frisch. „Aktuell“ ist kein Sicherheits- oder Verfügbarkeitsnachweis. Die bisherige Website-Zustandsberechnung wurde an die gewichtete WordPress-Berechnung angepasst; kritische Tests verhindern die Bewertung „Gut“.

## WPvivid Pro

Der Adapter wurde anhand des lokal vorliegenden Pro-Codes 2.2.47 und der relevanten Änderungen in 2.2.49 entwickelt. Die vom Nutzer eingesetzte Kombination Free 0.9.135 / Pro 2.2.52 ist noch nicht anhand ihres Codes oder auf einer echten Website geprüft. Für diese Prüfung wird das Pro-2.2.52-Plugin-ZIP benötigt. Keine WPvivid-Pro-Dateien sind in unseren ZIPs enthalten.

Gelesen werden ausschließlich Erfolgsmetadaten aus den lokalen/externen Backup-Listen, der letzte bestätigte Versuch und aktive allgemeine Zeitpläne. Zugangsdaten, Backup-Pfade, Dateinamen, Rohprotokolle oder Backup-Inhalte werden nicht an den Master übertragen. Vorhandene Backups tragen die Startzeit laut WPvivid. Künftig beobachtete Erfolgsereignisse erhalten eine Abschlusszeit. Fehler werden separat gespeichert, damit eine neuere fehlgeschlagene Sicherung neben dem früheren Erfolg sichtbar bleibt.

Ein zusammengefasstes Archiv bedeutet nicht automatisch eine vollständige Website-Sicherung; bei unklarem Umfang wird „Umfang unbekannt“ angezeigt. Die Angaben bestätigen weder die aktuelle Existenz der Backup-Dateien noch eine getestete Wiederherstellbarkeit. Ein zuletzt erfolgreicher Datenbank- oder Teilbackup-Lauf ist nicht gleichbedeutend mit einem vollständigen Website-Backup.

Wenn genau ein allgemeiner aktiver Pro-Zeitplan ein auslesbares Intervall liefert, wird dieses verwendet. Bei mehreren oder inkrementellen Zeitplänen erfolgt keine vorgetäuschte zusammengefasste Planbewertung: Es gilt die monatliche Vorgabe oder die Projekt-Ausnahme. Unter Projekt bearbeiten kann die Bewertung auf täglich, wöchentlich oder monatlich gestellt werden. Monatlich bedeutet einen Kalendermonat, begrenzt auf den letzten Tag des Folgemonats. Zusätzlich gelten 48 Stunden Toleranz. Ein in die Zukunft verschobener Cron-Termin verdeckt kein nach dem Erfolgszeitpunkt überfälliges Backup. Der nächste Pro-Cron-Termin wird separat angezeigt. Abgleichzeiten hängen von WP-Cron/Website-Aufrufen ab.

Multisite-Backup-Daten werden in dieser Version nicht ausgewertet. Fehlende oder nicht erkennbare Daten bleiben „Unbekannt“.

## Validierung

30 neue Wartungsprüfungen, 22 Client-/Master-Tests, 43 E-Mail-Regel-Tests und 79 bestehende Integrationstests bestanden unter WordPress 7.0.2 / PHP 8.3.14. Keine echten Updates, Backups oder E-Mails ausgelöst; Pro-Datenstrukturen und Ereignisse mit Testdaten geprüft. Browserprüfung der echten Tabellen-Ausgabe mit Testdaten einschließlich Details und Aktionsmenü. PHP-Syntax separat geprüft für Client unter 7.4 und 8.1–8.4 sowie Master unter 8.1–8.4.

Referenzen:
- https://developer.wordpress.org/reference/functions/get_site_transient/
- https://docs.wpvivid.com/set-up-general-backup-schedules.html
