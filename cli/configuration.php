<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
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

if (!defined('TYPO3_cliMode'))  die('You cannot run this script directly!');

/**
 * This file is a sample configuration file
 * which configures indexers to build different indexes
 * in varying circumstances, e.g. for different languages.
 *
 * Extend this file or clone it and customize the cloned one.
 * It will usually be reasonable to have one configuration file
 * for each index to be built.
 */

// Indexers to be used.
// This array is extended by the extension which provides an indexer service themselves -
// so usually there is nothing to do here.
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = '[some content type]';

// Completely override definitions of active indexers to be used like this:
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'] =
//	array('core.tt_content', [further content types]);

// In order to explicitely disable one or more indexer do:
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'] =
//	array_diff(
//		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'],
//		array([content types to ignore])
//	);

// Indexing of content types is handled by different indexer services
// which are configured each with its own options.
// These options configure some indexer's special functionality,
// e.g. limiting the indexed data by certain criteria.
// The options expected by the indexer should ;-) be documented
// in the phpdoc section of the respective indexer class.
//
// Basic indexer services configuration schema:
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config'][$contentType] = array($options);
// where $contentType is the string representing the content type (like above)
// in the form "$extKey.$contentTypeName" => this is actually the indexer service's subtype.
// Example: Configure the indexer "core.tt_content" to limit data to be indexed
// to records where the sys_language_uid is "1":
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.tt_content']['lang'] = 1;
