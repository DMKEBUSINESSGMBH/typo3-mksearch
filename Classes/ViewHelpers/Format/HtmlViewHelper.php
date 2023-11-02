<?php

namespace DMK\Mksearch\ViewHelpers\Format;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/*
 * DMK\Mksearch\ViewHelpers\Format$HtmlViewHelper
 *
 * nähere Infos in Configuration/XClasses.php
 *
 * @package         TYPO3
 * @subpackage      mksearch
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class HtmlViewHelper.
 *
 * This class can be removed with TYPO3 13 as the simulation of the FE in BE context should be dropped.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 *
 * @see \TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper
 */
class HtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Children must not be escaped, to be able to pass {bodytext} directly to it.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Plain HTML should be returned, no output escaping allowed.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('parseFuncTSPath', 'string', 'Path to the TypoScript parseFunc setup.', false, 'lib.parseFunc_RTE');
        $this->registerArgument('data', 'mixed', 'Initialize the content object with this set of data. Either an array or object.');
        $this->registerArgument('current', 'string', 'Initialize the content object with this value for current property.');
        $this->registerArgument('currentValueKey', 'string', 'Define the value key, used to locate the current value for the content object');
        $this->registerArgument('table', 'string', 'The table name associated with the "data" argument.', false, '');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $parseFuncTSPath = $arguments['parseFuncTSPath'];
        $data = $arguments['data'];
        $current = $arguments['current'];
        $currentValueKey = $arguments['currentValueKey'];
        $table = $arguments['table'];

        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        $isBackendRequest = $request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isBackend();
        if ($isBackendRequest) {
            // @deprecated since v12, remove in v13: Drop simulateFrontendEnvironment() and resetFrontendEnvironment() and throw a \RuntimeException here.
            trigger_error('Using f:format.html in backend context has been deprecated in TYPO3 v12 and will be removed with v13', E_USER_DEPRECATED);
            $tsfeBackup = self::simulateFrontendEnvironment();
        }

        $value = $renderChildrenClosure() ?? '';

        // Prepare data array
        if (is_object($data)) {
            $data = ObjectAccess::getGettableProperties($data);
        } elseif (!is_array($data)) {
            $data = (array) $data;
        }

        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->setRequest($request);
        $contentObject->start($data, $table);

        if (null !== $current) {
            $contentObject->setCurrentVal($current);
        } elseif (null !== $currentValueKey && isset($data[$currentValueKey])) {
            $contentObject->setCurrentVal($data[$currentValueKey]);
        }

        $content = $contentObject->parseFunc($value, null, '< '.$parseFuncTSPath);

        if ($isBackendRequest) {
            self::resetFrontendEnvironment($tsfeBackup);
        }

        return $content;
    }

    /**
     * Copies the specified parseFunc configuration to $GLOBALS['TSFE']->tmpl->setup in Backend mode.
     * This somewhat hacky work around is currently needed because ContentObjectRenderer->parseFunc() relies on those variables to be set.
     *
     * @return ?TypoScriptFrontendController The 'old' backed up $GLOBALS['TSFE'] or null
     */
    protected static function simulateFrontendEnvironment(): ?TypoScriptFrontendController
    {
        // @todo: We may want to deprecate this entirely and throw an exception in v13 when this VH is used in BE scope:
        //        Core has no BE related usages to this anymore and in general it shouldn't be needed in BE scope at all.
        //        If BE really relies on content being processed via FE parseFunc, a controller should do this and assign
        //        the processed value directly, which could then be rendered using f:format.raw.
        $tsfeBackup = $GLOBALS['TSFE'] ?? null;
        if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
            $GLOBALS['TSFE'] = new \stdClass();
            $GLOBALS['TSFE']->tmpl = new \stdClass();
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            $GLOBALS['TSFE']->tmpl->setup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        }

        return $tsfeBackup;
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment().
     */
    protected static function resetFrontendEnvironment(?TypoScriptFrontendController $tsfeBackup): void
    {
        if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
            $GLOBALS['TSFE'] = $tsfeBackup;
        }
    }
}
