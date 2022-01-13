Filter (LUCENE)
===============

SearchForm
----------

Filter, der den Suchbegriff über ein Formular vom Nutzer abfragt. Dieser Filter ist standard-mäßig aktiviert.

Default-Konfiguration:

~~~~ {.sourceCode .ts}
lib.mksearch.filter {
   # Default filter which provides a standard form
   # to receive a search term
   searchForm = tx_mksearch_filter_SearchForm
   searchForm {
      # Configuration of the filter itself
      config {
         # form marker in main search template
         #  >>>   marker "SEARCH_FORM" is already defined
         #     in the default main search template!
         marker = SEARCH_FORM
         # template containing the actual form
         template = EXT:mksearch/templates/searchForm.html
         # subpart in the form template
         subpart = FORM
      }
      # Configuration of form data used for displaying the form
      form {
         searchterm.stdWrap.htmlSpecialChars = 1
         dcsearchtermheading = TEXT
         dcsearchtermheading {
            wrap = <h1>###LABEL_search_yoursearchtermwas### <em>|</em></h1>
            required = 1
            field = searchterm
            stdWrap.htmlSpecialChars = 1
         }
      }
   }
}
~~~~

Aktivierung dieses Filters über:

~~~~ {.sourceCode .ts}
plugin.tx_mksearch.search.filter < lib.mksearch.filter.searchForm # diesen evtl. Pfad anpassen, falls Filter komplett neu konfiguriert wird
~~~~
