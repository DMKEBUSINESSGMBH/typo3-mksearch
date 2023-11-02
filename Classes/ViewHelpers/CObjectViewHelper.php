<?php

namespace DMK\Mksearch\ViewHelpers;

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
 * DMK\Mksearch\ViewHelpers\Format$HtmlViewHelper.
 *
 * nähere Infos in Configuration/XClasses.php
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class CObjectViewHelper.
 *
 * This class can be removed with TYPO3 13 as the simulation of the FE in BE context should be dropped.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 *
 * @see \TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper
 */
class CObjectViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Disable escaping of child nodes' output.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Disable escaping of this node's output.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('data', 'mixed', 'the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used');
        $this->registerArgument('typoscriptObjectPath', 'string', 'the TypoScript setup path of the TypoScript object to render', true);
        $this->registerArgument('currentValueKey', 'string', 'currentValueKey');
        $this->registerArgument('table', 'string', 'the table name associated with "data" argument. Typically tt_content or one of your custom tables. This argument should be set if rendering a FILES cObject where file references are used, or if the data argument is a database record.', false, '');
    }

    /**
     * Renders the TypoScript object in the given TypoScript setup path.
     *
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $data = $renderChildrenClosure();
        $typoscriptObjectPath = (string) $arguments['typoscriptObjectPath'];
        $currentValueKey = $arguments['currentValueKey'];
        $table = $arguments['table'];
        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        $contentObjectRenderer = self::getContentObjectRenderer($request);
        $contentObjectRenderer->setRequest($request);
        $tsfeBackup = null;
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            $tsfeBackup = self::simulateFrontendEnvironment();
        }
        $currentValue = null;
        if (is_object($data)) {
            $data = ObjectAccess::getGettableProperties($data);
        } elseif (is_string($data) || is_numeric($data)) {
            $currentValue = (string) $data;
            $data = [$data];
        }
        $contentObjectRenderer->start($data, $table);
        if (null !== $currentValue) {
            $contentObjectRenderer->setCurrentVal($currentValue);
        } elseif (null !== $currentValueKey && isset($data[$currentValueKey])) {
            $contentObjectRenderer->setCurrentVal($data[$currentValueKey]);
        }
        $pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
        $lastSegment = (string) array_pop($pathSegments);
        $setup = self::getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        foreach ($pathSegments as $segment) {
            if (!array_key_exists($segment.'.', $setup)) {
                throw new Exception('TypoScript object path "'.$typoscriptObjectPath.'" does not exist', 1253191023);
            }
            $setup = $setup[$segment.'.'];
        }
        if (!isset($setup[$lastSegment])) {
            throw new Exception('No Content Object definition found at TypoScript object path "'.$typoscriptObjectPath.'"', 1540246570);
        }
        $content = self::renderContentObject($contentObjectRenderer, $setup, $typoscriptObjectPath, $lastSegment);
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            self::resetFrontendEnvironment($tsfeBackup);
        }

        return $content;
    }

    /**
     * Renders single content object and increases time tracker stack pointer.
     */
    protected static function renderContentObject(ContentObjectRenderer $contentObjectRenderer, array $setup, string $typoscriptObjectPath, string $lastSegment): string
    {
        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        if ($timeTracker->LR) {
            $timeTracker->push('/f:cObject/', '<'.$typoscriptObjectPath);
        }
        $timeTracker->incStackPointer();
        $content = $contentObjectRenderer->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment.'.'] ?? [], $typoscriptObjectPath);
        $timeTracker->decStackPointer();
        if ($timeTracker->LR) {
            $timeTracker->pull($content);
        }

        return $content;
    }

    protected static function getConfigurationManager(): ConfigurationManagerInterface
    {
        // @todo: this should be replaced by DI once Fluid can handle DI properly
        return GeneralUtility::getContainer()->get(ConfigurationManagerInterface::class);
    }

    protected static function getContentObjectRenderer(ServerRequestInterface $request): ContentObjectRenderer
    {
        if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
            $tsfe = $GLOBALS['TSFE'];
        } else {
            $site = $request->getAttribute('site');
            if (!($site instanceof SiteInterface)) {
                $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
                $site = reset($sites);
            }
            $language = $request->getAttribute('language') ?? $site->getDefaultLanguage();
            $pageArguments = $request->getAttribute('routing') ?? new PageArguments(0, '0', []);
            $tsfe = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                GeneralUtility::makeInstance(Context::class),
                $site,
                $language,
                $pageArguments,
                GeneralUtility::makeInstance(FrontendUserAuthentication::class)
            );
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class, $tsfe);
        $parent = $request->getAttribute('currentContentObject');
        if ($parent instanceof ContentObjectRenderer) {
            $contentObjectRenderer->setParent($parent->data, $parent->currentRecord);
        }

        return $contentObjectRenderer;
    }

    /**
     * \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->cObjGetSingle() relies on $GLOBALS['TSFE'].
     */
    protected static function simulateFrontendEnvironment(): ?TypoScriptFrontendController
    {
        $tsfeBackup = $GLOBALS['TSFE'] ?? null;
        if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
            $GLOBALS['TSFE'] = new \stdClass();
            $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
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

    /**
     * Explicitly set argument name to be used as content.
     */
    public function resolveContentArgumentName(): string
    {
        return 'data';
    }
}
