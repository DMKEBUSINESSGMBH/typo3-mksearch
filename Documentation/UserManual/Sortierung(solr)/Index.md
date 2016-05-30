Sortierung (SOLR)
=================

Die Standardklasse lautet tx\_mksearch\_filter\_SolrBase.

Felder nach denen sortiert werden soll, dürfen nicht tokenized sein und müssen auf indexed=true stehen. multivalue geht auch nicht. tokenized Felder gehen nur wenn der Tokenizer der KeywordTokenizer ist. Ansonsten sollte ein String Feld genutzt werden. Der Hintergrund ist das bei der Sortierung nur ein Token vorhanden sein darf. Wenn also nach einem Textfeld sortiert werden soll, dann muss der Feldtyp "text\_sort" aus der Beispiel schema.xml verwendet werden. Dieses bereitet den Text auch noch auf. Dabei werden unerwünschte Zeichen entfernt und Umlaute transformiert..

Wenn nach mehreren Feldern sortiert werden soll, dann können diese entweder zur query Zeit übergeben werden oder diese werden zur index Zeit zusammengefasst. Da das Feld für die Sortierung nicht multivalue sein darf, kann copyField nicht verwendet werden. Die Felder müssen daher anders zusammengefasst werden. Dafür gibt es in der solrconfig-x.xml die updateRequestProcessorChain clone-for-sort-title.

Im Filter tx\_mksearch\_filter\_SolrBase sind zwei unterschiedliche Möglichkeiten für eine Sortierung integriert.

Variante 1
----------

Die Sortierung kann über frei definierbare formfields konfiguriert werden. Folgendes Typoscript:

~~~~ {.sourceCode .ts}
searchsolr.filter.default.formfields.sort {
  default = score
  activeMark = selected="selected"
  values.10.value = score desc
  values.10.caption = ###LABEL_SCORE###
  values.20.value = tstamp asc
  values.20.caption = ###LABEL_TIME_ASC###
  values.30.value = tstamp desc
  values.30.caption = ###LABEL_TIME_DESC###
}
~~~~

Erzeugt aus folgender HTML-Vorlage

~~~~ {.sourceCode .html}
<label class="sort"> Sortierung
 <select name="###SEARCH_FILTER_SORT_FORM_NAME###">
  <option value="###SEARCH_FILTER_SORT_UID###" ###SEARCH_FILTER_SORT_SELECTED###>###SEARCH_FILTER_SORT_CAPTION###</option>
 </select>
</label>
~~~~

Diesen Ergebnis-String:

~~~~ {.sourceCode .html}
<label class="sort"> Sortierung
 <select name="mksearch[sort]">
  <option value="score desc" >Score</option>
  <option value="tstamp asc" selected="selected">Aktualität aufsteigend</option>
  <option value="tstamp desc" >Aktualität absteigend</option>
 </select>
</label>
~~~~

Da man den Marker für das aktive Feld konfigurieren kann, läßt sich die Ausgabe auch sehr schnell in eine Variante mit Radio-Buttons umbauen.

Des weiteren kann man die Ausgabe auch über eine Liste von Links/URLs realisieren. Dazu muss zunächst das Typoscript um die Konfiguration für den Link erweitert werden:

~~~~ {.sourceCode .ts}
searchsolr.filter.default.formfields.sort {
  links.show {
    pid = 0
    useKeepVars = 1
    _cfg.params.sort = uid
    atagparams.class = CASE
    atagparams.class.key.field = selected
    atagparams.class.1 = TEXT
    atagparams.class.1.value = active
    atagparams.class.default = TEXT
    atagparams.class.default.value = inactive
  }
  default = score
  activeMark = 1
~~~~

Hier im Beispiel wird der aktive Link gleich noch mit einer speziellen CSS-Klasse versehen. Wichtig ist auch die letzte Zeile. Da wird der activeMark = 1 gesetzt, damit der aktive Eintrag im Typoscript besser ausgewertet werden kann.

Nun noch das HTML-Template anpassen:

~~~~ {.sourceCode .html}
###SEARCH_FILTER_SORT_SHOWLINK### ###SEARCH_FILTER_SORT_CAPTION### ###SEARCH_FILTER_SORT_SHOWLINK###
~~~~


Eine Default-Sortierung kann man per TypoScript vorgeben. Dabei muss die Sortierreihenfolge zwingen angegeben werden:
~~~~ {.sourceCode .ts}
lib.mksearch {
	defaultsolrfilter.options.sort = crdate_i desc
}
~~~~

Variante 2
----------

**Diese Variante sollte nicht mehr verwendet werden! Alles was hier beschrieben ist funktioniert direkt auch in Variante 1 und ist da besser umgesetzt!**

Die Sortierung kann zurzeit wie folgt über Parameter übergeben werden:

~~~~ {.sourceCode .php}
// Sortier aufsteigend nach uid
$params['sort'] = uid
// Sortier absteigend nach uid
$params['sort'] = uid desc
// Sortier absteigend nach uid
$params['sort'] = uid
$params['sortorder'] = desc
~~~~

Beispielkonfiguration für die Ausgabe von Sortierungslinks im Template

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.searchsolr.filter.sort {
   ### Definiert Felder, für die zusätzliche Marker für die Sortierung integriert werden sollen
   fields = uid, title
   ### Konfiguration für die Sortierungs-Links
   link.pid = 0
   ### TS für die Order-Felder
   uid_order = CASE
   uid_order {
      key.field = uid_order
      default = TEXT

      desc = TEXT
      desc.value = headerSortDown

      asc = TEXT
      asc.value = headerSortUp
   }
   title_order < .uid_order
   title_order.key.field = title_order
}
~~~~

Marker
------

Folgende Marker werden im Template anhand der Konfiguration oben bereitgestellt:

    ###SORT_UID_ORDER### = asc
    ###SORT_UID_LINKURL### = index.php?mksearch[sort]=uid&mksearch[sortorder]=asc
    ###SORT_UID_LINK### = wrappedArray mit dem A-Tag
    ###SORT_TITLE_ORDER### = asc
    ###SORT_TITLE_LINKURL### = index.php?mksearch[sort]=title&mksearch[sortorder]=asc
    ###SORT_TITLE_LINK### = wrappedArray mit dem A-Tag

### über verschiedene Felder

Contenttypen müssen z.B. ihren Titel nicht immer in das Feld title schreiben. Dadurch lässt es sich nicht einfach bewerkstelligen nach Tiel zu sortieren da nur über ein einzelnes Feld sortiert werden kann, die Typen aber nicht das gleich haben. Die Lösung ist einfach in der schema.xml ein neues Feld für die Sortierung anzulegen. Dort werden alle Felder der einzelnen Contenttypen reinkopiert, die jeweils den z.B. den Titel repäsentieren.
