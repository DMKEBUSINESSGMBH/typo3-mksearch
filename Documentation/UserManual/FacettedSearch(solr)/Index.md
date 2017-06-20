Facettierung (SOLR)
===================

Unter Facettierung versteht mal das Gruppieren und Zählen der Treffer einer Ergebnismenge nach verschiedenen Kriterien. In einer relationalen Datenbank entspricht dies verschiedenen GROUP BY Statements auf den Ergebnisfilter. In Solr und auch in ElasticSearch gibt es verschiedenen Arten von Facettierungen. In mksearch werden derzeit Field und Query-Facetten unterstützt.

**Hinweis:** Bei den Typoscript-Beispielen wird grundsätzlich der Dismax-Filter genutzt. Alle Beispiel funktionieren aber genauso mit dem Default-Filter. Im TS-Pfad dann einfach **dismax** durch **default** ersetzen.

Field facets
------------

Die Field-Facette ist die einfachste Möglichkeit der Facettierung in Solr. Es wird einfach nach den Werten eines bestimmten Attributes im Index gruppiert. Im Zusammenspiel mit mksearch werden aber zwei Felder benötigt. Eines für die Darstellung der Facette im Frontend und eines für die Filterung. Das ist notwendig, weil per mksearch kein direkter Zugriff auf das Solr benötigt wird.

Zunächst benötigen wir also zwei Attribute im Solr-Dokument für die Facette. In schema.xml von Solr konfigurieren wir folgende Felder:

~~~~ {.sourceCode .xml}
<field name="facet_ctype" type="string" indexed="true" stored="true" multiValued="true" />
<field name="ctype" type="string" indexed="true" stored="true" multiValued="true" />
~~~~

In der Indexer-Konfiguration wird für den Content-Indexer folgendes FixedField konfiguriert:

~~~~ {.sourceCode .ts}
fixedFields{
  facet_ctype = content<[DFS]>Seite
  ctype = content
}
~~~~

Für jedes Inhaltselement werden also fest die beide Strings **content\<[DFS]\>Seite** und **content** in das D okument geschrieben.

Für den tt\_news-Indexer sieht die Konfiguration ähnlich aus:

~~~~ {.sourceCode .ts}
fixedFields{
  facet_ctype = news<[DFS]>News-Artikel
  ctype = news
}
~~~~

Natürlich können die Attribute auch mit dynamischen Daten gefüllt werden. Hier wird aber nur dieses einfache Beispiel demonstriert. Wichtig ist, daß der Prefix im Feld **facet\_ctype** (alles vor **\<[DFS]\>**) identisch mit dem Wert in **ctype** ist. Bei Unterschieden wird die Filterung später nicht funktionieren.

Nun kann die Facette konfiguriert werden. Das erledigt man am besten in der solrconfig.xml im entsprechenden RequestHandler:

~~~~ {.sourceCode .xml}
<requestHandler name="search" class="solr.SearchHandler">
  <lst name="defaults">
    <str name="facet">true</str>
    <str name="facet.field">facet_ctype</str>
~~~~

Solr wird nun bei jedem Aufruf des RequestHandler **search** die Daten für diese Facette mitliefern. In mksearch werden die Facetten automatisch erkannt und über den Subpart **\#\#\#GROUPEDFACETS\#\#\#** ausgegeben. Man kann noch den Namen der Gruppe per Typoscript konfigurieren:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr {
  groupedfacet.dcfield.facet_ctype = TEXT
  groupedfacet.dcfield.facet_ctype.value = Inhaltstyp:
  groupedfacet.dcfield.facet_ctype.wrap = <p>|</p>
}
~~~~

Die Facetten will man natürlich nicht nur anzeigen, sondern auch für die Filterung nutzen. Dafür muss sie in mksearch zunächst freigegeben werden, natürlich per Typoscript:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr.filter.dismax {
  # Weitere Parameter kommasepariert hinzufügen
  allowedFqParams = ctype
}
~~~~

Wie man sieht, wird hier nicht **facet\_ctype** freigeschaltet, sondern nur **ctype**. Der Request-Parameter, den mksearch erzeugt, enthält aber den Wert aus **facet\_ctype**. Für News-Artikel also bspw. **news\<[DFS]\>News-Artikel**. Der Filter von mksearch erkennt diese DFS-Feld und extrahiert automatisch den Wert **news**. Damit dieser String nur im Filter auf das Solr-Attribut **ctype** angewendet werden kann, benötigen wird noch ein letztes Mapping:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr {
  groupedfacet.hit {
    mapping.field {
      facet_ctype = ctype
    }
  }
}
~~~~

Suchoperator konfigurieren
--------------------------

