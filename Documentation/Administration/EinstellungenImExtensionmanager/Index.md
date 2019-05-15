Einstellungen im ExtensionManager
=================================

Nachdem die Extension installiert wurde, sollte muss man im Extensionmanager noch ein paar Angaben machen. Das wichtigeste ist der Eintrag **Indexer config storage PID** . Hier sollte die UID des SysFolders eingetragen werden, in dem später die Konfiguration für die Indizierung abgelegt wird. Also einen neuen SysFolder anlegen und dann dessen UID eintragen.

Zend Lucene (zendPath und luceneIndexDir)
-----------------------------------------

Wenn man Zend Lucene als SearchEngine verwenden möchte, dann muss man noch den Pfad zum Zend Framework eintragen. Außerdem wird das Verzeichnis benötigt, in dem Lucene die Indexdaten ablegen soll. Dieses Verzeichnis sollte Außer dem des Webroots liegen, muss aber für PHP vom Webserver aus beschreibbar sein.

Apache Tika (tikaJar, tikaLocaleType und postTikaCommandParameters)
----------------------------------------

Mit Tika lassen sich Informationen aus Binärdateien wie Bildern, Videos, aber auch aus PDF- oder Worddokumenten extrahieren. Wenn man Tika verwenden möchte, dann sollte man noch den kompletten Pfad zum Tika-Jarfile angeben.

Tika kann z.B. auf der [Tika Homepage](https://tika.apache.org/download.html) heruntergeladen werden (tika-app).

Hinweis: Apache Solr hat ebenfalls eine Integration für Tika. Wenn diese verwendet wird, muss das Tika-Jar hier nicht angegeben werden.

Hinweis: Wenn eine eigene Tika Lib angegeben wird, dann sollte tikaLokaleType in der Extension Konfiguration gesetzt werden. Dieser ist notwendig da es sonst zu Problemen mit Dateien kommen kann, welche Umlaute/Sonderzeichen im Dateinamen haben. de\_DE.UTF-8 ermöglicht es z.B. Dateien mit Umlauten zu verarbeiten. Ggf. müssen verschiedene Typen probiert werden. Auf Windows Systemen ist diese Einstellung egal.

Hinweis: Tika wirft u.U. Fehler und Warnungen wenn z.B. Fonts fehlen, was für gewöhnlich nicht schlimm ist. In diesem Fall kann mit 
postTikaCommandParameters STDERR nach /dev/null umgeleitet werden. Dazu sollte postTikaCommandParameters
auf "2>/dev/null" gesetzt werden. Wenn es grundsätzlich zu Problemen kommt, ist ein Anzeichen, dass jedes Dokument leeren Inhalt liefert, was
in TYPO3 geloggt wird. 

Außerdem wird natürlich Java benötigt.

Sekunden bis Queue Einträge gelöscht werden (secondsToKeepQueueEntries)
-----------------------------------------------------------------------

Es kann konfiguriert werden, ab welchem Alter gelöschte Queue Einträge vollständig entfernt werden. Auf diese Weise bleibt die Queue Tabelle relativ klein.
