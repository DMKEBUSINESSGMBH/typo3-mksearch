<?php

// ////////////////////////////////////////////////////////// //
// deprecated, will be removed, when TYPO3 4.x support drops! //
// ////////////////////////////////////////////////////////// //

$MCONF['name'] = 'web_txmksearchM1';
$MCONF['script']='_DISPATCH';

$MCONF['access'] = 'user,group';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mksearch/mod1/locallang_mod.xml';

define('ICON_OK', -1);
define('ICON_INFO', 1);
define('ICON_WARN', 2);
define('ICON_FATAL', 3);
