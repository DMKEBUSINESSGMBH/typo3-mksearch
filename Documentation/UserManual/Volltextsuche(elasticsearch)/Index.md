Volltextsuche (ElasticSearch)
=============================

Die Konfiguration geschieht im großen und ganzen analog zu SOLR. Es gibt aber noch nicht die preUserFunc um umfangreicher Konfiguration zu ermöglichen.

Folgende Möglichkeiten hat man um die Felder des Suchformulars für die Suche bekannt zu machen. In diesem Beispiel wird mit dem Parameter mksearch[term] im Feld “text” gesucht. Dabei wird zusätzlich immer “contentType:\*” gesetzt.

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.elasticsearch.filter{
   # Die hier konfigurierten Parameter werden als Marker im Term-String verwendet
   # Es sind verschiedene Extensions möglich
   params.mksearch {
      term = TEXT   
      term {
         field = term
         #wrap = AND text:|
         fieldRequired = term
         required = term
      }
   }

   # So wurden auch alle Parameter von tt_news ausgelesen
   #params.tt_news {
   #}

   fields {
      ### Die Marker haben das Format ###PARAM_EXTQUALIFIER_PARAMNAME###
      ### beim DisMaxRequestHandler darf hier nur
      ### PARAM_MKSEARCH_TERM### stehen!
      term = contentType:* ###PARAM_MKSEARCH_TERM###
   }
}
~~~~
