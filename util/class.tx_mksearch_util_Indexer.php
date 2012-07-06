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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

/**
 */
class tx_mksearch_util_Indexer {
	
	/**
	 * Liefert eine Datumsfeld für Solr
	 * @param 	string 	$date | insert "@" before timestamps
	 * @param 	string 	$type date or time
	 * @return string
	 */
	public static function getDateTime($date,$type='time'){
		$dateTime = tx_rnbase::makeInstance('DateTime', $date, tx_rnbase::makeInstance('DateTimeZone', 'GMT-0'));
		$dateTime->setTimeZone(tx_rnbase::makeInstance('DateTimeZone', 'UTC'));

		return $dateTime->format('Y-m-d\TH:i:s\Z');
	}
}