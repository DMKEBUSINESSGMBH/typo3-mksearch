<?php

//// DO NOT REMOVE OR CHANGE THESE 2 LINES:
//$MCONF['name'] = 'tools_txmksearchM1';
//$MCONF['script'] = '_DISPATCH';
    

// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/mksearch/mod1/');
$BACK_PATH='../../../../typo3/';
$MCONF['name'] = 'tools_txmksearchM1';

// script legt fest, welches Script für das Modul aufgerufen wird
// Nimmt man _DISPATCH wird die Datei typo3/mod.php verwendet und der Aufruf dann an das Modul 
// weitergeleitet. Problem: Die Basispfade stimmen dann nicht mehr.
$MCONF['script']='index.php';
//$MCONF['script'] = '_DISPATCH';


$MCONF['access'] = 'user,group';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mksearch/mod1/locallang_mod.xml';
