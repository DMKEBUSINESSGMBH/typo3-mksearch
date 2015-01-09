

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


Sortierung (SOLR)
^^^^^^^^^^^^^^^^^

Felder nach denen sortiert werden soll, dürfen nicht tokenized sein
und müssen auf indexed=true stehen. multivalue geht auch nicht.
tokenized Felder gehen nur wenn der Tokenizer der KeywordTokenizer
ist. Ansonsten sollte ein String Feld genutzt werden. Der Hintergrund
ist das bei der Sortierung nur ein Token vorhanden sein darf. Wenn
also nach einem Textfeld sortiert werden soll, dann muss der Feldtyp
"text\_sort" aus der Beispiel schema.xml verwendet werden. Dieses
bereitet den Text auch noch auf. Dabei werden unerwünschte Zeichen
entfernt und Umlaute transformiert..

Wenn nach mehreren Feldern sortiert werden soll, dann können diese
entweder zur query Zeit übergeben werden oder diese werden zur index
Zeit zusammengefasst. Da das Feld für die Sortierung nicht multivalue
sein darf, kann copyField nicht verwendet werden. Die Felder müssen
daher anders zusammengefasst werden. Dafür gibt es in der
solrconfig-x.xml die updateRequestProcessorChain clone-for-sort-title.

Im filter tx\_mksearch\_filter\_SolrBase wurde die Möglichkeit für
eine einfache Sortierung integriert. Die Sortierung kann zurzeit wie
folgt über Parameter übergebenwerden:

::

   // Sortier aufsteigend nach uid
   $params['sort'] = uid
   // Sortier absteigend nach uid
   $params['sort'] = uid desc
   // Sortier absteigend nach uid
   $params['sort'] = uid
   $params['sortorder'] = desc


Konfiguration
"""""""""""""


((generated))
~~~~~~~~~~~~~

plugin.tx\_mksearch.searchsolr.filter.sort {### Definiert Felder, für die zusätzliche Marker für die Sortierung integriert werden sollenfields = uid, title### Konfiguration für die Sortierungs-Linkslink.pid = 0### TS für die Order-Felderuid\_order = CASEuid\_order {key.field = uid\_orderdefault = TEXTdesc = TEXTdesc.value = headerSortDownasc = TEXTasc.value = headerSortUp}title\_order < .uid\_ordertitle\_order.key.field = title\_order}
'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''


Marker
""""""

Folgende Marker werden im Template anhand der Konfiguration oben
bereitgestellt:

::

   ###SORT_UID_ORDER### = asc
   ###SORT_UID_LINKURL### = index.php?mksearch[sort]=uid&mksearch[sortorder]=asc
   ###SORT_UID_LINK### = wrappedArray mit dem A-Tag
   ###SORT_TITLE_ORDER### = asc
   ###SORT_TITLE_LINKURL### = index.php?mksearch[sort]=title&mksearch[sortorder]=asc
   ###SORT_TITLE_LINK### = wrappedArray mit dem A-Tag


((generated))
~~~~~~~~~~~~~

über verschiedene Felder
''''''''''''''''''''''''

Contenttypen müssen z.B. ihren Titel nicht immer in das Feld title
schreiben. Dadurch lässt es sich nicht einfach bewerkstelligen nach
Tiel zu sortieren da nur über ein einzelnes Feld sortiert werden kann,
die Typen aber nicht das gleich haben. Die Lösung ist einfach in der
schema.xml ein neues Feld für die Sortierung anzulegen. Dort werden
alle Felder der einzelnen Contenttypen reinkopiert, die jeweils den
z.B. den Titel repäsentieren.

