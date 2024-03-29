<?php

use Sys25\RnBase\Backend\Module\IModFunc;

/**
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Misc
{
    /**
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     *
     * @return mixed null or string
     */
    public static function checkPid(\Sys25\RnBase\Backend\Module\IModule $mod)
    {
        $pages = self::getStorageFolders();
        if ($mod->getPid() && (empty($pages) || isset($pages[$mod->getPid()]))) {
            return null;
        }
        foreach ($pages as $pid => &$page) {
            $pageRecord = \Sys25\RnBase\Backend\Utility\BackendUtility::getRecord('pages', $pid);
            if (null == $pageRecord) {
                continue;
            }
            $pageinfo = \Sys25\RnBase\Backend\Utility\BackendUtility::readPageAccess(
                $pid,
                $GLOBALS['BE_USER']->getPagePermsClause(\TYPO3\CMS\Core\Type\Bitmask\Permission::PAGE_SHOW)
            );
            $modUrl = \Sys25\RnBase\Backend\Utility\BackendUtility::getModuleUrl('web_MksearchM1', ['id' => $pid]);
            $page = '<a href="'.$modUrl.'">';
            $page .= \Sys25\RnBase\Backend\Utility\Icons::getSpriteIconForRecord('pages', $pageRecord);
            $page .= ' '.$pageinfo['title'];
            $page .= ' '.htmlspecialchars($pageinfo['_thePath']);
            $page .= '</a>';
        }
        $out = '<div class="tables graybox">';
        $out .= '<h2 class="bgColor2 t3-row-header">###LABEL_NO_PAGE_SELECTED###</h2>';
        if (!empty($pages)) {
            $out .= '<ul><li>'.implode('</li><li>', $pages).'</li></ul>';
        }
        $out .= '</div>';

        return $out;
    }

    /**
     * Liefert Page Ids zu seiten mit mksearch inhalten.
     *
     * @return array
     */
    private static function getStorageFolders()
    {
        static $pids = false;
        if (is_array($pids)) {
            return $pids;
        }

        $database = \Sys25\RnBase\Database\Connection::getInstance();
        $pages = array_merge(
            // wir holen alle seiten auf denen indexer liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_indices', ['enablefieldsbe' => 1]),
            // wir holen alle seiten auf denen configs liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_indexerconfigs', ['enablefieldsbe' => 1]),
            // wir holen alle seiten auf denen composites liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_configcomposites', ['enablefieldsbe' => 1]),
            // wir holen alle seiten auf denen keywords liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_keywords', ['enablefieldsbe' => 1]),
            // wir holen alle seiten die mksearch beinhalten
            $database->doSelect('uid as pageid', 'pages', ['enablefieldsbe' => 1, 'where' => 'module=\'mksearch\''])
        );
        if (empty($pages)) {
            return [];
        }
        // wir mergen die seiten zusammen
        $pages = call_user_func_array('array_merge_recursive', array_values($pages));
        if (empty($pages['pageid'])) {
            return [];
        }
        // Wenn nur ein Eintrag existiert, haben wir hier einen String!
        if (!is_array($pages['pageid'])) {
            $pages['pageid'] = [$pages['pageid']];
        }
        // wir machen aus den pid keys
        $pages = array_flip($pages['pageid']);
        // pid 0 schließ0en wir aus
        if (isset($pages[0])) {
            unset($pages[0]);
        }
        $pids = $pages;

        return $pids;
    }

    /**
     * @param string|\Psr\Http\Message\ResponseInterface $content
     *
     * @return \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface|string|\TYPO3\CMS\Core\Http\RedirectResponse|string
     */
    public static function getSubModuleContent(
        $content,
        IModFunc $subModule,
        string $subModuleName
    ) {
        $ret = tx_mksearch_mod1_util_Misc::checkPid($subModule->getModule());
        if ($ret) {
            if (\Sys25\RnBase\Utility\TYPO3::isTYPO121OrHigher()) {
                $modUrl = \Sys25\RnBase\Backend\Utility\BackendUtility::getModuleUrl(
                    $subModuleName.'.pageNotSelected',
                    ['id' => $subModule->getModule()->getPid()]
                );
            } else {
                $modUrl = \Sys25\RnBase\Backend\Utility\BackendUtility::getModuleUrl(
                    'web_MksearchM1',
                    ['id' => $subModule->getModule()->getPid()]
                );
            }

            return new \TYPO3\CMS\Core\Http\RedirectResponse($modUrl);
        }

        if ($content instanceof \Psr\Http\Message\ResponseInterface) {
            $content = $content->withBody(
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\StreamFactory::class)->createStream(
                    tx_mksearch_mod1_util_Template::parseBasics($content->getBody()->getContents(), $subModule)
                )
            );
        } else {
            $content = tx_mksearch_mod1_util_Template::parseBasics($content, $subModule);
        }

        return $content;
    }
}
