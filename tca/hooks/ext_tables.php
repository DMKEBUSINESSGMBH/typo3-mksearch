<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
require tx_rnbase_util_Extensions::extPath($_EXTKEY).'tca/hooks/tt_content.php';
