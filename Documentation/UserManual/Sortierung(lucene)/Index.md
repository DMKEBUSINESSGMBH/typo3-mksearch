Sortierung (LUCENE)
===================

Die Sortierung kann analog zu SOLR konfiguriert werden und bietet die gleichen Marker für das Template an. Der Pfad für die TS Konfiguration lautet: **plugin.tx\_mksearch.searchlucene.filter.sort**

Eine Ausnahme bildet eine Zufallssortierung. Diese kann über **plugin.tx\_mksearch.searchlucene.filter.options.sortRandom = 1** aktiviert werden und ignoriert jegliche vorherige Sortierung. Im Gegensatz zu SOLR ist das die einzige Möglichkeit mittels Lucene per Zufall zu sortieren. Für SOLR gibt es andere (elegantere) Lösungen.
