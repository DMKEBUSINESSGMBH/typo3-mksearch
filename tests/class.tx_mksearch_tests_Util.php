<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_tests
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_cache_Manager');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_rnbase_util_Spyc');

/**
 * Statische Hilfsmethoden für Tests
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 */
class tx_mksearch_tests_Util {

	/**
	 * Sicherung von hoocks
	 *
	 * @var array
	 */
	private static $hooks = array();

	/**
	 * Sicherung der TCA
	 *
	 * @var array
	 */
	private static $TCA = NULL;

	/**
	 * Sichert die hoocks unt entfernt diese in der globalconf.
	 *
	 * @param array $hooks
	 * @return void
	 */
	public static function hooksSetUp($hooks = NULL) {
		if (!is_array($hooks)) {
			$hooks = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']);
		}
		foreach ($hooks as $hook) {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook])) {
				self::$hooks[$hook] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook];
			}
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook] = array();
		}
	}
	/**
	 * Setzt die im setUp gesetzten Hooks zurück
	 *
	 * @return void
	 */
	public static function hooksTearDown() {
		foreach(self::$hooks as $hookName => $hookConfig) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookName] = $hookConfig;
		}
		self::$hooks = array();
	}

	/**
	 * Setzt die TCA zurück.
	 *
	 * Durch den Typo3 Relation Manager werden alle Relationen aufgelöst,
	 * welche in der TCA Definiert sind.
	 * So kann es vorkommen, das auf Tabellen zugegriffen wird,
	 * welche in einem DB-TestCase nicht existieren.
	 *
	 * @param array $extensions
	 * @return void
	 */
	public static function tcaSetUp(array $extensions = array()) {
		self::$TCA = NULL;
		if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
			self::$TCA = $GLOBALS['TCA'];
			$GLOBALS['TCA'] = array();
			// \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(FALSE);
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(FALSE);
			// spezielle Extension TCA's laden!?
			tx_mksearch_tests_Util::loadSingleExtTablesFiles($extensions);
		}
	}

	/**
	 * Setzt die TCA zurück
	 *
	 * @return void
	 */
	public static function tcaTearDown() {
		if (self::$TCA !== NULL) {
			$GLOBALS['TCA'] = self::$TCA;
			self::$TCA = NULL;
		}
	}

	/**
	 * Load ext_tables.php as single files
	 *
	 * This Method is taken from
	 *     \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadSingleExtTablesFiles
	 *
	 * @param array $extensions
	 * @return void
	 */
	public static function loadSingleExtTablesFiles(array $extensions) {
		// In general it is recommended to not rely on it to be globally defined in that
		// scope, but we can not prohibit this without breaking backwards compatibility
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;
		global $_EXTKEY;
		// Load each ext_tables.php file of loaded extensions
		foreach ($extensions as $_EXTKEY) {
			if (empty($GLOBALS['TYPO3_LOADED_EXT'][$_EXTKEY])) {
				continue;
			}
			$extensionInformation = $GLOBALS['TYPO3_LOADED_EXT'][$_EXTKEY];
			if (is_array($extensionInformation) && $extensionInformation['ext_tables.php']) {
				// $_EXTKEY and $_EXTCONF are available in ext_tables.php
				// and are explicitly set in cached file as well
				$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
				require $extensionInformation['ext_tables.php'];
				// loads the dynamicConfigFile
				// @TODO: implement, if needet!
				// static::loadNewTcaColumnsConfigFiles();
			}
		}
	}

	/**
	 * Liefert einen kompletten Dateipfad für eine Datei in einer Extension
	 * @param $filename
	 * @param $dir
	 * @param $extKey
	 * @return string
	 */
	public static function getFixturePath($filename, $dir = 'tests/fixtures/', $extKey = 'mksearch') {
		return t3lib_extMgm::extPath($extKey).$dir.$filename;
	}

	/**
   	 * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
   	 * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
   	 * "lib.links." liegt
   	 * @return tx_rnbase_configurations
   	 */
  	public static function loadPageTS4BE() {
  		$extKeyTS = $extKey = 'mksearch';

	    t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/static/static_extension_template/setup.txt">');

	    $pageTSconfig = self::getPagesTSconfig(0);
	    $tempConfig = $pageTSconfig['plugin.']['tx_'.$extKeyTS.'.'];
	    $tempConfig['lib.'][$extKeyTS.'.'] = $pageTSconfig['lib.'][$extKeyTS.'.'];
	    $tempConfig['lib.']['links.'] = $pageTSconfig['lib.']['links.'];
	    $pageTSconfig = $tempConfig;

	    $qualifier = $pageTSconfig['qualifier'] ? $pageTSconfig['qualifier'] : $extKeyTS;

	  	return $pageTSconfig;
  	}

  	/**
  	 * wrapper funktion
  	 *
  	 * @param number $pageId
  	 *
  	 * @return array
  	 */
  	public static function getPagesTSconfig($pageId = 0) {
  		// ab TYPO3 6.2.x wird die TS config gecached wenn nicht direkt eine
  		// rootline ungleich NULL übergeben wird.
  		// wir müssen die rootline auf nicht NULL setzen und kein array damit
  		// die rootline korrekt geholt wird und nichts aus dem Cache. Sonst werden
  		// die gerade hinzugefügten TS Dateien nicht beachtet
  		$rootLine = 1;
  		return t3lib_BEfunc::getPagesTSconfig($pageId, $rootLine);
  	}

