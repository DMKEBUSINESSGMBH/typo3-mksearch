<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Lars Heber (das Medienkombinat)
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

tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Class for working on hierarchical tree data
 * 
 * @TODO: replace by ext:tx_mkstats_util_Tree (to come...)
 */
class tx_mksearch_util_Tree {
	/**
	 * Cache for calculated record trees.
	 * Cache can store tree data for several tables.
	 *
	 * @var array array['table1'=>array[], 'table2'=>...]
	 */
	private static $treeCache = array();
	
	/**
	 * Get a flat list of children of a generic node defined by configuration 
	 * 
	 * 
	 * @param array $fields		Fields for the tx_rnbase_util_SearchGeneric search
	 * @param array $options	Options for the tx_rnbase_util_SearchGeneric search
	 * 							Example:
	 * 							options {
	 * 								// You're encouraged to define field aliases via "as" sql clause to prevent hard to debug naming problems. Then, use THIS alias for $config['keyField'] 
	 * 								what = PAGES.uid as uid, PAGES.title, PAGES.pid
	 * 								searchdef {
	 * 									basetable = pages
	 * 									basetablealias = PAGES
	 * 									usealias = 1
	 * 								}
	 *							}				
	 * 
	 * @param array $config		Additional configuration:
	 * 							* string			keyField	Name of the field containing the record uid
	 * 							* string			parentField	Name of the field which contains the uid of the parent record
	 * 							* array | string	rootPoints	UID(s) of starting point(s) for which the children are to be searched
	 * 							* int optional=10	maxLevel	Max. number of levels to traverse. Note this is your safety net if something goes wrong with the db query so we find us back in an infinite loop!
	 * @return array
	 */
	public static function getFlatChildren(array $fields, array $options, array $config) {
		if (!isset($config['keyField'])) throw new Exception('tx_mksearch_util_Tree::getFlatChildren(): parameter $config[\'keyField\'] missing!');
		if (!isset($config['parentField'])) throw new Exception('tx_mksearch_util_Tree::getFlatChildren(): parameter $config[\'parentField\'] missing!');
		if (!isset($config['rootPoints'])) throw new Exception('tx_mksearch_util_Tree::getFlatChildren(): parameter $config[\'rootPoints\'] missing!');
		if (!isset($config['maxLevel'])) $config['maxLevel'] = 10;
		
		$result = array();
		$searcher = tx_rnbase_util_SearchBase::getInstance('tx_rnbase_util_SearchGeneric');
		$config['parentField'] = $options['searchdef']['basetablealias'].'.'. $config['parentField'];
		
		$parentIds = is_array($config['rootPoints']) ? implode(',' , $config['rootPoints']) : $config['rootPoints']; 

		// Just prepare so we can fill it later...
		$fields[$config['parentField']] = array(OP_IN_INT => 0);
		
		$i = 0;
		do {
			$fields[$config['parentField']][OP_IN_INT] = $parentIds;
			$children = $searcher->search($fields, $options);
			// Reset parents
			$parentIds = array();
			foreach ($children as $child) {
				$parentIds[] = $child[$config['keyField']];
				$result[] = $child;
			}
			$parentIds = implode(',', $parentIds);
		} while ($children and ++$i < $config['maxLevel']);
		return $result;
	}
	
