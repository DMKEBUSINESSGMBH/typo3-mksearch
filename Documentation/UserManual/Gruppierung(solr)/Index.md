Gruppierung (SOLR)
=================

Wenn tt_content indiziert wird, hat man immer wieder das Problem dass man mehrere Suchergebnisse
pro Seite bekommt. Das ist meistens unerwünscht. Um das zu umgehen, kann man das grouping von Solr
nutzen. Dort könnte einfach auf das pid Feld gruppiert werden. Somit bekommt man aber Probleme bei
tt_news etc. da diese für gewöhnlich alle die gleiche pid haben.

Die Lösung ist ein eigenes Feld zu indizieren. Dieses Feld lautet "group_s" (Hinweis: Das Feld sollte String oder ähnliches sein ohne Tokenizer etc. um unerwünschte Seiteneffekte zu vermeiden). Per default wird dort
die Solr ID eingetragen. Im Moment überschreibt nur der tt_content Indexer die Methode zum liefern
des Wert und liefert stattdessen core:tt_content:$pid. In Zukunft könnte das noch konfigurierbar gestaltet werden.

Wenn die Gruppierung aktiviert wird, werden somit alle Elemente außer tt_content praktisch nicht gruppiert.

Die Gruppierung kann bequem im Plugin konfiguriert werden. Wichtig ist dabei hauptsächlich das Feld, dieses
ist per default group_s, kann aber jedes beliebige sein. Mit ngroups wird angegeben ob die Anzahl der gefundenen
Elemente sich auf die Dokumente bezieht (default) oder auf die Anzahl der Gruppen. Damit die angezeigte
Anzahl der Suchergebnisse mit der tatsächlichen Anzahl der Elemente übereinstimmt sollte das gesetzt werden, da
per default pro Gruppe nur ein Dokument geliefert wird.

Beispiel TypoScript:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr.filter.(dismax|default).options {
   group {
      enable = 1
      field = group_s

     ### per default ist die Anzahl der Ergebnisse gleich den gefundenen Dokumenten.
     ### durch die Gruppierung liefert Solr aber natürlich weniger Gruppen
     ### als Dokumente. Mit dieser Anweisung wird statt der Anzahl der gefundenen
     ### Dokumente die Anzahl der Gruppen verwendet
     useNumberOfGroupsAsSearchResultCount = 1

     ### Weitere Optionen wie group.limit noch nicht implementiert.
   }
}
~~~~

Achtung: Das könnte die Suche etwas verlangsamen.

ToDos
----------

-   Weitere Optionen wie group.limit implementieren
-   gruppierte Suchergebnisse nicht nur flach wie normale Suchergebnisse darstellen sondern tatsächlich gruppiert
-   mehr als ein Feld für die Gruppierung unterstützen
