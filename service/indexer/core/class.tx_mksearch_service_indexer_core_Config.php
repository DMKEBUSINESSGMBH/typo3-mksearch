<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_service_indexer_BaseDataBase');
tx_rnbase::load('tx_mksearch_util_Tree');

/**
 * Helper class for configuring core contents
 */
class tx_mksearch_service_indexer_core_Config {
	
	/**
	 * Page instance
	 *
	 * @var t3lib_pageSelect
	 */
	private static $page;

	/**
	 * Return page instance
	 *
	 * @return t3lib_pageSelect
	 */
	private static function page() {
		if (!isset(self::$page))
			self::$page = t3lib_div::makeInstance('t3lib_pageSelect');
		return self::$page;
	}
	
	/**
	 * Cache for storing actually resulting fe groups of pages
	 *
	 * Note that this cache is used for both pages and tt_contents!
	 *
	 * @var array('groups' => [array], 'local' => [array])
	 * * 'groups':	all groups of a page relevant when page is seen as parent of another page
	 * * 'local':	all groups of a page in its local context (i.e. including local fe groups which aren't relevant for children because access rights are not marked as "extend to subpages")
	 */
	private static $resultingAccessCache = array();
	
	/**
	 * Cache for storing subgroups
	 *
	 * @var array
	 */
	private static $groupCache = array();
	
	/**
	 * Returns the rootline of the given page id.
	 * @param 	int 	$uid
	 * @return 	array
	 */
	public static function getRootLine($uid){
		return self::page()->getRootLine($uid);
	}
	/**
	 * Returns the siteroot page of the given page.
	 * @param 	int 	$uid
	 * @return 	array
	 */
	public static function getSiteRootPage($uid){
		foreach (self::page()->getRootLine($uid) as $page){
			if($rootPage['is_siteroot']) {
				return $page;
			}
		}
		return array();
	}
	/**
	 * Returns al list of page ids from the siteroot page of the given page.
	 * @param 	int 	$uid
	 * @return 	string
	 */
	public static function getPidListFromSiteRootPage($uid, $recursive = 0){
		$rootPage = self::getSiteRootPage($uid);
		if(empty($rootPage)) return '';
		tx_rnbase::load('tx_rnbase_util_DB');
		return tx_rnbase_util_DB::_getPidList($rootPage['uid'], $recursive);
	}
	
