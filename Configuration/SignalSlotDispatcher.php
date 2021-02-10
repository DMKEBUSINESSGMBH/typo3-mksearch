<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

$signalSlotDispatcher = tx_rnbase::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
    'recordCreated',
    'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
    'putFileIntoQueue',
    false
);

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
    'recordUpdated',
    'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
    'putFileIntoQueue',
    false
);

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
    'recordDeleted',
    'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
    'putFileIntoQueue',
    false
);

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
    'recordMarkedAsMissing',
    'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
    'putFileIntoQueue',
    false
);