Wenn man mehrere Werte einer Facette filtern, dann werden diese per Default mit **AND** verknüpft. Häufig will man aber einer eine Verknüpfung per **OR**. Das lässt sich leicht im Typoscript einstellen:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr.filter.dismax {
  # Operatoren für Facetten
  filterQuery.ctype.operator = OR
}
~~~~

Tags verwenden
--------------

Häufig ist es sinnvoll, für die Erzeugung der Facette, den Filter auf darauf zu ignorieren (Vgl. Solr in Action, Kapitel 8.7.2 Tags, excludes, and multiselect faceting). Man kann dies über spezielle Tags erreichen, mit denen die fq-Parameter gekennzeichnet werden. Im Typoscript dazu folgende Anweisung setzen:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr.filter.dismax {
  filterQuery.ctype.tag = tag4ctype
}
~~~~

Nun kann man den RequestHandler in der solrconfig.xml anweisen, die Daten die Filterquery mit diesem Tag zu ignorieren:

~~~~ {.sourceCode .xml}
<requestHandler name="search" class="solr.SearchHandler">
  <lst name="defaults">
    <str name="facet">true</str>
    <str name="facet.field">{!ex=tag4ctype}facet_ctype</str>
~~~~

DFS-Feld dynamisch erzeugen
---------------------------

Wir haben weiter oben gesehen, wie der **facet\_ctype** über ein fixedField fest mit einem Wert wie **news\<[DFS]\>News-Artikel** indexiert wurde. Es ist natürlich viel häufiger notwendig diese Felder dynamisch zu erzeugen. Das soll am Beispiel der News-Kategorien kurz veranschaulicht werden. Der Anwendungsfall wird hier etwas komplexer, weil wir eine MM-Referenz zwischen der News-Meldung und der Kategorie haben. Für die korrekte Indexierung benötigen wir die kompletten Datensätze der zugeordneten News-Kategorien. Das wichtigste Werkzeug, daß mksearch hier bereitstellt, ist die **fieldsConversion**. Darüber lassen sich Werte vor der Indexierung manipulieren. Und man kann hier den stdWrap von TYPO3 nutzen.

Den Lookup der News-Kategorien bekommt man sicher auch über den stdWrap hin. Man kann es sich aber auch einfacher machen und die Arbeit an eine PHP-Funktion übergeben:

~~~~ {.sourceCode .ts}
fieldsConversion{
  category = USER
  category.userFunc = Tx_ExtKey_Package_CategorySetter->handleNews
  category.userFunc.category = TEXT
  category.userFunc.category.field = uid
  facet_category < .category
  facet_category.userFunc.uid.dataWrap = |<[DFS]>{field:title}
}

indexedFields {
   facet_category = uid
   category = uid
}
~~~~

Auch hier bitte beachten, daß wir zwei Felder vorbereiten. Einmal wird nur die UID der Kategorie gespeichert und einmal die UID zusammen mit dem Titel der Kategorie. Dazu wird lediglich ein zusätzlicher dataWrap ausgeführt. Die Angabe in **indexedFields** ist lediglich notwendig, damit die Attribute **facet\_category** und **category** vom Indexer gefüllt werden und damit die **fieldConversion** überhaupt starten kann.

Die Methode handleNews() hat nun die Aufgabe die News-Kategorien einer News-Meldung zu ermitteln und als Ergebnis zu liefern:

~~~~ {.sourceCode .php}
class Tx_ExtKey_Package_CategorySetter {
   /* wird automatisch von TYPO3 gesetzt */
   public $cObj;
   /**
    *
    * @param string $content
    * @param array $conf
    * @return string
    */
   public function handleNews($content, $conf) {
      $record = $this->cObj->data;

      $categories = $this->getNewsCategories($record['uid']);
      $result = array();
      foreach ($categories as $cat) {
         $this->cObj->data = $cat->record;
         $value = $this->cObj->cObjGetSingle($conf['userFunc.']['category'], $conf['userFunc.']['category.']);
         if($value)
            $result[] = $value;
      }
      // reset data in cObj
      $this->cObj->data = $record;

      // Return the array serialized to mksearch, since stdWrap can handle strings only. Mksearch will recognize this array.
      return serialize($result);

   }

   /**
    * Get all categories of the news record
    *
    * @param tx_rnbase_IModel $model
    * @return array[tx_rnbase_model_Base]
    */
   private function getNewsCategories($newsUid) {
      $options = array(
            'where' => 'tt_news_cat_mm.uid_local=' . $newsUid,
            'wrapperclass' => 'tx_rnbase_model_Base',
            'orderby' => 'tt_news_cat_mm.sorting ASC'
      );
      $join = ' JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign=tt_news_cat.uid AND tt_news_cat.deleted=0 ';
      $from = array('tt_news_cat' . $join, 'tt_news_cat');
      $rows = tx_rnbase_util_DB::doSelect(
            'tt_news_cat_mm.uid_foreign, tt_news_cat.uid, tt_news_cat.title, tt_news_cat.single_pid',
            $from, $options
      );
      return $rows;
   }

}
~~~~

