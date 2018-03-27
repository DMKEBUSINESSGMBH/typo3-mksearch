# mksearch

MK Search ist eine mächtige Such-Extension für TYPO3. Sie ist leicht erweiterbar und vielfältig konfigurierbar. Dabei stellt MK Search keine eigene Suchmaschine bereit, sondern agiert nur als Wrapper. Theoretisch können Anbindungen an beliebige Suchmaschinen implementiert werden. Derzeit gibt es für MK Search Anbindungen an Zend Lucene, ElasticSearch und Apache Solr. Somit ist sowohl eine reine PHP-Variante für den Einstieg verfügbar, als auch eine professionelle Client-Server Lösung für High-End-Anwendungen.

Im Gegensatz zu anderen Such-Lösungen werden bei MK Search keine Webseiten indiziert. Die Indizierung erfolgt auf Ebene der Daten (entspricht den Tabellen in der Datenbank. Auch die Indizierung von und Suche in Dateien wie PDFs ist möglich). Das hat bei der Suche den Vorteil, daß man die Ausgabe entsprechend des Typs des gefundenen Datensatzes gestalten kann. Auch hat man die maximale Kontrolle darüber, welche Informationen indiziert und später gefunden werden können.

Die Indizierung erfolgt grundsätzlich asynchron. Daten die im Index aktualisiert werden müssen, wandern zunächst in eine Warteschlange und werden über einen Scheduler-Dienst abgearbeitet.

## Documentation 

[Screenshots](Documentation/Introduction/Screenshots/Index.md)

[UserManual](Documentation/UserManual/Index.md)

[Administration](Documentation/Administration/Index.md)

[Developers](Documentation/Developers/Index.md)

[KnownProblems](Documentation/KnownProblems/Index.md)

[To-doList](Documentation/To-doList/Index.md)

[Changelog](Documentation/Changelog/Index.md)

## Support

 *  Slack Channel:  
    https://typo3.slack.com/messages/mksearch/  
    (request your invite for Slack here: https://forger.typo3.org/slack)