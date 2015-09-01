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

1.5.0
-----
* [FEATURE] #4 support for query facets
* [FEATURE] Predefined form field for sorting search results in solr
* [FEATURE] Indexer configuration: It is possible to index database fields (commaseparated) into more several document attributes
* [FEATURE] Predefined form field to limit page size for solr
* [FEATURE] Indexer configuration: It is possible to autoconvert unix timestamps to ISO dates with fieldsConversion.attributename.unix2isodate=1
* check the manual, the example templates and static/static_extension_template/setup.txt for the new features

1.4.34
------
* [TASK] typecast to int for preferer option, instead of bool cast

1.4.32
------
* [TASK] optimize index on commit with service method from interface.
* [TASK] new getPreparedIndexDocMockByRecord method for phpunit tests

1.4.31
------
* [CLEANUP] abort message changed for ttnewss indexer

1.4.28
------
* [BUGFIX] modelToIndex property now protected instead of private

1.4.25
------
* [BUGFIX] solr search cache key fixed. (generateCacheKey walks recursiveley over field and options now)

1.4.24
------
* [TASK] fallback to page title for emty content headers when indexing tt_content
* [TASK] content anchor for search entries to the url
* [TASK] new dfs field for news categories of tt_news
* [TASK] pid in getPageContent has to be an integer and greather than 0.
* [CLEANUP] title parsing outsourced to own method

1.4.23
------

* [TASK] compatibility of mksearch with cal 1.9.0
