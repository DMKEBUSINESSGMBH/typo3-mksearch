<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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

class tx_mksearch_util_UserGroups
{
    /**
     * Cache for storing actually resulting fe groups of pages.
     *
     * Note that this cache is used for both pages and tt_contents!
     *
     * @var array('groups' => [array], 'local' => [array])
     *                     * 'groups':  all groups of a page relevant when page is seen as parent of another page
     *                     * 'local':   all groups of a page in its local context (i.e. including local fe groups which aren't relevant for children because access rights are not marked as "extend to subpages")
     */
    private $resultingAccessCache = array();

    /**
     * Cache for storing subgroups.
     *
     * @var array
     */
    private static $groupCache = array();

    /**
     * @return tx_mksearch_util_UserGroups
     */
    public static function getInstance()
    {
        static $instance = null;
        if (!is_object($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Fetch all subgroups for the given group.
     *
     * @param int   $groupId
     * @param array $callStackGroups For internal use only! As groups and subgroups may define an endless recursion, all groups of the current call stack are stored here to make it possible to break through the endless loop!
     *
     * @return array
     */
    private function getSubGroups($groupId, array $callStackGroups = array())
    {
        if (isset($this->groupCache[$groupId])) {
            return $this->groupCache[$groupId];
        }

        // the old obsolete db connection
        $connection = Tx_Rnbase_Database_Connection::getInstance()->getDatabaseConnection();

        // Simplify query via table sys_refindex so what
        // we don't need to struggle with those stupid csv fields...
        $res = $connection->exec_SELECTquery(
            'ref_uid',
            'fe_groups JOIN sys_refindex AS ref ON fe_groups.uid = ref.ref_uid',
            '1=1'.tx_rnbase_util_TYPO3::getSysPage()->enableFields('fe_groups').
            ' AND ref.ref_table = \'fe_groups\' AND ref.field = \'subgroup\''.
            ' AND ref.recuid = '.intval($groupId)
        );
        // Initialize suber group array with ourselves!
        $sub = array($groupId);

        while ($row = $connection->sql_fetch_assoc($res)) {
            // Recursively get sub groups, avoiding endless recursion
            if (!in_array($row['ref_uid'], $callStackGroups)) {
                $sub = array_merge($sub, $this->getSubGroups($row['ref_uid'], $sub));
            }
        }

        // Eliminate duplicates
        if ($sub) {
            $sub = array_unique($sub);
        }

        // Fill cache
        $this->groupCache[$groupId] = $sub;

        return $sub;
    }

    /**
     * Actually calculate fe_groups.
     *
     * Calculation means to respect the fe_groups of
     * hierarchically superordinated pages, and also
     * includes exploring of all implicite subgroups
     * which are finally returned explicitely.
     *
     * @param int $pid
     *
     * @return array('groups' => [array], 'extendToSubPages' => [bool])
     *
     * @see self::getEffectiveFeGroups()
     *
     * @todo Move to tx_mksearch_service_indexer_BaseDataBase
     */
    private function calculateEffectiveFeGroups($pid)
    {
        // In cache?
        if (isset($this->resultingAccessCache[$pid])) {
            return $this->resultingAccessCache[$pid];
        }
        // else: Begin calculating...
        $rootline = tx_mksearch_util_Indexer::getInstance()->getRootlineByPid($pid);

        if (!sizeof($rootline)) {
            // MW: Keine Rootline gefunden, Seite gelöscht!?
            return false;
        }

        // Page's index in rootline array (i.e. last element)
        $selfIndex = sizeof($rootline) - 1;

        // Get fe groups and all their superordinate groups
        // as these are also allowed to view the page!
        $self = array();

        $baseGroups = Tx_Rnbase_Utility_Strings::trimExplode(',', $rootline[$selfIndex]['fe_group'], true);
        foreach ($baseGroups as $b) {
            $sub = self::getSubGroups($b);
            if ($sub) {
                $self = array_merge($self, $sub);
            }
        }
        $self = array_unique($self);

        $this->resultingAccessCache[$pid] = array();

        // We're root! We're god! Our access rules are valid without any further checks!
        if (1 == sizeof($rootline)) {
            $this->resultingAccessCache[$pid]['groups'] = $self;
        } // We really have to calculate...
        else {
            // Get parent page's access rights
            $parent = $this->calculateEffectiveFeGroups($rootline[$selfIndex]['pid']);

            if (false === $parent) { // MW: keine parrent uid found
                $this->resultingAccessCache[$pid]['groups'] = $self;
            } else {
                // If current page has explicitely set FE groups:
                if ($rootline[$selfIndex]['fe_group']) {
                    // Prepare merged parent and current pages' fe_groups:
                    if ($parent['groups']) {
                        $merged = array_intersect($parent['groups'], $self);
                    } else {
                        $merged = $self;
                    }

                    // Current page's fe_groups forced to "extend to subpages"?
                    // Effective groups are merged ones:
                    if ($rootline[$selfIndex]['extendToSubpages']) {
                        $this->resultingAccessCache[$pid]['groups'] = $merged;
                    } // Current page's fe_groups NOT forced to "extend to subpages"?
                    // Merged groups are only locally valid,
                    // whilst groups valid for children are only parent page's groups...
                    else {
                        $this->resultingAccessCache[$pid]['groups'] = $parent['groups'];
                        $this->resultingAccessCache[$pid]['local'] = $merged;
                    }
                } // No fe_groups defined in current page: Use parent page's groups
                else {
                    $this->resultingAccessCache[$pid]['groups'] = $parent['groups'];
                }
            }
        }

        return $this->resultingAccessCache[$pid];
    }

    /**
     * Return calculated FE groups of the given page.
     *
     * @param int $pid ID of page, for which FE groups are searched
     *
     * @return array
     */
    public function getEffectivePageFeGroups($pid)
    {
        $foo = $this->calculateEffectiveFeGroups($pid);
        if (isset($foo['local'])) {
            return $foo['local'];
        }
        // else
        return $foo['groups'];
    }

    /**
     * Return a content element's FE groups.
     *
     * @param int   $pid      ID of the content element's page
     * @param array $ceGroups FE groups of content element
     *
     * @return array
     */
    public function getEffectiveContentElementFeGroups($pid, $ceGroups)
    {
        // Page's effective groups:
        $groupsArr = $this->calculateEffectiveFeGroups($pid);
        $groupsArr = (isset($groupsArr['local'])) ? $groupsArr['local'] : $groupsArr['groups'];
        $groupsArr = !empty($groupsArr) ? $groupsArr : array(0);

        // Explicite groups for content element?
        if ($ceGroups) {
            $groupsArr = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($groupsArr, $ceGroups);
        }

        if (empty($groupsArr)) {
            $groupsArr[] = 0;
        } // Wenn keine Gruppe gesetzt ist, dann die 0 speichern

        return $groupsArr;
    }
}
