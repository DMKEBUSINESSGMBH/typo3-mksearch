<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}


tx_rnbase::load('tx_mksearch_signalSlotDispatcher_FileIndexRepsitory');
$signalSlotDispatcher = tx_rnbase::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
	'recordCreated',
	'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
	'putFileIntoQueue',
	FALSE
);

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
	'recordUpdated',
	'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
	'putFileIntoQueue',
	FALSE
);

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository',
	'recordDeleted',
	'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
	'putFileIntoQueue',
	FALSE
);