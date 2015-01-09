

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


Highlighting (SOLR)
^^^^^^^^^^^^^^^^^^^

Solr bietet die Möglichkeit, Suchbegriffe zu highlighten. Dazu muss im
requestHandler folgendes eingetragen werden:

::

   <str name="hl">true</str>
   <str name="hl.fl">abstract,text,title...</str>

In hl.fl kommen alle Felder, in welchen Highlights gesetzt werden
sollen. Das sollten die Felder sein, welche im FE ausgegeben werden!

Achtung!!!

Die Felder dürfen keine string Felder sein da Felder, welche ein
highlight erhalten sollten, tokenized sein müssen. Ich empfehle daher
das dynamische Feld “\*\_s” nicht zum Typ string sondern zum Typ text
zu machen. Das sollte in Ordnung sein.Wenn auch Teile eines Wortes
hervorgehoben werden sollen, dann muss der Feldtyp einen Ngram Filter
enthalten oder Stemming durchführen.

Diese Highlightings werden von Solr separat von den eigentlichen
Dokumenten in Form von Snippets zurück gegeben. Die Snippets sind
dabei genau das gleiche wie in der Google Suche. Dementsprechend wird
eine Möglichkeit benötigt die Highlightings in das jeweilige Dokument
zu bekommen. Dafür gibt es 2 Möglichkeiten.

1. wenn die Option overrideWithHl ($options['overrideWithHl']) auf
true gesetzt wird, dann werden die eigentlichen Inhaltsfelder mit den
Snippets des Highlightings überschrieben. Dabei muss man die Solr
Option hl.fragsize beachten, welche die Länge der Snippets bestimmt.

2. wenn die Option overrideWithHl nicht gesetzt wurde dann werden alle
Highlightings in eigene Felder nach folgendem Schema geschrieben:
$Feldname\_hl. Dabei wäre dann möglich die Felder flexibel über TS
überschreiben zu lassen bspw. so: content.override.field = content\_hl

Wie die Highlights ausgegeben werden, wird in solrconfig.xml
eingestellt. Z.B. erzeugt folgende Konfig Highlights die fett und
kursiv sind:

::

   <searchComponent class="solr.HighlightComponent" name="highlight">
      <highlighting>
        <formatter name="html" 
                   default="true"
                   class="solr.highlight.HtmlFormatter">
          <lst name="defaults">
            <str name="hl.simple.pre"><![CDATA[<em><strong>]]></str>
            <str name="hl.simple.post"><![CDATA[</strong></em>]]></str>
          </lst>
        </formatter>
      </highlighting>
    </searchComponent>

