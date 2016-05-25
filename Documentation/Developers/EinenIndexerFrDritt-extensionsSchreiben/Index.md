Einen Indexer für Dritt-Extensions schreiben
============================================

In mksearch können nur Daten indiziert werden, für die ein passender Indexer bereitsteht. Für viele populäre TYPO3 Extensions liefert mksearch bereits passende Indexer mit. Es ist aber auch mit wenigen PHP-Kenntnissen möglich einen eigenen Indexer zu schreiben. Die Aufgabe des Indexer besteht darin, suchbare Daten aus der Datenbank oder einer anderen Datenquelle zu sammeln und für die Suchmaschine bereitzustellen. Folgende Schritte müssen für einen eigenen Indexer ausgeführt werden:

1.  Anlegen einer eigenen Extensions (falls noch nicht vorhanden)
2.  Schreiben der Indexerklasse
3.  Registrierung des Indexers

Die Indexer-Klassen von mksearch finden Sie im Verzeichnis EXT:mksearch/indexer. Beim Anlegen der Klassen müssen die Konventionen für das Autoloading beachtet werden! Schauen Sie sich am besten die vorhandenen Indexer an und nutzen Sie diese als Vorlage für ihren eigenen Indexer.

Mit folgender Anweisung in der ext\_localconf.php kann ein Indexer registriert werden:

    if (tx_rnbase_util_Extensions::isLoaded('irfaq')) {
            tx_mksearch_util_Config::registerIndexer(
              'irfaq',
              'question',
              'tx_mksearch_indexer_Irfaq',
                   array(
                      //main table
                      'tx_irfaq_q',
                      //tables with related data
                      'tx_irfaq_expert',
                      'tx_irfaq_cat',
                    )
            );
    }

In diesem Beispiel kann man sehr schön sehen, daß sich ein Indexer für mehrere Datenbank-Tabellen registrieren kann. Dies ist notwendig, wenn man die Frage betrachtet, was eigentlich indiziert werden sollte. Suchmaschinen indizieren Dokumente. Ein Dokument ist dabei ein Datensatz, der sich aus mehreren relationalen Datensätzen zusammensetzt. Redundanz ist hier ausdrücklich erwünscht! Der Indexer von irfaq indiziert in jedem Dokument die Frage/Antwort, die Kategorie und den Experten mit allen Informationen. Somit muss sich der Indexer auch für alle drei Tabellen registrieren, damit er den Index immer auf dem aktuellen Stand halten kann.

Dabei wird der Indexer aber nicht bei jeder Änderung sofort neue Daten indizieren. Wenn bspw. eine Änderung des Kategorienamens erfolgt, dann wird der Indexer darüber informiert. Er sollte dann alle betroffenen FAQ-Fragen dieser Kategorie ermitteln und in die Warteschlange legen. Dieser Vorgang kann sehr schnell ausgeführt werden und blockiert dadurch die Warteschlange nicht.
