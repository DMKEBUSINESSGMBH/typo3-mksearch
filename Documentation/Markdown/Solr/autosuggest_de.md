# Autosuggest und Spellchecking
Mit diesen beiden Begriffen werden Möglichkeiten beschrieben, den Anwender bei der Formulierung seiner Suchanfrage zu unterstützen. Rein technisch basieren die beiden Ansätze auf der selben Basis, für den Anwender erscheint die Unterstützung aber zu unterschiedlichen Zeitpunkten.

**Autosuggest:** Der Anwender erhält hier eine Vorschlagsliste von Begriffen oder Formulierungen während er seine Query im Suchfeld eintippt.
**Spellchecking:** Umgangssprachlich wird dieses Feature häufig als *Meinten Sie?* bezeichnet. Damit sollte auch schon klar sein, was die Funktion tut. Mit dem Ergebnis der Suche erhält der Nutzer eine Liste von alternativen Suchbegriffen, mit denen er eventuell bessere Ergebnisse erhält.

Die Umsetzung in Solr erfolgt bei beiden Funktionen über die Such-Komponente Spell-Check. Auf die Einzelheiten zur Konfiguration wird hier nicht eingegangen. Statt dessen beleuchtet diese Doku die Integration Funktion in mksearch.

## Spellchecking
Sobald in Solr der SpellChecker aktiviert ist, wird der Response von Solr um einen weiteren Block ergänzt:
```javascript
spellcheck {
  suggestions {
    form {
      numFound: 1,
      startOffset: 0,
      endOffset: 4,
      suggestion: [forst]
    }
  }
}
```
In diesem Beispiel wird der alternative Begriff **forst** für den Suchbegriff **form** vorgeschlagen.
Beim Rendern der Ergebnisseite erkennt mksearch diesen Vorschlag automatisch und bindet ihn über folgenden Subpart mit ein:

```html
	<fieldset class="suggestion">
		<!-- ###SUGGESTIONS### START -->
		Meinten Sie:
		<!-- ###SUGGESTION### START -->
		<strong>###SUGGESTION_SEARCHLINK######SUGGESTION_VALUE######SUGGESTION_SEARCHLINK###</strong>
		<!-- ###SUGGESTION### START -->
		<!-- ###SUGGESTIONS### START -->
	</fieldset>		
```

Der Begriff wird automatisch verlinkt. Die gesamte, mitgelieferte TS-Konfiguration aus dem Static-Template ist recht überschaubar:

```
plugin.tx_mksearch.searchsolr.suggestions {
	# Join terms with comma
	implode = ,
	implode.noTrimWrap = || |
	# prepare search link
	links.search {
		pid = 0
		_cfg.params.term = value
		useKeepVars = 1
		useKeepVars.skipEmpty = 1
		# allow useful parameters only
		useKeepVars.allow = submit, pagelimit, sort
		useKeepVars.add = submit=
	}
}
```
Bei der Link-Erzeugung sollten markierte Facetten oder die aktuelle Seite der Pagination nicht mit genutzt werden. Da eine neue Suche gestartet wird, würden diese ggf. zu einer leeren Trefferliste führen.

## Autosuggest
Auch hier liefert uns Solr nach entsprechender Konfiguration im Response einen Block **spellcheck**. Allerdings erfolgt die Einbindung hier über einen Ajax-Call.

**ACHTUNG**: Im Plugin von mksearch befindet sich im Tab **Search view for Solr** die Option **Enable autocomplete/suggest**. Diese Funktion ist deprecated und funktioniert nicht mehr. Der eingebundene Javascript-Code ist veraltet.

Für die Integration muss man selbst im Typoscript aktiv werden. Zunächst benötigt man einen Page-Type den man per Ajax erreichen kann. In mksearch ist dafür bereits eine Vorlage vorhanden, die nur minimal angepasst werden muss:

```
mksearchAjaxPage.10 {
	searchsolr {
		usedIndex = 1
	}
}
```

Es wird also lediglich der Solr-Index (UID) konfiguriert, der verwendet werden soll. Alternativ kann man aber auch die Typoscript-Konstante **plugin.tx_mksearch.usedIndex** verwenden.

Für die Umsetzung verwendet mksearch den speziellen Filter **tx_mksearch_filter_SolrAutocomplete**. Diese Klasse liefert als Ergebnis einen JSON-String, mit folgender Struktur:

```javascript
{
  "items":[],
  "searchUrl":"http:\/\/localhost:8983\/solr\/mycore\/select?limit=0&qt=%2Fsuggest&fq=...",
  "searchTime":"0.0012860298156738 ms",
  "numFound":null,
  "response":{},
  "facets":[],
  "suggestions":{
    "for":[
      {"uid":1,"record":{"uid":1,"value":"fortbildungsakademie","searchWord":"for"}},
      {"uid":2,"record":{"uid":2,"value":"forst","searchWord":"for"}}
    ]
  }
}
```
Bei den Vorschlägen muss man aufpassen. Die Objekte sind verschachtelt. Den eigentlichen Vorschlag findet man im Attribute record. Es kann sein, daß sich dies zukünftig noch ändert.

Nachdem diese Seite als Resource serverseitig zur Verfügung steht, ist die Integration im Suchfeld eine reine Frage des verwendeten Javascripts. Folgendes Code-Beispiel zeigt die Integration von [Twitter typeahead](https://twitter.github.io/typeahead.js/):

```javascript
jQuery(function($) {
	// MKSEARCH AUTOCOMPLETE with typeahead
	(function() {
		var searchFields = jQuery("#searchword");

		// nothing to do, there are no search fields
		if (searchFields.length === 0) {
			return;
		}
		var remoteUrl = window.location.href;
		remoteUrl = remoteUrl.split('?', 1);
		remoteUrl = remoteUrl[0];
		remoteUrl += "?type=540&mksearch[ajax]=1&mksearch[term]=";

		searchFields.typeahead({
			  minLength: 2,
			  highlight: true
			},
			{
				name: 'my-dataset',
				limit: 10,
				source: function(query, syncResults, asyncResults) {
					jQuery.ajax({
						url: remoteUrl+query,
						dataType: "json",
						success: function( data ) {
							if(data.suggestions != undefined)
								jQuery.each(data.suggestions, function(key, value) {
									jQuery.each(value, function(key, suggestion) {
										asyncResults([suggestion.record.value]);
									});
								});
						}
					});
				}
			}
		);
	})();
	// END MKSEARCH AUTOCOMPLETE with typeahead
}
```
