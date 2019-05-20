<?php
/*
 * Register necessary class names with autoloader
 *
 */
// wir können weder den Extensionmanager nutzen (auf Grund der verschiedenen TYPO3 Versionen)
// noch tx_rnbase_util_Extensions (weil noch nicht geladen). Also nehmen wir den Pfad hart. Dürfte
// aber kein Problem sein.
$extensionPath = PATH_typo3conf.'ext/mksearch/';

return array(
    'tx_mksearch_scheduler_indextask' => $extensionPath.'scheduler/class.tx_mksearch_scheduler_IndexTask.php',
    'tx_mksearch_scheduler_indextaskaddfieldprovider' => $extensionPath.'scheduler/class.tx_mksearch_scheduler_IndexTaskAddFieldProvider.php',
);