/**
   	 * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
   	 * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
   	 * "lib.links." liegt
   	 * @return tx_rnbase_configurations
   	 */
  	public static function loadConfig4BE($pageTSconfig) {
	    tx_rnbase::load('tx_rnbase_configurations');
	    tx_rnbase::load('tx_rnbase_util_Misc');

	    tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!
	    $GLOBALS['TSFE']->config = array();
	    $cObj = t3lib_div::makeInstance('tslib_cObj');

	    $configurations = new tx_rnbase_configurations();
	    $configurations->init($pageTSconfig, $cObj, 'mksearch', 'mksearch');
	    $configurations->setParameters(tx_rnbase::makeInstance('tx_rnbase_parameters'));

	  	return $configurations;
	}

	/**
	 * Erzeugt ein neues Indexer Dokument.
	 *
	 * @param string $extKeyOrIndexer
	 * @param string $cType
	 * @param string $documentClass
	 * @return tx_mksearch_model_IndexerDocumentBase
	 */
	public static function getIndexerDocument(
		$extKeyOrIndexer, $cType = NULL,
		$documentClass = 'tx_mksearch_model_IndexerDocumentBase'
	) {
		// extkey und contenttype vom indexer holen
		if ($extKeyOrIndexer instanceof tx_mksearch_interface_Indexer) {
			list($extKey, $cType) = $extKeyOrIndexer->getContentType();
		}
		// extkey und contenttype von den parametern nutzen
		elseif (is_string($extKeyOrIndexer)) {
			$extKey = $extKeyOrIndexer;
		}
		// falscher datentyp
		else {
			throw new Exception(
				'First argument of getIndexerDocument has to be an "string"'
				. ' or instance of "tx_mksearch_interface_Indexer", '
				. (is_object($extKeyOrIndexer) ? get_class($extKeyOrIndexer) : gettype($extKeyOrIndexer))
				.' given.'
			);
		}
		return tx_rnbase::makeInstance($documentClass, $extKey, $cType);
	}

	/**
	 * Disabled das Logging über die Devlog Extension für die
	 * gegebene Extension
	 *
	 * @param 	string 	$extKey
	 * @param 	boolean 	$bDisable
	 */
	public static function disableDevlog($extKey = 'devlog', $bDisable = true) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['nolog'] = $bDisable;
	}

	/**
	 * Setzt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren
	 */
	public static function disableRelationManager() {
		if (!tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
			return ;
		}
		tx_rnbase::load('tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler');
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\RelationHandler'] = array(
			'className' => 'tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler',
		);
	}
	/**
	 * entfernt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren
	 */
	public static function restoreRelationManager() {
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\RelationHandler']);
	}

	/**
	 * wir wollen zwar templavoila deaktivieren, wir wollen aber nicht
	 * das die PackageStates Datei angepasst wird, was TYPO3 aber zwangsläufig
	 * macht. Das hat zur Folge das requests an die Seite während der Tests
	 * eine Exception verursachen da templavoila nicht geladen ist.
	 *
	 * Also kopieren wir die PackageStates Datei damit wir die tatsächliche Datei
	 * nach dem deaktiveren wieder einfügen können
	 *
	 * @return void
	 */
	public static function unloadTemplavoilaForTypo362OrHigher() {
		if (!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			return;
		}

		// wir kommen an den Pfad zur Package Datei nur über Reflection
		$packageManager = tx_rnbase::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
		$packageStatesPathAndFilename = new ReflectionProperty('TYPO3\\CMS\\Core\\Package\\PackageManager', 'packageStatesPathAndFilename');
		$packageStatesPathAndFilename->setAccessible(TRUE);

		// backup machen
		$packageStatesFile = $packageStatesPathAndFilename->getValue($packageManager);
		$backupPackageStatesFile = $packageStatesFile . '.bak';
		copy($packageStatesFile, $backupPackageStatesFile);

		$extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();
		$extensionManagementUtility->unloadExtension('templavoila');


		// backup zurück spielen
		copy($backupPackageStatesFile, $packageStatesFile);
		unlink($backupPackageStatesFile);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']);
}