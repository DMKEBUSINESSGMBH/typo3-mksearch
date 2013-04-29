<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
require(t3lib_extMgm::extPath($_EXTKEY).'tca/hooks/tt_content.php');
