

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


Autocomplete (SOLR)
^^^^^^^^^^^^^^^^^^^

MKsearch bietet ein autocomplete Feature wenn Solr > 3.x.x verwendet
wird. Dieses kann über die TS Konfiguration
plugin.tx\_mksearch.searchsolr.autocomplete.enableaktiviert werden.
Überplugin.tx\_mksearch.searchsolr.autocomplete.elementSelectorkann
der jquery Element Selektor angegeben werden, welchem die autocomplete
Liste angehangen werden soll. Damit autocomplete funktioniert,
registriert mksearch einen eigenen Seitentyp (540) auf welchem sich
ein mksearch Pluginbefindet.

Der Standard Query Type ist “/suggest”. Dieser kann aber geändert
werden überlib.mksearchAjaxPage.searchsolr.requestHandler=/suggest

Nun muss noch Solr für autocomplete vorbereitet werden. Wir benötigen
hierfür zunächst ein neues Feld, welches die Informationen für die
autocomplete Vorschläge enthält. Dazu kann der Feldtyp
“text\_autocomplete” verwendet werden, welcher in der Beispiel
schema.xml enthalten ist. In der schema.xml gibt es auch schon ein
Beispiel für das Feld “text\_autocomplete\_phrases”, welches später
unsere Vorschläge enthält.

Des Weiteren benötigen wir noch den Request Handler für den Query Type
“/suggest” und die notwendige Search Component. Für beides gibt es
bereits Beispiele in der EXT:mksearch/solr/solrconfig(-3.x oder
-4.0).xml. Die Search Component heißt “suggest\_fst”. So heißt auch
der jeweilige Index, für welchen im Data Ordner von Solr ein
entsprechender Ordner angelegt wird.


SOLR 3.x und SOLR 4.x
"""""""""""""""""""""

Von Haus aus parsed Solr 3.x die Query, welche an die Suggest
Komponente gegeben wird, mit einem Whitespace Tokenizer. Es gibt auch
keine Möglichkeit dieses Verhalten zu unterbinden. So gibt es bei der
Eingabe von mehreren Wörtern, Vorschläge für jedes einzelne Wort aber
nicht für die gesamte Wortgruppe im Stück. Es gibt allerdings die
Möglichkeit einen eigenen Query Converter anzugegeben. Mksearch
liefert den Query Converter
org.dmk.solr.spelling.MultiWordSpellingQueryConverter bzw.
org.dmk.solr4.spelling.MultiWordSpellingQueryConverter. Dieser ist in
der Beispiel solrconfig-3.x.xml bzw. Solrconfig-4.x.xml auch für die
“/suggest” Komponente konfiguriert. Damit Solr diesen findet muss die
.jar Datei EXT:mksearch/solr/lib/dmk-solr-core-3.5.0.jar bzw.
EXT:mksearch/solr/lib/Dmk-MultiWordSpellingQueryConverter-Solr4.jar in
den Ordner $SOLRHOME/lib kopiert/verlinkt werden. Wo das $SOLRHOME
ist, lässt sich z.B. in der Solr Management Konsole auslesen. Entweder
ist das der Ordner, der die solr.xml enthält oder es ist der jeweilige
Core Ordner (z.B. $SOLRHOME/$COREHOME/lib). Wenn die .jar Datei am
falschen Ort liegt, wird diese nicht automatisch von Solr gefunden und
es gibt einen Fehler. Der Ordner muss ggf. angelegt werden.
Anschließend werden ganze Wortgruppen unterstützt.

Wie groß diese Wortgruppen sind, hängt von der Konfiguration des
Shingle Filters im Feldtyp “text\_autocomplete” ab.

Das gesamte TS Setup für das Suchplugin, welches die Vorschläge
liefert, befindet sich inlib.mksearchAjaxPage.searchsolr

Wenn Sie die Suggest-Komponente nachträglich integrieren, müssen Sie
unbedingt eine Neuindizierung aller Daten vornehmen!

