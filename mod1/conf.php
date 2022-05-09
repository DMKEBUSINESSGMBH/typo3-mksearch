<?php

// ////////////////////////////////////////////////////////// //
// deprecated, will be removed, when TYPO3 4.x support drops! //
// ////////////////////////////////////////////////////////// //

$MCONF['name'] = 'web_MksearchM1';
$MCONF['script'] = '_DISPATCH';

$MCONF['access'] = 'user,group';

$MLANG['default']['tabs_images']['tab'] = 'Resources/Public/Icons/moduleicon.png';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang_mod.xlf';

define('ICON_OK', -1);
define('ICON_INFO', 1);
define('ICON_WARN', 2);
define('ICON_FATAL', 3);