Die Klasse holt also die Kategorien aus der Datenbank und für ihrerseits für jede Kategorie den stdWrap auf den Record aus. Das Ergebnis ist natürlich ein Array. Da der stdWrap aber als Ergebnis nur einen String liefern kann, müssen wir einen kleinen Trick anwenden. Das Ergebnis-Array wird einfach serialisiert. Die fieldConversion in mksearch prüft automatisch, ob ein serialisierter String zurückgeliefert wird. In dem Fall werden die Daten vor der Indexierung noch deserialisiert.

Ein letzter Hinweis zu diesem Beispiel: Die PHP-Klasse kann leider nicht über includeLibs geladen werden. Also entweder über Autoloading bekannt machen, oder den include in die ext\_localconf.php integrieren.

DFS-Feld direkt im Indexer hinzufügen
---------------------------

Das hinzufügen von DFS Feldern bzw. Key-Value-Facetten, kann auch direkt in Indexern genutzt werden.

~~~~ {.sourceCode .php}
    $recordIndexMapping = array(
        'uid' => 'uid_mi',
    );
    $this->indexArrayOfModelsByMapping(
        $models,
        $recordIndexMapping,
        $indexDoc
    );
    $facetValues = array();
    foreach ($models as $model) {
        $facetValues[$model->getUid()] = $model->getTitle();
    }
    $indexDoc->addField(
        'my_facet_dfs_ms',
        tx_mksearch_util_KeyValueFacet::getInstance()->buildFacetValues(array_keys($facetValues), array_values($facetValues)),
        'keyword'
    );
~~~~

Somit hat das Feld my_facet_dfs_ms z.B. den Wert "UID-der-Facette\<DFS\>Label-der-Facette". Wichtig ist, dass es in diesem Fall noch ein extra Feld gibt, welches den Wert aus dem ersten Teil des DFS Strings einzeln enthält. In diesem Fall wäre es die UID bzw. das Feld uid_mi. Dieses wird dann für die tatsächliche Anfrage genutzt, mit dem entsprechenden Mapping.

~~~~ {.sourceCode .ts}
    plugin.tx_mksearch.searchsolr {
        groupedfacet {
            hit.mapping.field {
                my_facet_dfs_ms = uid_mi
            }
            dcfield {
                my_facet_dfs_ms = TEXT
                my_facet_dfs_ms.value = Mein Facettenlabel
            }
        }
    }
~~~~

Somit lassen sich Facetten anhand von Strings bauen (der 2. Teil des DFS Wert wird als Label verwendet), für die tatsächliche Abfrage wird aber die UID verwendet (immer der 1. Teil des DFS Wert). Als Feld für Links wird immer das gemappte genommen. Das DFS Feld ermittelt lediglich die Kandidaten für die Facetten.

**Achtung**: Bei der Konfiguration für die erlaubten Facettenfelder (allowedFqParams), muss dann das gemappte Feld (uid_mi) verwendet werden, da dieses in Links verwendet wird nud somit das tatsächlich verwendete Feld ist.

Damit umgeht man Beschränkungen, wenn nach Strings facettiert wird. Denn enthält ein Wert z.B. ein Solr Kontrollzeichen wie "-", dann wird das zwar indiziert und auch als Wert in einem fq Link verwendet, vor der Solr Anfrage werden die Kontrollzeichen aber entfernt. Damit erhält man keine Ergebnisse.

@TODO Sortieroption der DFS Facetten erläutern!

Query facets
------------

Hinter einer Query-Facette steht in Solr ein bei Bedarf recht komplexer Filter-String. Um einerseits Fehler zu vermeiden, aber andererseits auch interne Logik nicht im Frontend zu veröffentlichen, können Query-Facetten für mksearch nur mit Aliases genutzt werden. Wie üblich werden die Facetten am besten in der solrconfig.xml angelegt:

~~~~ {.sourceCode .xml}
<requestHandler name="search" class="solr.SearchHandler">
  <lst name="defaults">
    <str name="facet">true</str>
    <str name="facet.query">{!key="date_lastweek"}datetime:[NOW-7DAYS/DAY TO NOW]</str>
    <str name="facet.query">{!key="date_lastmonth"}datetime:[NOW-1MONTH/MONTH TO NOW]</str>
    <str name="facet.query">{!key="date_older"}datetime:[* TO NOW-1YEAR/YEAR]</str>
~~~~

Über den Modifier !key wird der Alias gesetzt. Über diesen Alias können die Queries auch gruppiert werden. Der Prefix vor dem ersten Unterstrich (im Beispiel date) steht für die Gruppe.

