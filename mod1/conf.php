<?php

// DO NOT REMOVE OR CHANGE THESE 2 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/mksearch/mod1/');
$BACK_PATH='../../../../typo3/';
$MCONF['name'] = 'web_txmksearchM1';
$MCONF['script']='index.php';

$MCONF['access'] = 'user,group';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mksearch/mod1/locallang_mod.xml';

define('ICON_OK', -1);
define('ICON_INFO', 1);
define('ICON_WARN', 2);
define('ICON_FATAL', 3);