<?php

namespace DMK\Mksearch\Utility;

/**
 * Copyright notice.
 *
 * (c) 2018 DMK E-Business GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * extension configs.
 *
 * @author Michael Wagner
 */
final class ComposerUtility
{
    public static $autoloadedElastica = false;

    /**
     * preloads the.
     */
    public static function autoloadElastica()
    {
        if (true === self::$autoloadedElastica) {
            return;
        }

        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
            'mksearch',
            'Resources/Private/PHP/Elastica/Composer/autoload.php'
        );

        self::$autoloadedElastica = true;
    }
}
