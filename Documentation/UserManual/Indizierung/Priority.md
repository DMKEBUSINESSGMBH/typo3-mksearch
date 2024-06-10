Priorität der Indizierung beeinflussen
==========================

Manchmal ist es hilfreich die Priorität bei der Indizierung beeinflussen zu können, damit
manche Tabellen grundlegend bevorzugt behandelt und damit schneller indiziert werden. 
Das kann wie folgt beeinflusst werden:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexingPriority']['tx_news_domain_model_news'] = 50;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexingPriority']['my_custom_table'] = 100;
```
