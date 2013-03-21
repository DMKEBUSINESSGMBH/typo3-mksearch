<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat
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


/**
 * Tika controller class
 */
class tx_mksearch_util_Tika {
	private static $instance = null;
	private $tikaJar = null;
	private $tikaAvailable = -1;
	private $tikaLocaleType;

	/**
	 * @return tx_mksearch_util_Tika
	 */
	public static function getInstance() {
		$tikaJar = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'tikaJar');
		self::$instance = new tx_mksearch_util_Tika($tikaJar);
		return self::$instance;
	}
	private function __construct($tikaJar) {
		$this->setTikaJar($tikaJar);
		$this->tikaLocaleType = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'tikaLocaleType');
	}
	/**
	 * Whether or not Tika is available on server.
	 * @return boolean
	 */
	public function isAvailable() {
		if($this->tikaAvailable != -1) {
			return $this->tikaAvailable == 1 ? true : false;
		}
		if (!is_file($this->tikaJar)) {
			tx_rnbase_util_Logger::warn('Tika Jar not found!', 'mksearch', array('Jar'=> $this->tikaJar));
			$this->tikaAvailable = 0;
			return $this->tikaAvailable;
		}
		
		if (!t3lib_exec::checkCommand('java')) {
			tx_rnbase_util_Logger::warn('Java not found! Java is required to run Apache Tika.', 'mksearch');
			$this->tikaAvailable = 0;
			return $this->tikaAvailable;
		}
		$this->tikaAvailable = 1;
		return $this->tikaAvailable;
	}
	private function setTikaJar($tikaJar) {
		$this->tikaJar = $tikaJar;
	}
	
	/**
	 * Umlaute in Dateinamen werden durch escapeshellarg entfernt
	 * außer es ist der korrekte LC_CTYPE gesetzt. Sollte auf de_DE.UTF-8
	 * stehen 
	 * 
	 * @see http://www.php.net/manual/de/function.escapeshellarg.php#99213
	 * 
	 * @return void
	 */
	private function setLocaleType() {
		if($this->tikaLocaleType){
			setlocale(LC_CTYPE, $this->tikaLocaleType);
		}
	}
	
	/**
	 * @return void
	 */
	private function resetLocaleType() {
		setlocale(LC_CTYPE, locale_get_default());
	}

	/**
	 * Extracs text from a file using Apache Tika
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return string
	 * @throws Exception
	 */
	public function extractContent($file) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		$ret = '';
		$absFile = self::checkFile($file);
		
		$this->setLocaleType();
		
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->tikaJar)
			. ' -t ' . escapeshellarg($absFile);
			
		$this->resetLocaleType();
		
		$ret = shell_exec($tikaCommand);
		return $ret;
	}

	/**
	 * Extracs text from a file using Apache Tika
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return string
	 * @throws Exception
	 */
	public function extractLanguage($file) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		$ret = '';
		$absFile = self::checkFile($file);
		
		$this->setLocaleType();
		
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->tikaJar)
			. ' -l ' . escapeshellarg($absFile);
			
		$this->resetLocaleType();
		
		$ret = shell_exec($tikaCommand);
		return trim($ret);
	}

	/**
	 * Extracs meta data from a file using Apache Tika
	 *
	 * @param	string file path.
	 * @return array
	 * @throws Exception
	 */
	public function extractMetaData($file) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		$absFile = self::checkFile($file);
		
		$this->setLocaleType();
		
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->tikaJar)
			. ' -m ' . escapeshellarg($absFile);
			
		$this->resetLocaleType();

		$shellOutput = array();
		exec($tikaCommand, $shellOutput);

		$ret = array();
		foreach ($shellOutput as $line) {
			list($meta, $value) = explode(':', $line, 2);
			$ret[$meta] = trim($value);
		}
		return $ret;
	}

	/**
	 * Check if a file exists and is readable within TYPO3.
	 * @param	string File name
	 * @return string File name with absolute path or FALSE.
	 * @throws Exception
	 */
	private static function checkFile ($fName)	{
		$absFile = t3lib_div::getFileAbsFileName($fName);
		if(!(t3lib_div::isAllowedAbsPath($absFile) && @is_file($absFile))) {
			throw new Exception('File not found: '.$absFile);
		}
		if(!@is_readable($absFile)) {
			throw new Exception('File is not readable: '.$absFile);
		}
		return $absFile;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tika.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tika.php']);
}