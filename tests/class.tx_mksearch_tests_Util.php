<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann
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

tx_rnbase::load('tx_rnbase_cache_Manager');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_rnbase_util_Spyc');

/**
 * Statische Hilfsmethoden für Tests
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 */
class tx_mksearch_tests_Util
{

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
    private static $TCA = null;


    /**
     * @var array
     */
    private static $extConf = array();

    /**
     * @var string
     */
    private static $addRootLineFieldsBackup = null;

    /**
     * Sichert die hoocks unt entfernt diese in der globalconf.
     *
     * @param array $hooks
     * @return void
     */
    public static function hooksSetUp($hooks = null)
    {
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
    public static function hooksTearDown()
    {
        foreach (self::$hooks as $hookName => $hookConfig) {
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
    public static function tcaSetUp(array $extensions = array())
    {
        self::$TCA = null;
        if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
            self::$TCA = $GLOBALS['TCA'];
            $GLOBALS['TCA'] = array();
            // \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(FALSE);
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(false);
            // spezielle Extension TCA's laden!?
            self::loadSingleExtTablesFiles($extensions);
        }
    }

    /**
     * Setzt die TCA zurück
     *
     * @return void
     */
    public static function tcaTearDown()
    {
        if (self::$TCA !== null) {
            $GLOBALS['TCA'] = self::$TCA;
            self::$TCA = null;
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
    public static function loadSingleExtTablesFiles(array $extensions)
    {
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
    public static function getFixturePath($filename, $dir = 'tests/fixtures/', $extKey = 'mksearch')
    {
        return tx_rnbase_util_Extensions::extPath($extKey).$dir.$filename;
    }

    /**
     * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
     * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
     * "lib.links." liegt
     * @return tx_rnbase_configurations
     */
    public static function loadPageTS4BE()
    {
        $extKeyTS = $extKey = 'mksearch';

        tx_rnbase_util_Extensions::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/static/static_extension_template/setup.txt">');

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
    public static function getPagesTSconfig($pageId = 0)
    {
        // ab TYPO3 6.2.x wird die TS config gecached wenn nicht direkt eine
        // rootline ungleich NULL übergeben wird.
        // wir müssen die rootline auf nicht NULL setzen und kein array damit
        // die rootline korrekt geholt wird und nichts aus dem Cache. Sonst werden
        // die gerade hinzugefügten TS Dateien nicht beachtet
        $rootLine = 1;
        tx_rnbase::load('Tx_Rnbase_Backend_Utility');

        return Tx_Rnbase_Backend_Utility::getPagesTSconfig($pageId, $rootLine);
    }

/**
 * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
 * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
 * "lib.links." liegt
 * @return tx_rnbase_configurations
 */
    public static function loadConfig4BE($pageTSconfig)
    {
        tx_rnbase::load('tx_rnbase_configurations');

        tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!
        $GLOBALS['TSFE']->config = array();
        $cObj = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass());

        $configurations = new tx_rnbase_configurations();
        $configurations->init((array) $pageTSconfig, $cObj, 'mksearch', 'mksearch');
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
        $extKeyOrIndexer,
        $cType = null,
        $documentClass = 'tx_mksearch_model_IndexerDocumentBase'
    ) {
        // extkey und contenttype vom indexer holen
        if ($extKeyOrIndexer instanceof tx_mksearch_interface_Indexer) {
            list($extKey, $cType) = $extKeyOrIndexer->getContentType();
        } // extkey und contenttype von den parametern nutzen
        elseif (is_string($extKeyOrIndexer)) {
            $extKey = $extKeyOrIndexer;
        } // falscher datentyp
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
     * @param   string  $extKey
     * @param   bool     $bDisable
     */
    public static function disableDevlog($extKey = 'devlog', $bDisable = true)
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['nolog'] = $bDisable;
    }

    /**
     * Setzt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren
     */
    public static function disableRelationManager()
    {
        if (!tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
            return;
        }
        tx_rnbase::load('tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\RelationHandler'] = array(
            'className' => 'tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler',
        );
    }
    /**
     * entfernt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren
     */
    public static function restoreRelationManager()
    {
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
     * @param string $extensionKey
     *
     * @return void
     */
    public static function unloadExtensionForTypo362OrHigher($extensionKey)
    {
        if (!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
            return;
        }

        // wir kommen an den Pfad zur Package Datei nur über Reflection
        $packageManager = tx_rnbase::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
        $packageStatesPathAndFilename = new ReflectionProperty('TYPO3\\CMS\\Core\\Package\\PackageManager', 'packageStatesPathAndFilename');
        $packageStatesPathAndFilename->setAccessible(true);

        // backup machen
        $packageStatesFile = $packageStatesPathAndFilename->getValue($packageManager);
        $backupPackageStatesFile = $packageStatesFile . '.bak';
        copy($packageStatesFile, $backupPackageStatesFile);

        $extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();

        $method = new ReflectionMethod('TYPO3\\CMS\\Core\\Package\\PackageManager', 'getDependencyArrayForPackage');
        $method->setAccessible(true);

        // falls eine extension von templavoila abhängt, müssen wir diese auch deinstallieren
        foreach ($packageManager->getActivePackages() as $package) {
            $packageKey = $package->getPackageMetaData()->getPackageKey();
            $dependencies = $method->invokeArgs($packageManager, array($packageKey));

            if (array_search($extensionKey, $dependencies) !== false) {
                $extensionManagementUtility->unloadExtension($packageKey);
            }
        }

        $extensionManagementUtility->unloadExtension($extensionKey);

        // bei autoloading werden die initialen packages durchsucht. Daher müssen
        // wir die aktualisierten packages dem class loader mitgeben
        $classLoaderProperty = new ReflectionProperty('TYPO3\\CMS\\Core\\Package\\PackageManager', 'classLoader');
        $classLoaderProperty->setAccessible(true);
        $classLoader = $classLoaderProperty->getValue($packageManager);
        $classLoader->setPackages($packageManager->getActivePackages());

        // backup zurück spielen
        copy($backupPackageStatesFile, $packageStatesFile);
        unlink($backupPackageStatesFile);
    }

    /**
     * Sichert eine Extension Konfiguration.
     * Wurde bereits eine Extension Konfiguration gesichert,
     * wird diese nur überschrieben wenn bOverwrite wahr ist!
     *
     * @param string    $extKey
     * @param bool   $overwrite
     */
    public static function storeExtConf($extKey = 'mksearch', $overwrite = false)
    {
        if (!isset(self::$extConf[$extKey]) || $overwrite) {
            self::$extConf[$extKey] = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey];
        }
    }
    /**
     * Setzt eine gesicherte Extension Konfiguration zurück.
     *
     * @param string $extKey
     * @return bool      wurde die Konfiguration zurückgesetzt?
     */
    public static function restoreExtConf($extKey = 'mksearch')
    {
        if (isset(self::$extConf[$extKey])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey] = self::$extConf[$extKey];

            return true;
        }

        return false;
    }

    /**
     * Setzt eine Vaiable in die Extension Konfiguration.
     * Achtung im setUp sollte storeExtConf und im tearDown restoreExtConf aufgerufen werden.
     * @param string    $cfgKey
     * @param string    $cfgValue
     * @param string    $extKey
     */
    public static function setExtConfVar($cfgKey, $cfgValue, $extKey = 'mksearch')
    {
        // aktuelle Konfiguration auslesen
        $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);
        // wenn keine Konfiguration existiert, legen wir eine an.
        if (!is_array($extConfig)) {
            $extConfig = array();
        }
        // neuen Wert setzen
        $extConfig[$cfgKey] = $cfgValue;
        // neue Konfiguration zurückschreiben
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey] = serialize($extConfig);
    }

    /**
     * wenn in addRootLineFields Felder stehen, die von anderen Extensions bereitgestellt werden,
     * aber nicht importiert wurden, führt das zu Testfehlern. Also machen wir die einfach leer.
     * sollte nicht stören.
     *
     * @return void
     */
    public static function emptyAddRootlineFields()
    {
        self::$addRootLineFieldsBackup = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
        if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
            $property = new ReflectionProperty('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', 'rootlineFields');
            $property->setAccessible(true);
            $rootLineFields = Tx_Rnbase_Utility_Strings::trimExplode(',', self::$addRootLineFieldsBackup, true);
            $property->setValue(null, array_diff($property->getValue(null), $rootLineFields));
        }
    }

    /**
     * @return void
     */
    public static function resetAddRootlineFields()
    {
        if (self::$addRootLineFieldsBackup != null) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = self::$addRootLineFieldsBackup;
            if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
                $property = new ReflectionProperty('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', 'rootlineFields');
                $property->setAccessible(true);
                $rootLineFields = Tx_Rnbase_Utility_Strings::trimExplode(',', self::$addRootLineFieldsBackup, true);
                $property->setValue(null, array_unique(array_merge($property->getValue(null), $rootLineFields)));
            }
            self::$addRootLineFieldsBackup = null;
        }
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']);
}
