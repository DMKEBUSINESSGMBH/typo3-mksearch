<?php

/**
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
 * benötigte Klassen einbinden.
 */

/**
 * Statische Hilfsmethoden für Tests.
 */
class tx_mksearch_tests_Util
{
    /**
     * Sicherung von hoocks.
     *
     * @var array
     */
    private static $hooks = [];

    /**
     * Sicherung der TCA.
     *
     * @var array
     */
    private static $TCA;

    /**
     * @var array
     */
    private static $extConf = [];

    /**
     * @var string
     */
    private static $addRootLineFieldsBackup;

    /**
     * @var array
     */
    private static $categoryRegistry;

    /**
     * Sichert die hoocks unt entfernt diese in der globalconf.
     *
     * @param array $hooks
     */
    public static function hooksSetUp($hooks = null)
    {
        if (!is_array($hooks)) {
            $hooks = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'] ?? []);
        }
        foreach ($hooks as $hook) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook])) {
                self::$hooks[$hook] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook];
            }
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hook] = [];
        }
    }

    /**
     * Setzt die im setUp gesetzten Hooks zurück.
     */
    public static function hooksTearDown()
    {
        foreach (self::$hooks as $hookName => $hookConfig) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookName] = $hookConfig;
        }
        self::$hooks = [];
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
     */
    public static function tcaSetUp(array $extensions = [])
    {
        self::$TCA = $GLOBALS['TCA'];
        $GLOBALS['TCA'] = [];
        // otherwise we may get warnings like ...no category registered for table..Key was already registered
        $categoryRegistryClass = 'TYPO3\\CMS\\Core\\Category\\CategoryRegistry';
        $registry = new ReflectionProperty($categoryRegistryClass, 'registry');
        $registry->setAccessible(true);
        $categoryRegistry = $categoryRegistryClass::getInstance();
        self::$categoryRegistry = $registry->getValue($categoryRegistry);
        $registry->setValue($categoryRegistry, []);

        // \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(FALSE);
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(false);
        // spezielle Extension TCA's laden!?
        self::loadSingleExtTablesFiles($extensions);
    }

    /**
     * Setzt die TCA zurück.
     */
    public static function tcaTearDown()
    {
        if (null !== self::$TCA) {
            $GLOBALS['TCA'] = self::$TCA;
            self::$TCA = null;

            $categoryRegistryClass = 'TYPO3\\CMS\\Core\\Category\\CategoryRegistry';
            $registry = new ReflectionProperty($categoryRegistryClass, 'registry');
            $registry->setAccessible(true);
            $categoryRegistry = $categoryRegistryClass::getInstance();
            $registry->setValue($categoryRegistry, self::$categoryRegistry);
            self::$categoryRegistry = null;
        }
    }

    /**
     * Load ext_tables.php as single files.
     *
     * This Method is taken from
     *     \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadSingleExtTablesFiles
     *
     * @param array $extensions
     */
    public static function loadSingleExtTablesFiles(array $extensions)
    {
        // Load each ext_tables.php file of loaded extensions
        $packageManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        foreach ($extensions as $extensionKey) {
            if (!$packageManager->isPackageActive($extensionKey)) {
                continue;
            }
            $extTablesFile = $packageManager->getPackage($extensionKey)->getPackagePath().'ext_tables.php';
            if (is_file($extTablesFile)) {
                require $extTablesFile;
            }
        }
    }

    /**
     * Liefert einen kompletten Dateipfad für eine Datei in einer Extension.
     *
     * @param $filename
     * @param $dir
     * @param $extKey
     *
     * @return string
     */
    public static function getFixturePath($filename, $dir = 'tests/fixtures/', $extKey = 'mksearch')
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey).$dir.$filename;
    }

    /**
     * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
     * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
     * "lib.links." liegt.
     *
     * @return \Sys25\RnBase\Configuration\Processor
     */
    public static function loadPageTS4BE()
    {
        $extKeyTS = $extKey = 'mksearch';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/static/static_extension_template/setup.txt">');

        $pageTSconfig = self::getPagesTSconfig(0);
        $tempConfig = $pageTSconfig['plugin.']['tx_'.$extKeyTS.'.'];
        $tempConfig['lib.'][$extKeyTS.'.'] = $pageTSconfig['lib.'][$extKeyTS.'.'];
        $tempConfig['lib.']['links.'] = $pageTSconfig['lib.']['links.'];
        $pageTSconfig = $tempConfig;

        $qualifier = $pageTSconfig['qualifier'] ? $pageTSconfig['qualifier'] : $extKeyTS;

        return $pageTSconfig;
    }

    /**
     * wrapper funktion.
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

        return \Sys25\RnBase\Backend\Utility\BackendUtility::getPagesTSconfig($pageId, $rootLine);
    }

    /**
     * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
     * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
     * "lib.links." liegt.
     *
     * @return \Sys25\RnBase\Configuration\Processor
     */
    public static function loadConfig4BE($pageTSconfig)
    {
        \Sys25\RnBase\Utility\Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!
        $GLOBALS['TSFE']->config = [];
        $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

        $configurations = new \Sys25\RnBase\Configuration\Processor();
        $pageTSconfig = (array) $pageTSconfig;
        $configurations->init($pageTSconfig, $cObj, 'mksearch', 'mksearch');
        $configurations->setParameters(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Request\Parameters::class));

        return $configurations;
    }

    /**
     * Erzeugt ein neues Indexer Dokument.
     *
     * @param string $extKeyOrIndexer
     * @param string $cType
     * @param string $documentClass
     *
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
            throw new Exception('First argument of getIndexerDocument has to be an "string" or instance of "tx_mksearch_interface_Indexer", '.(is_object($extKeyOrIndexer) ? get_class($extKeyOrIndexer) : gettype($extKeyOrIndexer)).' given.');
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($documentClass, $extKey, $cType);
    }

    /**
     * Setzt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren.
     */
    public static function disableRelationManager()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\RelationHandler'] = [
            'className' => 'tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler',
        ];
    }

    /**
     * entfernt eine XCLASS, um den Relationmanager von Typo3 > 6 zu deaktivieren.
     */
    public static function restoreRelationManager()
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\RelationHandler']);
    }

    /**
     * wir wollen zwar gridelements deaktivieren, wir wollen aber nicht
     * das die PackageStates Datei angepasst wird, was TYPO3 aber zwangsläufig
     * macht. Das hat zur Folge das requests an die Seite während der Tests
     * eine Exception verursachen da gridelements nicht geladen ist.
     *
     * Also kopieren wir die PackageStates Datei damit wir die tatsächliche Datei
     * nach dem deaktiveren wieder einfügen können
     *
     * @param string $extensionKey
     */
    public static function unloadExtensionForTypo362OrHigher($extensionKey)
    {
        // wir kommen an den Pfad zur Package Datei nur über Reflection
        $packageManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        $packageStatesPathAndFilename = new ReflectionProperty('TYPO3\\CMS\\Core\\Package\\PackageManager', 'packageStatesPathAndFilename');
        $packageStatesPathAndFilename->setAccessible(true);

        // backup machen
        $packageStatesFile = $packageStatesPathAndFilename->getValue($packageManager);
        $backupPackageStatesFile = $packageStatesFile.'.bak';
        copy($packageStatesFile, $backupPackageStatesFile);

        $extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();

        $method = new ReflectionMethod(\TYPO3\CMS\Core\Package\PackageManager::class, 'getDependencyArrayForPackage');
        $method->setAccessible(true);

        // falls eine extension von gridelements abhängt, müssen wir diese auch deinstallieren
        foreach ($packageManager->getActivePackages() as $package) {
            $packageKey = $package->getPackageMetaData()->getPackageKey();
            $dependencies = $method->invokeArgs($packageManager, [$packageKey]);

            if (false !== array_search($extensionKey, $dependencies)) {
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
     * @param string $extKey
     * @param bool   $overwrite
     */
    public static function storeExtConf($extKey = 'mksearch', $overwrite = false)
    {
        if (!isset(self::$extConf[$extKey]) || $overwrite) {
            self::$extConf[$extKey] = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extKey] ?? [];
        }
    }

    /**
     * Setzt eine gesicherte Extension Konfiguration zurück.
     *
     * @param string $extKey
     *
     * @return bool wurde die Konfiguration zurückgesetzt?
     */
    public static function restoreExtConf($extKey = 'mksearch')
    {
        if (isset(self::$extConf[$extKey])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extKey] = self::$extConf[$extKey];

            return true;
        }

        return false;
    }

    /**
     * Setzt eine Vaiable in die Extension Konfiguration.
     * Achtung im setUp sollte storeExtConf und im tearDown restoreExtConf aufgerufen werden.
     *
     * @param string $cfgKey
     * @param string $cfgValue
     * @param string $extKey
     */
    public static function setExtConfVar($cfgKey, $cfgValue, $extKey = 'mksearch')
    {
        // aktuelle Konfiguration auslesen
        $extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extKey] ?? [];
        // wenn keine Konfiguration existiert, legen wir eine an.
        if (!is_array($extConfig)) {
            $extConfig = [];
        }
        // neuen Wert setzen
        $extConfig[$cfgKey] = $cfgValue;
        // neue Konfiguration zurückschreiben
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extKey] = $extConfig;
    }

    /**
     * wenn in addRootLineFields Felder stehen, die von anderen Extensions bereitgestellt werden,
     * aber nicht importiert wurden, führt das zu Testfehlern. Also machen wir die einfach leer.
     * sollte nicht stören.
     */
    public static function emptyAddRootlineFields()
    {
        self::$addRootLineFieldsBackup = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
        $property = new ReflectionProperty('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', 'rootlineFields');
        $property->setAccessible(true);
        $rootLineFields = \Sys25\RnBase\Utility\Strings::trimExplode(',', self::$addRootLineFieldsBackup, true);
        $property->setValue(null, array_diff($property->getValue(null), $rootLineFields));
    }

    public static function resetAddRootlineFields()
    {
        if (null != self::$addRootLineFieldsBackup) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = self::$addRootLineFieldsBackup;
            $property = new ReflectionProperty('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', 'rootlineFields');
            $property->setAccessible(true);
            $rootLineFields = \Sys25\RnBase\Utility\Strings::trimExplode(',', self::$addRootLineFieldsBackup, true);
            $property->setValue(null, array_unique(array_merge($property->getValue(null), $rootLineFields)));
            self::$addRootLineFieldsBackup = null;
        }
    }
}
