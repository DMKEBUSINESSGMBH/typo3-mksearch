Changelog
=========

9.5.4
-----

-   add limit for max results for lucene search
-   updated Apache Solr library
-   added missing options on A21Glossary indexer
-   fix default date in queue table to work with strict mode in MySQL
-   don't optimize Solr after each commit
-   make indexing scheduler run always in parallel (DB update needed)


9.5.3
-----

-   offline records (pid = -1) are not longer treated as indexable 
except this pid is explicitly included in include.pages configuration of indexer.
It might be a good idea to reindex all data if you use workspaces. You also might
want to set deleteIfNotIndexable = 1 in the indexer configuration to have already
indexed records deleted.

9.5.2
-----

-   fixed compatibility issues for TYPO3 9.5

9.5.0
-----

-   added TYPO3 9.5 support
-   dropped TYPO3 6.2 and 7.6 support

3.2.16
-----

-   bugfixes and cleanup

3.2.15
-----

-   ban any url with mksearch parameters from realurl caching

3.2.14
-----

-   bugfixes and cleanup
-   added postTikaCommandParameters option

3.2.13
-----

-   bugfixes and cleanup
-   index the complete FAL properties including metadata

3.2.12
-----

-   bugfixes and cleanup

3.2.11
-----

-   added possibility to sort multivalued fields marker values

3.2.10
-----

-   bugfixes and cleanup

3.2.9
-----

-   bugfixes


3.2.8
-----

-   add support for new domain model in index util for index model by mapping
-   bugfixes and cleanup
-   added stronger defaults
-   supported dok types can be configured for tt_content indexers

3.2.7
-----

-   an own location header can be used for softlink redirects
-   bugfixes
-   translated labels of backend module into german

3.2.6
-----

-   bugfixes

3.2.5
-----

-   bugfixes

3.2.4
-----

-   index related links of tx_news

3.2.3
-----

-   updated solr example config

3.2.2
-----

-   add no_search field to rootline fields
-   bugfixes

3.2.1
-----

-   bugfixes

3.2.0
-----

-   method to get localized extbase model
-   bugfix multivalue fields should always be an array
-   make it possible to respect the no_:search flag for the rootline
-   bugfix getting tx_news model

3.1.0
-----

-   Elastica Library updated to 5.3.2  
    MK Search is compatible with all elasticsearch 5.x releases now.  
    Older Version are not supported anymore.
 
3.0.0
-----

-   Initial TYPO3 8.7 LTS Support 

2.0.25
------

-   bugfix if extensions depend on templavoila
-   bugfix if cal isn't installed
-   bugfix autoloading in T3 >= 6.2
-   bugfix cal indexer in T3 7.6
-   bugfix page indexer
-   support rnbase domain models
-   support reindexing deleted elements recursively
-   new config for solr 6.2
-   new feature grouped search in solr
-   new feature charbrowser for solr
-   new indexer for gridelements
-   new indexer for news
-   refactor several code and tests

2.0.8
-----

-   bugfix for core selection in solr admin panel

2.0.7
-----

-   bemodule refactored to rnbase module runner
-   be module registration for TYPO3 6 and above refactored

2.0.6
-----

-   support for workspaces included (IMPORTANT: reindexing of all data is neccessary if you have workspaces)

2.0.5
-----

-   updates manual
-   escape single quotes in the search term upon display in the FE for Lucence, Solr and ElasticSearch filters to avoid XSS

2.0.4
-----

-   converted documentation to markdown

2.0.3
-----

-   make some methods in solr response processor protected for inheritance

2.0.2
-----

-   solr response processer class can be extended from now on
-   raw value added to facet object

2.0.1
-----

-   fieldsConversion needs TSFE for cObj

2.0.0
-----

-   added support for TYPO3 7.6
-   removed CLI Crawler. Please use the Scheduler Task for indexing instead
-   cleanup and refactoring
-   added dfs field for irfaq categories
-   Handling of deleted files on FAL Indexing added
-   added missing exclude option in flexform for some fields
-   add new sorting feature for dfs fields
-   bugfix in case a FAL file has no storage
-   use title of FAL entites from metadata
-   fix solr ping checks to be typesafe
-   new addModelsToIndex method do add rnbase models and ArrayObjects to indexing queue
-   numbers are now accepted in fieldnames for filtering

1.5.10
------

-   [BUGFIX] passed model in tx\_mksearch\_indexer\_Base::indexEnableColumns is not changed anymore

1.5.9
-----

-   [!!!][BUGFIX] sys\_files with special characters like umlauts in their filename are now indexed correctly with the FAL indexer. You need the reindex all sys\_file records!

1.5.6
-----

-   [TASK] added FieldConversion for tt\_news and tt\_content indexer
-   [TASK] buildFacetData fallback removed tx\_mksearch\_util\_FacetBuilder
-   [TASK] Support for stdWrap in FieldConversion (issue \_\`\#6\`: <https://github.com/DMKEBUSINESSGMBH/typo3-mksearch/issues/6>)
-   [TASK] use TS parsinf of rn\_base to support file includes etc. in indexer configuration (\_\`\#7\`: <https://github.com/DMKEBUSINESSGMBH/typo3-mksearch/issues/7>)
-   [TASK] Support tags for fq-parameters (\_\`\#8\`: <https://github.com/DMKEBUSINESSGMBH/typo3-mksearch/issues/8>)
-   [TASK] rendering suggestions (\_\`\#9\`: <https://github.com/DMKEBUSINESSGMBH/typo3-mksearch/issues/9>)
-   [TASK] support for query facets added (\_\`\#10\`: <https://github.com/DMKEBUSINESSGMBH/typo3-mksearch/issues/10>)
-   [TASK] solr filter method parseFieldAndValue moved to filter util
-   [TASK] added documentation for the use of Tika to index PDFs etc.
-   some code cleanup and bugfixes

1.5.0
-----

-   [FEATURE] \#4 support for query facets
-   [FEATURE] Predefined form field for sorting search results in solr
-   [FEATURE] Indexer configuration: It is possible to index database fields (commaseparated) into more several document attributes
-   [FEATURE] Predefined form field to limit page size for solr
-   [FEATURE] Indexer configuration: It is possible to autoconvert unix timestamps to ISO dates with fieldsConversion.attributename.unix2isodate=1
-   check the manual, the example templates and static/static\_extension\_template/setup.txt for the new features

1.4.34
------

-   [TASK] typecast to int for preferer option, instead of bool cast

1.4.32
------

-   [TASK] optimize index on commit with service method from interface.
-   [TASK] new getPreparedIndexDocMockByRecord method for phpunit tests

1.4.31
------

-   [CLEANUP] abort message changed for ttnewss indexer

1.4.28
------

-   [BUGFIX] modelToIndex property now protected instead of private

1.4.25
------

-   [BUGFIX] solr search cache key fixed. (generateCacheKey walks recursiveley over field and options now)

1.4.24
------

-   [TASK] fallback to page title for emty content headers when indexing tt\_content
-   [TASK] content anchor for search entries to the url
-   [TASK] new dfs field for news categories of tt\_news
-   [TASK] pid in getPageContent has to be an integer and greather than 0.
-   [CLEANUP] title parsing outsourced to own method

1.4.23
------

-   [TASK] compatibility of mksearch with cal 1.9.0