	/**
	 * Get ancestors of a generic node according to configuration
	 *
	 * @param array $fields		Fields for the tx_rnbase_util_SearchGeneric search
	 * @param array $options	Options for the tx_rnbase_util_SearchGeneric search
	 * @param array $config		Additional configuration:
	 * 							* string					keyField		Name of the field containing the record uid
	 * 							* string					parentField		Name of the field which contains the uid of the parent record
	 * 							* string					startingPoint	UID of starting point for which the ancestors are to be searched
	 * 							* string | array optional	endingPoint		UID(s) of ancestor(s) where traversal stops
	 * 							* int optional=10			maxLevel		Max. number of levels to traverse. Note this is your safety net if something goes wrong with the db query so we find us back in an infinite loop!
	 * 
	 * @return array
	 * 
	 * @see self::getFlatChildren()
	 */
	public static function getAncestors(array $fields, array $options, array $config) {
		if (!isset($config['keyField'])) throw new Exception('tx_mksearch_util_Tree::getAncestors(): parameter $config[\'keyField\'] missing!');
		if (!isset($config['parentField'])) throw new Exception('tx_mksearch_util_Tree::getAncestors(): parameter $config[\'parentField\'] missing!');
		if (!isset($config['startingPoint'])) throw new Exception('tx_mksearch_util_Tree::getAncestors(): parameter $config[\'startingPoint\'] missing!');
		if (isset($config['endingPoint']) and !is_array($config['endingPoint'])) $config['endingPoint'] = array($config['endingPoint']);
		if (!isset($config['maxLevel'])) $config['maxLevel'] = 10;
		
		$result = array();
		$searcher = tx_rnbase_util_SearchBase::getInstance('tx_rnbase_util_SearchGeneric');
		$config['keyField'] = $options['searchdef']['basetablealias'].'.'. $config['keyField'];
		
		// Prepare search
		$fields[$config['keyField']] = array(OP_EQ_INT => $config['startingPoint']);
		
		$i = 0;
		while (
				$parent = $searcher->search($fields, $options) and 
				++$i < $config['maxLevel'] 
			) {
			$result[] = $parent[0];	
			$fields[$config['keyField']][OP_EQ_INT] = $parent[0][$config['parentField']];
			if (
				!$parent[0][$config['parentField']] or
				(isset($config['endingPoint']) and in_array($parent[0][$config['parentField']], $config['endingPoint']))
				)
				{break;}
		}

		return $result;
	}
	
	
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
		if (!isset(self::$page)) self::$page = t3lib_div::makeInstance('t3lib_pageSelect');
		return self::$page;
	}
	
	/**
	 * Return all uids in record trees of given uids.
	 * 
	 * This is a specialisation of self::getFlatChildren().
	 *
	 * @param array $uids
	 * @return array
	 */
	public static function getTreeUids(array $uids, $table, $keyField='uid', $parentField='pid') {
		global $GLOBALS;
		$cacheKey = serialize($uids);
		// Cache?
		if (isset(self::$treeCache[$table][$cacheKey]))
		return self::$treeCache[$table][$cacheKey];
		
		$ffields = array();
		
		// Turn off automatic enableFields,
		// as rn_base doesn't support skipping single enableField options (yet?)
		// Thus, WE have to take care of enableFields
		// Not every table supports enable fields...
		if (isset($GLOBALS['TCA'][$table]['ctrl']) && is_array($GLOBALS['TCA'][$table]['ctrl'])) {
			$enable = self::page()->enableFields($table, null, array('fe_group' => true));
			// Cut "AND" in the beginning
			$enable = substr($enable, strpos($enable, "AND ") + 4);
			$ffields[SEARCH_FIELD_CUSTOM] = $enable;
		}

		$ooptions = array(
						'what' => $keyField,
						'searchdef' => array(
 								'basetable' => $table,
								'basetablealias' => $table,
								'usealias' => 1,
								),
						'enablefieldsoff' => true,
//						'debug' => 1,
					);
		$config = array(
						'keyField' => $keyField,
						'parentField' => $parentField,
						'rootPoints' => $uids,
					);
						
		$result = $uids;
						
		foreach (tx_mksearch_util_Tree::getFlatChildren($ffields, $ooptions, $config) as $childCat) {
			$result[] = intval($childCat['uid']);
		}
		
		// Fill cache
		if (!isset(self::$treeCache[$table])) self::$treeCache[$table] = array();
		self::$treeCache[$table][$cacheKey] = array_unique($result); 
		return self::$treeCache[$table][$cacheKey];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tree.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tree.php']);
}