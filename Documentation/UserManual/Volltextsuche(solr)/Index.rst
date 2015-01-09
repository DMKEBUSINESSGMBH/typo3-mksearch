

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


Volltextsuche (SOLR)
^^^^^^^^^^^^^^^^^^^^

Für die deutsche Sprache gibt es einen Feldtyp, der dafür
prädestiniert ist: full\_text\_german. Alle relevanten Informationen
sollten in diesem Feld indiziert werden. Es gibt auch noch den Feldtyp
text\_german. Dieser ist z.B: wichtig für Felder, die in der Highlight
Komponente verwendet werden sollen.

Folgende Möglichkeiten hat man um die Felder des Suchformulars für die
Suche bekannt zu machen. In diesem Beispiel wird mit dem Parameter
mksearch[term] im Feld “text” gesucht. Dabei wird zusätzlich immer
“contentType:\*” gesetzt.

.. code-block:: ts

   plugin.tx_mksearch.searchsolr.filter{
      # Die hier konfigurierten Parameter werden als Marker im Term-String verwendet
      # Es sind verschiedene Extensions möglich
      params.mksearch {
         term = TEXT   
         term {
            field = term
            ### der Wrap muss beim DisMaxRequestHandler geleert werden!
            wrap = AND text:|
            fieldRequired = term
            required = term
            
            preUserFunc = tx_mksearch_util_UserFunc->searchSolrOptions
            preUserFunc {
               ### wo steckt der combination parameter?
               qualifier = mksearch
               
               ### wird der DisMaxRequestHandler genutzt?
               dismax = 0
      
               ### in Anführungszeichen setzen?
               quote = 1
      
               ### for fuzzy search!   
               fuzzySlop = 0.2
      
               ### remove solr control characters?
               sanitize = 1
      
               ### wie sollen die einzelnen wörter im suchterm verbunden werden
               combination = or
      
               ### sollen wildcards vor und dem term gesetzt werden?
               ### besser durch eine ordentliche filter chain in der schema.xml
               ### realisieren
               wildcard = 0
            }
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


