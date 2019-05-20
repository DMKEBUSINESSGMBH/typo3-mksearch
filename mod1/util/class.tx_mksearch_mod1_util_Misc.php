<?php

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_DB');
tx_rnbase::load('Tx_Rnbase_Backend_Utility_Icons');

/**
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Misc
{
    /**
     * @param tx_rnbase_mod_IModule $mod
     *
     * @return mixed null or string
     */
    public static function checkPid(tx_rnbase_mod_IModule $mod)
    {
        $pages = self::getStorageFolders();
        if ($mod->getPid() && (empty($pages) || isset($pages[$mod->getPid()]))) {
            return null;
        }
        foreach ($pages as $pid => &$page) {
            $pageRecord = Tx_Rnbase_Backend_Utility::getRecord('pages', $pid);
            if ($pageRecord == null) {
                continue;
            }
            $pageinfo = Tx_Rnbase_Backend_Utility::readPageAccess($pid, $mod->perms_clause);
            $modUrl = Tx_Rnbase_Backend_Utility::getModuleUrl('web_MksearchM1', array('id' => $pid), '');
            $page = '<a href="'.$modUrl.'">';
            $page .= Tx_Rnbase_Backend_Utility_Icons::getSpriteIconForRecord('pages', $pageRecord);
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

        $database = Tx_Rnbase_Database_Connection::getInstance();
        $pages = array_merge(
            // wir holen alle seiten auf denen indexer liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_indices', array('enablefieldsbe' => 1)),
            // wir holen alle seiten auf denen configs liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_indexerconfigs', array('enablefieldsbe' => 1)),
            // wir holen alle seiten auf denen composites liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_configcomposites', array('enablefieldsbe' => 1)),
            // wir holen alle seiten auf denen keywords liegen
            $database->doSelect('pid as pageid', 'tx_mksearch_keywords', array('enablefieldsbe' => 1)),
            // wir holen alle seiten die mksearch beinhalten
            $database->doSelect('uid as pageid', 'pages', array('enablefieldsbe' => 1, 'where' => 'module=\'mksearch\''))
        );
        if (empty($pages)) {
            return array();
        }
        // wir mergen die seiten zusammen
        $pages = call_user_func_array('array_merge_recursive', array_values($pages));
        if (empty($pages['pageid'])) {
            return array();
        }
        // Wenn nur ein Eintrag existiert, haben wir hier einen String!
        if (!is_array($pages['pageid'])) {
            $pages['pageid'] = array($pages['pageid']);
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
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Misc.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Misc.php'];
}
