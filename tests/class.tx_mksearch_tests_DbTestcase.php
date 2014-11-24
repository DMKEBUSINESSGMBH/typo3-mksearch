<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH
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
***************************************************************/

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_rnbase_util_TYPO3');

/**
 * Base Testcase for DB Tests
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
abstract class tx_mksearch_tests_DbTestcase
	extends tx_phpunit_database_testcase {

	protected $workspaceBackup;
	protected $templaVoilaConfigBackup = NULL;
	protected $db;

	/**
	 * @var boolean
	 */
	protected $unloadTemplavoila = true;

	/**
	 * @var array
	 */
	protected $addRootLineFieldsBackup = '';

	/**
	 * Liste der extensions, welche in die test DB importiert werden müssen.
	 *
	 * @var array
	 */
	protected $importExtensions = array('cms', 'mksearch');

	/**
	 * Liste der daten, welche in die test DB importiert werden müssen.
	 *
	 * @var array
	 */
	protected $importDataSets = array();

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name the name of a testcase
	 * @param array $data ?
	 * @param string $dataName ?
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			$this->importExtensions[] = 'core';
			$this->importExtensions[] = 'frontend';
		}

		// templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		// und man die Testergebnisse nicht sieht
		if (t3lib_extMgm::isLoaded('realurl')) {
			$this->importExtensions[] = 'realurl';
		}
		if (t3lib_extMgm::isLoaded('templavoila')) {
			$this->importExtensions[] = 'templavoila';
		}
		// fügt felder bei datenbank abfragen hinzu in $TYPO3_CONF_VARS['FE']['pageOverlayFields']
		// und $TYPO3_CONF_VARS['FE']['addRootLineFields']
		if (t3lib_extMgm::isLoaded('tq_seo')) {
			$this->importExtensions[] = 'tq_seo';
		}
	}

	/**
	 * setUp() = init DB etc.
	 */
	protected function setUp() {
		// wenn in addRootLineFields Felder stehen, die von anderen Extensions bereitgestellt werden,
		// aber nicht importiert wurden, führt das zu Testfehlern. Also machen wir die einfach leer.
		// sollte nicht stören.
		$this->addRootLineFieldsBackup = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
		$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';

		// set up the TCA
		tx_mksearch_tests_Util::tcaSetUp($this->importExtensions);

		// set up hooks
		tx_mksearch_tests_Util::hooksSetUp();

		// wir deaktivieren den relation manager
		tx_mksearch_tests_Util::disableRelationManager();

		// set up the workspace
		$this->workspaceBackup = $GLOBALS['BE_USER']->workspace;
		$GLOBALS['BE_USER']->setWorkspace(0);

		// WORKAROUND: phpunit seems to backup static attributes (in phpunit.xml)
		// from version 3.6.10 not before. I'm not completely
		// sure about that but from version 3.6.10 clearPageInstance is no
		// more neccessary to have the complete test suite succeed.
		// But this version is buggy. (http://forge.typo3.org/issues/36232)
		// as soon as this bug is fixed, we can use the new phpunit version
		// and dont need this anymore
		tx_mksearch_service_indexer_core_Config::clearPageInstance();

		// set up database
		$GLOBALS['TYPO3_DB']->debugOutput = TRUE;
		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();

		$this->importStdDB();
		$this->importExtensions($this->importExtensions);

		foreach ($this->importDataSets as $importDataSet) {
			$this->importDataSet($importDataSet);
		}

		// das devlog stört nur bei der Testausführung im BE und ist da auch
		// vollkommen unnötig
		tx_mksearch_tests_Util::disableDevlog();

		// set up tv
		if (t3lib_extMgm::isLoaded('templavoila') && $this->unloadTemplavoila) {
			$this->templaVoilaConfigBackup = $GLOBALS['TYPO3_LOADED_EXT']['templavoila'];
			$GLOBALS['TYPO3_LOADED_EXT']['templavoila'] = NULL;

			if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
				$extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();
				$extensionManagementUtility->unloadExtension('templavoila');
			}
		}

		$this->purgeRootlineCaches();
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	protected function tearDown() {
		// tear down TCA
		tx_mksearch_tests_Util::tcaTearDown();

		// tear down hooks
		tx_mksearch_tests_Util::hooksTearDown();

		// wir aktivieren den relation manager wieder
		tx_mksearch_tests_Util::restoreRelationManager();

		// tear down DB
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		// tear down Workspace
		$GLOBALS['BE_USER']->setWorkspace($this->workspaceBackup);

		// tear down tv
		if ($this->templaVoilaConfigBackup !== NULL) {
			$GLOBALS['TYPO3_LOADED_EXT']['templavoila'] = $this->templaVoilaConfigBackup;
			$this->templaVoilaConfigBackup = NULL;

			if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
				$extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();
				$extensionManagementUtility->loadExtension('templavoila');
			}
		}

		$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = $this->addRootLineFieldsBackup;

		$this->purgeRootlineCaches();
	}

	/**
	 * @return void
	 */
	protected function purgeRootlineCaches() {
		if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			\TYPO3\CMS\Core\Utility\RootlineUtility::purgeCaches();
		}
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_DbTestcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_DbTestcase.php']);
}
