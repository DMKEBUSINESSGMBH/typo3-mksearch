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

require_once(t3lib_extMgm::extPath('mksearch').'lib/Apache/Solr/Service.php' );

/**
 * Miscellaneous methods
 */
class tx_mksearch_util_Misc {
	/**
	 * Convert a Timestamp to a ISO formatted DateTime string in GMT time zone
	 * @param int $tstamp
	 * @param int $offset
	 */
	public static function getISODateFromTimestamp($tstamp, $offset=0) {
		$dt = new DateTime('@'.($tstamp+$offset));
		return $dt->format('Y-m-d\TH:i:s\Z');
	}

	/**
	 * Allgemeine Include/exclude-Prüfung. Derzeit auf PID-Ebene
	 *
	 * @param array 	$sourceRecord
	 * @param array 	$options
	 * @return boolean
	 *
	 * @deprecated unbedingt tx_mksearch_util_Indexer::getInstance()->isOnIndexablePage nutzen.
	 * das unterstützt sowohl pages als auch pageTrees.
	 */
	public static function isOnValidPage($sourceRecord, $options){
		if(!is_array($options)) { return true; }
		if(array_key_exists('include.', $options) && is_array($options['include.'])) {
			$aPages = (array_key_exists('pages', $options['include.']) && strlen(trim($options['include.']['pages'])))
						? t3lib_div::intExplode(',', $options['include.']['pages']) : false;
			if(!is_array($aPages))
				$aPages = $options['include.']['pages.'];

			if(is_array($aPages) && count($aPages)) {
				// Wenn das Element auf einer dieser Seiten eingebunden ist, indizieren!
				return in_array($sourceRecord['pid'],$aPages);
			}
		}
		if(array_key_exists('exclude.', $options) && is_array($options['exclude.'])) {
			$aPages = (array_key_exists('pages', $options['exclude.']) && strlen(trim($options['exclude.']['pages'])))
						? t3lib_div::intExplode(',', $options['exclude.']['pages']) : false;
			if(!is_array($aPages))
				$aPages = $options['exclude.']['pages.'];
			if(is_array($aPages) && count($aPages)) {
				// Wenn das Element auf einer dieser Seiten eingebunden ist,
				// NICHT indizieren!
				return !in_array($sourceRecord['pid'],$aPages);
			}
		}
		return true;
	}

	/**
	 * Liefert einen UTF8 codierten String
	 *
	 * @TODO: wäre in der tx_rnbase_util_Strings besser aufgehoben?
	 *
	 * @param 	mixed 	$t
	 * @return 	mixed
	 */
	public static function utf8Encode($mixed) {
		// Nur prüfen, wenn es sich um einen String handelt.
		if(!is_string($mixed)) return $mixed;
		// String prüfen und ggf. encodieren
		tx_rnbase::load('tx_rnbase_util_Strings');
		return tx_rnbase_util_Strings::isUtf8String($mixed) ? $mixed : utf8_encode($mixed);
	}

	/**
	 * Convert HTML to plain text
	 *
	 * Removes HTML tags and HTML comments and converts HTML entities
	 * to their applicable characters.
	 *
	 * @param string	$t
	 * @return string	Converted string (utf8-encoded)
	 */
	static function html2plain($text) {
		if(!is_string($text)) return $text;

		return trim(html_entity_decode(
			preg_replace(
				array('/(\s+|(<.*?>)+)/', '/<!--.*?-->/'),
				array(' ', ''),
				$text
			),
			ENT_QUOTES,
			'UTF-8'
		));
	}

	/**
	 * Sanitizes the given term and removes all unwanted
	 * chars. These are for example some for the solr
	 * search syntax.
	 * @param string $sTerm
	 * @return string
	 * @todo sollte hier nicht Apache_Solr_Service::escape() genutzt werden!?
	 * siehe auch die escape Methode aus der Apache Solr TYPO3 Extension wie Phrasen
	 * unterstützt werden könnten? Sollte aber alles nur fir nicht dismax interessant sein.
	 */
	public static function sanitizeTerm($sTerm) {
		//wir brauchen 3 backslashes (\\\) um einen einfachen zu entwerten.
		//der erste entwertet den zweiten für die hochkommas. der zweite
		//entwertet den dritten für regex.
		//ansonsten sind das alle Zeichen, die in Solr nicht auftauchen
		//dürfen da sie zur such-syntax gehören
		//genau dürfen nicht auftauchen: + - & | ! ( ) { } [ ] ^ " ~ * ? : \
		//außerdem nehmen wir folgende raus um die Suche zu verfeinern:
		//, . / # ' % < >
		//eigentlich sollte dies aber ebenfalls durch Solr filter realisiert
		//werden
		return preg_replace('/[%,.&*+-\/\'!?#()\[\]\{\}"^|<>:\\\~]+/', '', $sTerm);
	}

	/**
	 * IP-based Access restrictions
	 *
	 * @param 	string 		$remoteAddress
	 * @param 	string 		$devIPmask
	 * @return 	boolean
	 */
	public static function isDevIpMask($remoteAddress='',$devIPmask=''){
		$devIPmask = trim(strcmp($devIPmask, '') ? $devIPmask : $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
		$remoteAddress = trim(strcmp($remoteAddress, '') ? $remoteAddress : t3lib_div::getIndpEnv('REMOTE_ADDR'));
		return t3lib_div::cmpIP($remoteAddress, $devIPmask);
	}



	/**
	 * Parse the configuration of the given models
	 * @param string $sTs
	 */
	public static function parseTsConfig($sTs) {
		/* @var $TSparserObject t3lib_tsparser */
		$TSparserObject = t3lib_div::makeInstance('t3lib_tsparser');
		$TSparserObject->parse($sTs);
		return $TSparserObject->setup;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Misc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Misc.php']);
}