<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__]);

    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::STRICT_BOOLEANS,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);

    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->ruleWithConfiguration(
        ExtEmConfRector::class,
        [
            ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.0-8.4.99',
            ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '12.4.0-13.4.99',
            ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
        ]
    );
    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);

    $rectorConfig->phpstanConfig(Typo3Option::PHPSTAN_FOR_RECTOR_PATH);
    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon');

    $rectorConfig->skip([
        'Resources/Private/PHP/**',
        // no namespace imports for these files:
        NameImportingPostRector::class => [
            'ext_localconf.php',
            'ext_tables.php',
            __DIR__.'/Configuration/*.php',
            __DIR__.'/Configuration/**/*.php',
        ],
        // this would generate a faulty setting of TypoScript setup
        Ssch\TYPO3Rector\TYPO312\v1\TemplateServiceToServerRequestFrontendTypoScriptAttributeRector::class => [
            __DIR__.'/Classes/ViewHelpers/Format/HtmlViewHelper.php',
        ],

        // the gridelement classes are not available in TYPO3 13.4 and phpstan  runs into an error
        // if ::class is used
        Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class => [
            '/ext_localconf.php',
        ],

        // We check for a class name that does not exist in TYPO3 12.4
        Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector::class => [
            __DIR__.'/scheduler/class.tx_mksearch_scheduler_IndexTaskAddFieldProvider.php',
        ],
    ]);

    // keep backwards compatibility to TYPO3 12.4
    if (class_exists(Ssch\TYPO3Rector\TYPO313\v0\MigrateTypoScriptFrontendControllerReadOnlyPropertiesRector::class)) {
        $rectorConfig->skip([
            Ssch\TYPO3Rector\TYPO313\v0\MigrateTypoScriptFrontendControllerReadOnlyPropertiesRector::class,
        ]);
    }

    if (class_exists(Ssch\TYPO3Rector\TYPO313\v0\MigrateTypoScriptFrontendControllerFeUserRector::class)) {
        $rectorConfig->skip([
            Ssch\TYPO3Rector\TYPO313\v0\MigrateTypoScriptFrontendControllerFeUserRector::class,
        ]);
    }

    // declare strict breaks TER releases
    if (class_exists(Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class)) {
        $rectorConfig->skip([
            Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class => [
                '/ext_emconf.php',
            ],
        ]);
    }
};
