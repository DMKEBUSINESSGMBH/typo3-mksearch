

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Einstellungen im ExtensionManager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Nachdem die Extension installiert wurde, sollte muss man im
Extensionmanager noch ein paar Angaben machen. Das wichtigeste ist der
Eintrag  **Indexer config storage PID** . Hier sollte die UID des
SysFolders eingetragen werden, in dem später die Konfiguration für die
Indizierung abgelegt wird. Also einen neuen SysFolder anlegen und dann
dessen UID eintragen.


Zend Lucene (zendPath und luceneIndexDir)
"""""""""""""""""""""""""""""""""""""""""

Wenn man Zend Lucene als SearchEngine verwenden möchte, dann muss man
noch den Pfad zum Zend Framework eintragen. Außerdem wird das
Verzeichnis benötigt, in dem Lucene die Indexdaten ablegen soll.
Dieses Verzeichnis sollte Außer dem des Webroots liegen, muss aber für
PHP vom Webserver aus beschreibbar sein.


Apache Tika (tikaJar und tikaLocaleType)
""""""""""""""""""""""""""""""""""""""""

Mit Tika lassen sich Informationen aus Binärdateien wie Bildern,
Videos, aber auch aus PDF- oder Worddokumenten extrahieren. Wenn man
Tika verwenden möchte, dann sollte man noch den kompletten Pfad zum
Tika-Jarfile angeben.

Hinweis: Apache Solr hat ebenfalls eine Integration für Tika. Wenn
diese verwendet wird, muss das Tika-Jar hier nicht angegeben werden.

Hinweis: Wenn eine eigene Tika Lib angegeben wird, dann sollte
tikaLokaleType in der Extension Konfiguration gesetzt werden. Dieser
ist notwendig da es sonst zu Problemen mit Dateien kommen kann, welche
Umlaute/Sonderzeichen im Dateinamen haben. de\_DE.UTF-8 ermöglicht es
z.B. Dateien mit Umlauten zu verarbeiten. Ggf. müssen verschiedene
Typen probiert werden. Auf Windows Systemen ist diese Einstellung
egal.

Sekunden bis Queue Einträge gelöscht werden (secondsToKeepQueueEntries)
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Es kann konfiguriert werden, ab welchem Alter gelöschte Queue Einträge
vollständig entfernt werden. Auf diese Weise bleibt die Queue Tabelle
relativ klein.

