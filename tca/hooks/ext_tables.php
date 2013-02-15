<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
require_once(t3lib_extMgm::extPath($_EXTKEY).'tca/hooks/tt_content.php');
