<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 das Medienkombinat
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

tx_rnbase::load('tx_rnbase_util_Logger');

// Include basis cli class
require_once(PATH_t3lib.'class.t3lib_cli.php');

class tx_mksearch_cli_crawler extends t3lib_cli {
    
	private $_cliKey = 'mksearch';
	
	/**
	 * Constructor
	 */
	public function __construct () {
		// Running parent class constructor
		parent::t3lib_cli();

		$arg0 = (string)$this->cli_args['_DEFAULT'][0];

		// Setting help texts:
		$this->cli_help['name'] = 'Website crawling and indexing';
		$this->cli_help['synopsis'] = $arg0. ' ' . $this->_cliKey . ' TASK INDEXNAME ###OPTIONS###';	// Options get listed here automatically
		$this->cli_help['description'] = 'Crawls the website and indexes its contents according to the configuration made by the respective indexer providing extensions and the given configuration file.

For an example configuration have a look at ext:mksearch/cli/configuration.php.';
		$this->cli_help['examples'] = $arg0. ' ' . $this->_cliKey . " build index\n" .
					$arg0. ' ' . $this->_cliKey . " build index -s\n" .
					$arg0. ' ' . $this->_cliKey . " build index -c conf1.php conf2.php\n" .
					$arg0. ' ' . $this->_cliKey . " renew techindex -ss\n";

		$this->cli_help['author'] = '(c) 2009-2011 das Medienkombinat';

		// Merge tasks into cli_help array on desired position...
		$tasks = 
'build	Create or update index, if still one exists
renew	Renew complete index, i. e. delete old index and re-create it';
//        $this->cli_help['tasks'] = $tasks;
		$this->cli_help = array_merge(
				array_slice($this->cli_help, 0, 4),
				array('tasks' => $tasks),
				array_slice($this->cli_help, 4)
		);

		$this->cli_options[] = array('-c','Path to one or more configuration files');
		$this->cli_options[] = array('-o','Optimize index after creation. BEWARE: May take a long time!');
		$this->cli_options[] = array('-dd','Debug mode: Displays index data. BEWARE: Will create MUCH output!');
		$this->cli_options[] = array('-dp','Debug mode: Enables PHP error level E_ALL. BEWARE: Will create HUGE output!');
		$this->cli_options[] = array('-h','Show this help');
	}

	private function exitWithError($error = 'General Error', $showHelp=true) {
		$this->cli_echo("\nERROR:\t$error\n\n");
		if ($showHelp) 
			$this->cli_help();
		exit(1);
	}

	/**
	 * CLI engine
	 *
	 * @param    array        Command line arguments
	 * @return    string
	 */
	public function cli_main($argv) {

		if ($this->cli_isArg('-h')) {
			$this->cli_help(); exit;
		} 

		$task = 'update';
		// get task (function)
//		$task = (string)$this->cli_args['_DEFAULT'][1];
//		if (!$task) 
//			$this->exitWithError("No task given.", true);
		// FIXME: Das kann nicht mehr so funktionieren. Hier kann höchstens die UID eines konfigurierten
		// Index aus der DB übergeben werden!
		// Anschließend den Index laden und darüber die Engine ermitteln
//		$indexName = (string)$this->cli_args['_DEFAULT'][2];
//		if (!$indexName) 
//			$this->exitWithError("No index given.", true);

		// Explicitely switch errors on / off
		if ($this->cli_isArg('-dp')) {
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
		} else {
			ini_set('display_errors', '0');
		}

		$this->cli_echo("Start processing of indexing queue.\n", true);
		
		// Read configuration file
		if ($this->cli_isArg('-c')) {
			foreach ($this->cli_args['-c'] as $filename) {
				$this->cli_echo("Reading configuration file \"$filename\" ...");
				if (file_exists($filename))
					require_once($filename);
				else {
					$this->cli_echo("\n");
					$this->exitWithError("Could not read configuration file!", false);
				}
				$this->cli_echo(" done.\n");
			} 
		}
		
		switch($task) {
			case 'update':
				$status = $this->updateQueue();
				break;
//			case 'renew':
//				$status = $this->crawl($indexName, true, $this->cli_isArg('-o'), $this->cli_isArg('-dd'));
//				break;
//			case 'build':	
//				$status = $this->crawl($indexName, false, $this->cli_isArg('-o'), $this->cli_isArg('-dd'));
//				break;
			default:		
				$this->exitWithError('Unknown task.', true);
		}
		if (isset($this->cli_args['-ss'])) 
			exit($status);
	}
	private function updateQueue() {
		try {
			$rows = tx_mksearch_util_ServiceRegistry::getIntIndexService()->triggerQueueIndexing(100);
			$rows = count(call_user_func_array('array_merge', array_values($rows)));
			$msg = sprintf(($rows ? '[CLI mksearch] %d item(s) indexed' : '[CLI mksearch] No items in indexing queue.') , $rows);
			$this->cli_echo($msg);
			tx_rnbase_util_Logger::info($msg, 'mksearch');
		} catch (Exception $e) {
			$msg = '[CLI mksearch] Indexing failed!';
			$this->cli_echo($msg);
			tx_rnbase_util_Logger::fatal($msg, 'mksearch', array('Exception' => $e->getMessage()));
			$success = false;
		}
	}
}

// Call the functionality
$cleanerObj = t3lib_div::makeInstance('tx_mksearch_cli_crawler');
$cleanerObj->cli_main($_SERVER['argv']);