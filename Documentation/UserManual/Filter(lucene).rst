

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


Filter (LUCENE)
---------------

SearchForm
^^^^^^^^^^

Filter, der den Suchbegriff über ein Formular vom Nutzer abfragt. Dieser Filter ist standard-mäßig aktiviert.

Default-Konfiguration:

.. code-block:: ts

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

Aktivierung dieses Filters über:

.. code-block:: ts

   plugin.tx_mksearch.search.filter < lib.mksearch.filter.searchForm # diesen evtl. Pfad anpassen, falls Filter komplett neu konfiguriert wird

SearchByReferer
^^^^^^^^^^^^^^^

Filter, der den Suchbegriff aus dem HTTP-Referer extrahiert. Bei Verwendung dieses Filters muss das Template search.html angepasst werden (Marker ###SEARCH_FORM### entfernen), um das Formular des Standard-Filters SearchForm auszublenden.

Default-Konfiguration:

.. code-block:: ts

   lib.mksearch.filter {
      # Alternative filter which receives the search term from the
      # HTTP referer URL. This could be used to search our own site
      # for the search term the user just entered on Google website.
      searchByReferer = tx_mksearch_filter_SearchByReferer
      searchByReferer {
         referers {
            google {
               # Which URL? All URLs ending with "google.xx[x]"
               urlRegEx = /\.google\.[a-z]{2,3}\//i
               # How to find the search term within the URL? Here it's the "q" parameter.
               # The term within the (first and only) brackets () is used.
               searchTermRegEx = /[?&]q=([^&]*)/
               # How to separate words within the url encoded search term?
               searchTermDelimiterRegEx = /\++/
               # Type of logical conjunction in our own page search: "or" [default] or "and"
               searchTermOperator = or
            }
            yahoo {
               urlRegEx = /\.yahoo\.[a-z]{2,3}\//i
               searchTermRegEx = /[?&]p=([^&]*)/
               searchTermDelimiterRegEx = /\++/
               searchTermOperator = or
            }
         }
      }
   }

Aktivierung dieses Filters über:

.. code-block:: ts

   plugin.tx_mksearch.search.filter < lib.mksearch.filter.searchByReferer # diesen evtl. Pfad anpassen, falls Filter komplett neu konfiguriert wird
