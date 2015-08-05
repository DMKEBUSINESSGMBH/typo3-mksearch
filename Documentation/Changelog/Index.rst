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
