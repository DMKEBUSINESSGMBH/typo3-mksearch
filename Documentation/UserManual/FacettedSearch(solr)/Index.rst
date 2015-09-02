

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


Facetted Search (SOLR)
----------------------

Solr bietet die Möglichkeit Fassetten in der Ergebnisliste mit
auszugeben. Natürlich unterstützt auch mksearch dieses Feature.

TODO: Notwendige Schritte erklären! Bis dahin kann es im static TypoScript nachvollzogen
werden.

Field facets
^^^^^^^^^^^^

TODO

Query facets
^^^^^^^^^^^^

Hinter einer Query-Facette steht in Solr ein bei Bedarf recht komplexer Filter-String. Um einerseits Fehler zu vermeiden, aber andererseits auch interne Logik nicht im Frontend zu veröffentlichen, können Query-Facetten für mksearch nur mit Aliases genutzt werden. Wie üblich werden die Facetten am besten in der solrconfig.xml angelegt:

.. code-block:: xml

   <requestHandler name="search" class="solr.SearchHandler">
     <lst name="defaults">
       <str name="facet">true</str>
       <str name="facet.query">{!key="date_lastweek"}datetime:[NOW-7DAYS/DAY TO NOW]</str>
       <str name="facet.query">{!key="date_lastmonth"}datetime:[NOW-1MONTH/MONTH TO NOW]</str>
       <str name="facet.query">{!key="date_older"}datetime:[* TO NOW-1YEAR/YEAR]</str>

Über den Modifier !key wird der Alias gesetzt. Über diesen Alias können die Queries auch gruppiert werden. Der Prefix vor dem ersten Unterstrich (im Beispiel date) steht für die Gruppe.

Für die Anzeige im Frontend werden alle Facetten in mksearch als Gruppen behandelt. Daher erfolgt die Ausgabe einheitlich über einen vordefinierten Block:

.. code-block:: html

      <!-- ###GROUPEDFACETS### START -->
      <fieldset class="facets">
         <legend>Filter</legend>
         <ul class="mksearch-facets">
         <!-- ###GROUPEDFACET### START -->
            <!-- ###GROUPEDFACET_HITS### START -->
            <li class="###GROUPEDFACET_FIELD###">
               ###GROUPEDFACET_DCFIELD###
               <ul class="###GROUPEDFACET_FIELD###">
                  <!-- ###GROUPEDFACET_HIT### START -->
                  <li>
                     <input
                        type="checkbox"
                        name="###GROUPEDFACET_HIT_FORM_NAME###"
                        value="###GROUPEDFACET_HIT_FORM_VALUE###"
                        id="###GROUPEDFACET_HIT_FORM_ID###"
                        ###GROUPEDFACET_HIT_ACTIVE###
                     />
                     <label for="###GROUPEDFACET_HIT_FORM_ID###">
                        ###GROUPEDFACET_HIT_DCLABEL### <!-- ###GROUPEDFACET_HIT_COUNT### -->
                     </label>
                  </li>
                  <!-- ###GROUPEDFACET_HIT### END -->
               </ul>
            </li>
            <!-- ###GROUPEDFACET_HITS### END -->
         <!-- ###GROUPEDFACET### END -->
         </ul>
      </fieldset>
      <!-- ###GROUPEDFACETS### END -->

Damit werden die Facetten im Frontend angezeigt. Allerdings müssen die technischen Keys noch in lesbare Labels übersetzt werden. Dazu im Typoscript folgendes Beispiel verwenden:

.. code-block:: ts

   plugin.tx_mksearch{
      searchsolr{
         # Formatierung der Facets
         groupedfacet.dcfield.date = TEXT
         groupedfacet.dcfield.date.value = Zeitraum:
         groupedfacet.hit {
            dclabel {
               date_lastweek = TEXT
               date_lastweek.value = Letzte Woche
               date_lastmonth = TEXT
               date_lastmonth.value = Letzter Monat
               date_older = TEXT
               date_older.value = älter als 1 Jahr
            }
         }

Damit bei Aktivierung einer Facette auch die Filter reagiert, muss diese Facette im Filter noch freigeschaltet werden. Außerdem muss der Alias auf eine konkrete Filteranweisung für Solr gemappt werden. Auch dies erfolgt natürlich per Typoscript:

.. code-block:: ts

   plugin.tx_mksearch.searchsolr {
     filter.dismax {
       # Freigabe für Query-Facets. Eine Einschränkung auf bestimmte Queries erscheint nicht sinnvoll/notwendig.
       allowedFqParams = type_query
       # Diese Anweisungen müssen identisch sein, mit den Angaben in der solrconfig.xml
       facet.queries {
         date_lastweek = datetime:[NOW-7DAYS/DAY TO NOW]
         date_lastmonth = datetime:[NOW-1MONTH/MONTH TO NOW]
         date_older = datetime:[* TO NOW-1YEAR/YEAR]
       }
     }
   }

Wir geben hier allgemein die Query-Facets frei. Eine Einschränkung auf bestimmte Queries erscheint hier nicht sinnvoll.