	/**
	 * Fetch all subgroups for the given group
	 *
	 * @param int $groupId
	 * @param array $callStackGroups	For internal use only! As groups and subgroups may define an endless recursion, all groups of the current call stack are stored here to make it possible to break through the endless loop!
	 * @return array
	 */
	private static function getSubGroups($groupId, array $callStackGroups=array()) {
		// Cache?
		if (isset(self::$groupCache[$groupId])) return self::$groupCache[$groupId];
		
		// Simplify query via table sys_refindex so what
		// we don't need to struggle with those stupid csv fields...
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
									'ref_uid',
									'fe_groups JOIN sys_refindex AS ref ON fe_groups.uid = ref.ref_uid',
											'1=1' . self::page()->enableFields('fe_groups') .
									' AND ref.ref_table = \'fe_groups\' AND ref.field = \'subgroup\'' .
									' AND ref.recuid = ' . intval($groupId)
								);
		// Initialize suber group array with ourselves!
		$sub = array($groupId);
		
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			// Recursively get sub groups, avoiding endless recursion
			if (!in_array($row['ref_uid'], $callStackGroups))
				$sub = array_merge($sub, self::getSubGroups($row['ref_uid'], $sub));
		}

		// Eliminate duplicates
		if ($sub) $sub = array_unique($sub);
		
		// Fill cache
		self::$groupCache[$groupId] = $sub;
		
		return $sub;
	}
	
	/**
	 * Actually calculate fe_groups
	 *
	 * Calculation means to respect the fe_groups of
	 * hierarchically superordinated pages, and also
	 * includes exploring of all implicite subgroups
	 * which are finally returned explicitely.
	 *
	 * @param int $pid
	 * @return array('groups' => [array], 'extendToSubPages' => [bool])
	 * @see self::getEffectiveFeGroups()
	 * @todo Move to tx_mksearch_service_indexer_BaseDataBase
	 */
	private static function calculateEffectiveFeGroups($pid) {
		// In cache?
		if (isset(self::$resultingAccessCache[$pid])) return self::$resultingAccessCache[$pid];
		// else: Begin calculating...
		$rootline = self::getRootLine($pid);
		
		if (!sizeof($rootline))
			// MW: Keine Rootline gefunden, Seite gelöscht!?
			return false;
//			throw new Exception('tx_mksearch_service_indexer_core_Config->calculateEffectiveFeGroups(): No rootline found for pid '.$pid.'.');

		// Page's index in rootline array (i.e. last element)
		$selfIndex = sizeof($rootline)-1;
			
		// Get fe groups and all their superordinate groups
		// as these are also allowed to view the page!
		$self = array();
		
		$baseGroups = t3lib_div::trimExplode(',', $rootline[$selfIndex]['fe_group'], true);
		foreach ($baseGroups as $b) {
			$sub = self::getSubGroups($b);
			if ($sub) $self = array_merge($self, $sub);
		}
		$self = array_unique($self);
		
		self::$resultingAccessCache[$pid] = array();
		
		// We're root! We're god! Our access rules are valid without any further checks!
		if (sizeof($rootline) == 1) {
			self::$resultingAccessCache[$pid]['groups'] = $self;
		}
		
		// We really have to calculate...
		else {
			// Get parent page's access rights
			$parent = self::calculateEffectiveFeGroups($rootline[$selfIndex]['pid']);

			if($parent===false) { // MW: keine parrent uid found
				//@todo MW: mir fehlt hier momentan das verständniss, ist das so korrekt?
				self::$resultingAccessCache[$pid]['groups'] = $self;
			} else {
				// If current page has explicitely set FE groups:
				if ($rootline[$selfIndex]['fe_group']) {
					// Prepare merged parent and current pages' fe_groups:
					if ($parent['groups']) $merged = array_intersect($parent['groups'], $self);
					else $merged = $self;
					
					// Current page's fe_groups forced to "extend to subpages"?
					// Effective groups are merged ones:
					if ($rootline[$selfIndex]['extendToSubpages'])
						self::$resultingAccessCache[$pid]['groups'] = $merged;
					// Current page's fe_groups NOT forced to "extend to subpages"?
					// Merged groups are only locally valid,
					// whilst groups valid for children are only parent page's groups...
					else {
						self::$resultingAccessCache[$pid]['groups'] = $parent['groups'];
						self::$resultingAccessCache[$pid]['local'] = $merged;
					}
				}
				// No fe_groups defined in current page: Use parent page's groups
				else self::$resultingAccessCache[$pid]['groups'] = $parent['groups'];
			}
		}
		return self::$resultingAccessCache[$pid];
	}
	
	/**
	 * Return calculated FE groups of the given page
	 *
	 * @param int $pid	ID of page, for which FE groups are searched
	 * @return array
	 */
	public static function getEffectivePageFeGroups($pid) {
		$foo = self::calculateEffectiveFeGroups($pid);
		if (isset($foo['local'])) return $foo['local'];
		// else
		return $foo['groups'];
	}
	
	/**
	 * Return a content element's FE groups
	 *
	 * @param int	$pid		ID of the content element's page
	 * @param array	$ceGroups	FE groups of content element
	 * @return array
	 */
	public static function getEffectiveContentElementFeGroups($pid, $ceGroups) {
		// Page's effective groups:
		$groupsArr = self::calculateEffectiveFeGroups($pid);
		$groupsArr = (isset($groupsArr['local'])) ? 	$groupsArr['local'] : $groupsArr['groups'];

		// Explicite groups for content element?
		if ($ceGroups && is_array($groupsArr))
			$groupsArr = array_intersect($groupsArr, $ceGroups);

		if(empty($groupsArr))
			$groupsArr[] = 0; // Wenn keine Gruppe gesetzt ist, dann die 0 speichern
		return $groupsArr;
	}
	
	/**
	 * Return SQL where clause containing include and exclude pages
	 *
	 * @param array 	$include		Include options as returned by self::getIncludeExclude()
	 * @param array		$exclude		Exclude options as returned by self::getExcludeExclude()
	 * @return string
	 * @todo Move to tx_mksearch_service_indexer_BaseDataBase, making it generic
	 */
	public static function getIncludeExcludeWhere(array $include, array $exclude) {
		$whereAll = array();
		$foo = array();
		foreach ($include as $param=>$uids)
			$foo[] = $param . ' IN (' . implode(',', $uids) . ')';
		if (count($foo)) $whereAll[] = '(' . implode(' OR ', $foo) . ')';

		$foo = array();
		foreach ($exclude as $param=>$uids)
			$foo[] = $param . ' IN (' . implode(',', $uids) . ')';
		if (count($foo)) $whereAll[] = '(' . implode(' OR ', $foo) . ')';
		
		if (count($whereAll))
			return ' AND ' . implode(' AND ', $whereAll);
		else return '';
	}
	
	
	/**
	 * Return arrays of include and exclude uids
	 *
	 * @param array 	$include		Include options as defined in tx_mksearch_service_indexer_core_Page config options
	 * @param array		$exclude		Exclude options as defined in tx_mksearch_service_indexer_core_Page config options
	 * @param array		$parame=array	Array of parameter suffixes to be processed, with the array key being the name of the column used for restriction in SQL statement
	 * @return array['include' => array[param1 => array[uids], param2 => array[uids]], 'exclude' => ...]
	 * @todo Move to tx_mksearch_service_indexer_BaseDataBase, making it generic
	 */
	public static function getIncludeExclude(array $include, array $exclude, array $params=array('uid' => 'page')) {
		$res = array('include' => array(), 'exclude' => array());
		foreach ($params as $column=>$s) {
			if ($include) {
				if ($s == 'page' and isset($include[$s.'Trees.']))
					$res['include'][$column] = tx_mksearch_util_Tree::getTreeUids($include[$s.'Trees.'], 'pages');
				if (isset($include[$s.'s.'])) {
					if (!isset($res['include'][$column])) $res['include'][$column] = array();
					$res['include'][$column] = array_merge($res['include'][$column], $include[$s.'s.']);
				}
				if (isset($res['include'][$column]))
					$res['include'][$column] = array_unique($res['include'][$column]);
			}
			
			if ($exclude) {
				if ($s == 'page' and isset($exclude[$s.'Trees.']))
					$res['exclude'][$column] = tx_mksearch_util_Tree::getTreeUids($exclude[$s.'Trees.'], 'pages');
				if (isset($exclude[$s.'s'])) {
					if (!isset($res['exclude'][$column])) $res['exclude'][$column] = array();
					$res['exclude'][$column] = array_merge($res['exclude'][$column], $exclude[$s.'s.']);
				}
				if (isset($res['exclude'][$column]))
					$res['exclude'][$column] = array_unique($res['exclude'][$column]);
			}
		}
		return $res;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_Config.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_Config.php']);
}