Für die Anzeige im Frontend werden alle Facetten in mksearch als Gruppen behandelt. Daher erfolgt die Ausgabe einheitlich über einen vordefinierten Block:

~~~~ {.sourceCode .html}
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
~~~~

Damit werden die Facetten im Frontend angezeigt. Allerdings müssen die technischen Keys noch in lesbare Labels übersetzt werden. Dazu im Typoscript folgendes Beispiel verwenden:

~~~~ {.sourceCode .ts}
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
~~~~

Damit bei Aktivierung einer Facette auch die Filter reagiert, muss diese Facette im Filter noch freigeschaltet werden. Außerdem muss der Alias auf eine konkrete Filteranweisung für Solr gemappt werden. Auch dies erfolgt natürlich per Typoscript:

~~~~ {.sourceCode .ts}
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
~~~~

Wir geben hier allgemein die Query-Facets frei. Eine Einschränkung auf bestimmte Queries erscheint hier nicht sinnvoll.

Pivot facets
------------

Die Pivot bzw Hierarchical Facets können verwendet werden, um mehrere zu Facetierende Felder in einer Baumstruktur auszugeben. Die Pivot Facets reduzieren die Solr-Performance und sollten nur mit bedacht eingesetzt werden. Wie üblich werden die Facetten am besten in der solrconfig.xml angelegt:

~~~~ {.sourceCode .xml}
<requestHandler name="search" class="solr.SearchHandler">
    <lst name="defaults">
        <str name="facet">true</str>
        <str name="facet.pivot">fiel_one,fiel_two,fiel_three</str>
        <str name="facet.pivot">field_main,fiel_sub</str>
    </lst>
</requestHandler>
~~~~

Die Pivot Facets können sich durch die freie Angabe von Feldern beliebig verschachteln. Jede Facette kann dadurch nun Kinder beinhalten. Um das zusammen bauen kümmert sich der Facet-Builder. Um die Kindfacetten nun auszugeben ist eine Anpassung des Templates notwendig. Die relevanten Bereiche sind die CHILD-Subparts. Diese müsse so weit verschachtelt im Template angegeben werden, wie Kindfacetten ,öglich sind.

~~~~ {.sourceCode .html}
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
                  <!-- ###GROUPEDFACET_HIT_CHILDS### START -->
                     <ul class="###GROUPEDFACET_HIT_FIELD###">
                        <!-- ###GROUPEDFACET_HIT_CHILD### START -->
                           <li>
                              <input
                                 type="checkbox"
                                 name="###GROUPEDFACET_HIT_CHILD_FORM_NAME###"
                                 value="###GROUPEDFACET_HIT_CHILD_FORM_VALUE###"
                                 id="###GROUPEDFACET_HIT_CHILD_FORM_ID###"
                                 ###GROUPEDFACET_HIT_CHILD_ACTIVE###
                              />
                              <label for="###GROUPEDFACET_HIT_CHILD_FORM_ID###">
                                 ###GROUPEDFACET_HIT_CHILD_DCLABEL### <!-- ###GROUPEDFACET_HIT_CHILD_COUNT### -->
                              </label>
                              <!-- ###GROUPEDFACET_HIT_CHILD_CHILDS### START -->
                                 <ul class="###GROUPEDFACET_HIT_CHILD_FIELD###">
                                    <!-- ###GROUPEDFACET_HIT_CHILD_CHILD### START -->
                                       <li>
                                          <input
                                             type="checkbox"
                                             name="###GROUPEDFACET_HIT_CHILD_CHILD_FORM_NAME###"
                                             value="###GROUPEDFACET_HIT_CHILD_CHILD_FORM_VALUE###"
                                             id="###GROUPEDFACET_HIT_CHILD_CHILD_FORM_ID###"
                                             ###GROUPEDFACET_HIT_CHILD_CHILD_ACTIVE###
                                          />
                                          <label for="###GROUPEDFACET_HIT_CHILD_CHILD_FORM_ID###">
                                             ###GROUPEDFACET_HIT_CHILD_CHILD_DCLABEL### <!-- ###GROUPEDFACET_HIT_CHILD_CHILD_COUNT### -->
                                          </label>
                                       </li>
                                    <!-- ###GROUPEDFACET_HIT_CHILD_CHILD### END -->
                                 </ul>
                              <!-- ###GROUPEDFACET_HIT_CHILD_CHILDS### END -->
                           </li>
                        <!-- ###GROUPEDFACET_HIT_CHILD### END -->
                     </ul>
                  <!-- ###GROUPEDFACET_HIT_CHILDS### END -->
               </li>
               <!-- ###GROUPEDFACET_HIT### END -->
            </ul>
         </li>
         <!-- ###GROUPEDFACET_HITS### END -->
      <!-- ###GROUPEDFACET### END -->
      </ul>
   </fieldset>
<!-- ###GROUPEDFACETS### END -->
~~~~
