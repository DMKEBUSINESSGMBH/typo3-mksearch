#Elastica


MKSearch bringt die Benötigten Bibliotheken mit, um ElasticSearch in Version 1 anbinden zu können. 
Wenn eine aktuellere Version verwendet wird, muss diese Bibliothek aktualisiert werden.

Als Client wird [Elastica](http://elastica.io/) verwendet. Diese Bibliothek steht in verschiedenen Versionen zur Verfügung
und muss je nach verwendeter ElasticSearch Version bereit gestellt werden.

Um eine spezielle Version von Elastica zu verwenden, 
muss zunächst die interne über die Extensionkonfiguration `useInternalElasticaLib` deaktiviert werden.

Anschließend gibt es verschiedene Wege eine bestimmte Version zu nutzen.

## Composer

Am einfachsten geht es mit Composer.
Hierzu im Webroot einfach die Abhängigkeiten setzen. 
Für ElasticSearch 5.4  würde dies so aussehen:

```bash
    composer require "ruflin/elastica:~5.3"
```

## Extension

Steht kein Composer zur Verfügung, 
kann die Bibliothek über eine eigene Extension bereit gestellt werden.

Dazu einfach die Sourcen von [Elastica](http://elastica.io/) herunter laden,
in der Extension beispielsweise unter `Resources/Private/PHP/Elastica/` ablegen
und in der ext_localconf.php die Classmap für das Autoloading ergänzen:

```php
    ...
    'autoload' => array(
        'classmap' => array(
            'Resources/Private/PHP/Elastica/'
        )
    )
    ...
```
