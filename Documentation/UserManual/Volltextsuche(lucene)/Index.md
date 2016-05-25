Volltextsuche (LUCENE)
======================

Die Konfiguration geschieht im großen und ganzen analog zu SOLR.

Folgende Möglichkeiten hat man um die Felder des Suchformulars für die Suche bekannt zu machen. In diesem Beispiel wird mit dem Parameter mksearch[term] im Feld “text” gesucht. Dabei wird zusätzlich immer “contentType:\*” gesetzt.

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchlucene.filter{

   # Die hier konfigurierten Parameter werden als Marker im Term-String verwendet
   # Es sind verschiedene Extensions möglich
   params.mksearch {
      term = TEXT
      term {
         field = term

         # "-" vor ein Feld um den Wert auszuschließen, "+" um es zu einzuschließen
         # bzw. mit und zu verknüpfen. alternativ kann auch combination
         # in der preUserFunc auf "and" gesetzt werden. allerdings funtkioniert das
         # nur wenn die fields nicht explizit gesetzt sind
         #wrap = +|
         required = 1

         preUserFunc = tx_mksearch_util_UserFunc->searchLuceneOptions
         preUserFunc {
            ### wo steckt der combination parameter?
            qualifier = mksearch

            ## in Anführungszeichen setzen? sollte nicht in verbindung mit
            wildcard

            ## suchen verwendet werden
            quote = 1

            ### remove lucene control characters?
            sanitize = 1

            ### wie sollen die einzelnen wörter im suchterm verbunden werden
            combination = and

            ### sollen wildcards vor und dem term gesetzt werden?
            wildcard = 1

            ### sollen klammern um den term? das nur nutzen wenn in einem konkreten
            ### feld gesucht wird. wenn die default fields verwendet werden, dann
            ### keine klammern
            dismax = 1
         }
      }
   }

   # So wurden auch alle Parameter von tt_news ausgelesen
   #params.tt_news {
   #}

   fields {
      # Die Marker haben das Format ###PARAM_EXTQUALIFIER_PARAMNAME###
      # "-" vor ein Feld um den Wert auszuschließen, "+" um es zu einzuschließen
      # bzw. mit und zu verknüpfen
      term = +contentType:* ###PARAM_MKSEARCH_TERM###
   }
}      
~~~~
