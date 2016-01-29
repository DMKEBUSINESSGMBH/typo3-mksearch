<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
 */


/**
 * Die Klasse stellt Auswahlmenus zur Verfügung
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Selector {

	/**
	 * @var 	tx_rnbase_mod_IModule
	 */
	private $mod;
	/**
	 * @var 	tx_rnbase_util_FormTool
	 */
	private $formTool;

	/**
	 * Initialisiert das Objekt mit dem Template und der Modul-Config.
	 */
	public function init(tx_rnbase_mod_IModule $module){
		$this->mod = $module;
		$this->formTool = $this->mod->getFormTool();
	}

	/**
	 * Method to display a form with an input array, a description and a submit button.
	 * Keys are 'field' and 'button'.
	 *
	 * @param 	string 	$out 				HTML string
	 * @param 	string 	$key 				mod key
	 * @param 	array 	$options
	 * 				string 	buttonName 		name of the submit button. default is key.
	 * 				string 	buttonValue 	value of the sumbit button. default is LLL:label_button_search.
	 * 				string 	label 			label of the sumbit button. default is LLL:label_search.
	 * @return 	string 						search term
	 */
	public function showFreeTextSearchForm (&$out, $key, array $options = array()) {

		$searchstring = $this->getValueFromModuleData($key);

		// Erst das Suchfeld, danach der Button.
		$out['field'] 	= $this->formTool->createTxtInput('SET['.$key.']',$searchstring,10);
		$out['button'] 	= empty($options['submit']) ? '' : $this->formTool->createSubmit(
					$options['buttonName'] ? $options['buttonName'] : $key,
					$options['buttonValue'] ? $options['buttonValue'] : $GLOBALS['LANG']->getLL('label_button_search')
				);
		$out['label'] = $options['label'] ? $options['label'] : $GLOBALS['LANG']->getLL('label_search');
		return $searchstring;
	}

	/**
	 * Returns a delete select box. All data is stored in array $data.
	 * @param 	array 	$data
	 * @param 	array 	$options
	 * @return 	boolean
	 */
	public function showHiddenSelector(&$data, $options=array()) {
		$items = array(
			0 => $GLOBALS['LANG']->getLL('label_select_hide_hidden'),
			1 => $GLOBALS['LANG']->getLL('label_select_show_hidden'),
		);

		$options['label'] = $options['label'] ? $options['label'] : $GLOBALS['LANG']->getLL('label_hidden');
		return $this->showSelectorByArray($items,'showhidden',$data, $options);
	}
	/**
	 * Zeigt eine Datumsauswahl mit einzelnen Selects für Tag, Monat und Jahr.
	 * @param array $aItems Array mit den werten der Auswahlbox
	 * @param string $sDefId ID-String des Elements
	 * @param array $aData enthält die Formularelement für die Ausgabe im Screen. Keys: selector, label
	 * @param array $aOptions zusätzliche Optionen: yearfrom, yearto,
	 * @return DateTime selected day
	 */
	public function showDateSelector($sDefId, &$aData, $aOptions=array()) {
		$baseId = isset($aOptions['id']) && $aOptions['id'] ? $aOptions['id'] : $sDefId;
		// Da es drei Felder gibt, benötigen wir drei IDs
		$dayId = $baseId.'_day';
		$monthId = $baseId.'_month';
		$yearId = $baseId.'_year';

		// Defaultwerte werden benötigt, wenn noch keine Eingabe erfolgte
		$aDefault = explode('-', $aOptions['default']);

		if(isset($aOptions['id']))
			unset($aOptions['id']);
		// Monate
		$tmpDataMonth = array();
		$items = array();
		for($i=1; $i<13; $i++) {
			$date = new DateTime();
			$items[$i] = $date->setDate(2000, $i, 1)->format('F');
		}
		$selectedMonth = $this->getValueFromModuleData($monthId);
		$selectedMonth = $this->showSelectorByArray($items, $monthId, $tmpDataMonth, array('forcevalue'=>($selectedMonth) ? $selectedMonth : $aDefault[1]));

		// Jahre
		$today = new DateTime();
		$from = intval($aOptions['yearfrom']);
		if(!$from) {
			// Default 10 Jahre. Damit wir PHP 5.2. verwenden können, die Berechnung etwas umständlich.
			$from = intval($today->format('Y')) - 10;
		}
		$to = intval($aOptions['yearto']);
		if(!$to) {
			$to = intval($today->format('Y'));
		}

		$tmpDataYear = array();
		$items = array();
		for($i=$from; $i<$to; $i++) {
			$items[$i] = $i;
		}
		$selectedYear = $this->getValueFromModuleData($yearId);
		$selectedYear = $this->showSelectorByArray($items, $yearId, $tmpDataYear, array('forcevalue'=>($selectedYear) ? $selectedYear : $aDefault[0]));

		// Tage
		$tmpDataDay = array();
		$items = array();
		$totalDays = date('t',mktime(0,0,0,$selectedMonth,1,$selectedYear));
		for($i=1; $i<$totalDays+1; $i++) $items[$i] = $i;
		$selectedDay = $this->getValueFromModuleData($dayId);
		$selectedDay = ($selectedDay>$totalDays) ? $totalDays : $selectedDay;
		$selectedDay = $this->showSelectorByArray($items, $dayId, $tmpDataDay, array('forcevalue'=>($selectedDay) ? $selectedDay : $aDefault[2]));

		// Rückgabe
		$aData['day_selector'] = $tmpDataDay['selector'];
		$aData['month_selector'] = $tmpDataMonth['selector'];
		$aData['year_selector'] = $tmpDataYear['selector'];

		$ret = new DateTime();
		$ret->setDate($selectedYear, $selectedMonth, $selectedDay);

		return $ret;
	}

	/**
	 * Gibt einen selector mit den elementen im gegebenen array zurück
	 * @param array $aItems Array mit den werten der Auswahlbox
	 * @param string $sDefId ID-String des Elements
	 * @param array $aData enthält die Formularelement für die Ausgabe im Screen. Keys: selector, label
	 * @param array $aOptions zusätzliche Optionen: label, id
	 * @return string selected item
	 */
	protected function showSelectorByArray($aItems, $sDefId, &$aData, $aOptions=array()) {
		$id = isset($aOptions['id']) && $aOptions['id'] ? $aOptions['id'] : $sDefId;

		$selectedItem = array_key_exists('forcevalue', $aOptions) ? $aOptions['forcevalue'] : $this->getValueFromModuleData($id);

		// Build select box items
		$aData['selector'] = Tx_Rnbase_Backend_Utility::getFuncMenu(
			$this->mod->getPid(), 'SET['.$id.']', $selectedItem, $aItems
		);

		//label
		$aData['label'] = $aOptions['label'];

		// as the deleted fe users have always to be hidden the function returns always false
		//@todo wozu die alte abfrage? return $defId==$id ? false : $selectedItem;
		return $selectedItem;
	}
	/**
	 * Gibt einen selector mit den elementen im gegebenen array zurück
	 * @return string selected item
	 */
	protected function showSelectorByTCA($sDefId, $table, $column, &$aData, $aOptions=array()) {
		$items = array();
		if(is_array($aOptions['additionalItems'])) {
			$items = $aOptions['additionalItems'];
		}
		if(is_array($GLOBALS['TCA'][$table]['columns'][$column]['config']['items']))
			foreach($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'] As $item){
				$items[$item[1]] = $GLOBALS['LANG']->sL($item[0]);
			}
		return $this->showSelectorByArray($items, $sDefId, $aData, $aOptions);
	}

	/**
	 * Returns an instance of tx_rnbase_mod_IModule
	 *
	 * @return 	tx_rnbase_mod_IModule
	 */
	protected function getModule() {
		return $this->mod;
	}
	/**
	 *
	 * @return tx_rnbase_util_FormTool
	 */
	protected function getFormTool() {
		return $this->getModule()->getFormTool();
	}

	/**
	 * Return requested value from module data
	 *
	 * @param 	string $key
	 * @return 	mixed
	 */
	public function getValueFromModuleData($key) {
		// Fetch selected company trade
		$modData = Tx_Rnbase_Backend_Utility::getModuleData(array ($key => ''),tx_rnbase_parameters::getPostOrGetParameter('SET'),$this->getModule()->getName());
		if (isset($modData[$key])) return $modData[$key];
		// else
		return null;
	}

	/**
	 * Setzt einen Wert in den Modul Daten. Dabei werden die bestehenden
	 * ergänzt oder ggf. überschrieben
	 *
	 * @param 	array $aModuleData
	 * @return 	void
	 */
	public function setValueToModuleData($sModuleName, $aModuleData = array()) {
		$aExistingModuleData = $GLOBALS['BE_USER']->getModuleData($sModuleName);
		if(!empty($aModuleData))
			foreach ($aModuleData as $sKey => $mValue)
				$aExistingModuleData[$sKey] = $mValue;
		$GLOBALS['BE_USER']->pushModuleData($sModuleName,$aExistingModuleData);
	}


	/**
	 *
	 * @param 	array 	$data
	 * @return 	string
	 */
	public function buildFilterTable(array $data){
		$out = '';
		if(count($data)){
			$out .= '<table class="filters">';
			foreach($data as $label => $filter){
				$out .= '<tr>';
				$out .= '<td>'. (isset($filter['label']) ? $filter['label'] : $label).'</td>';
				unset($filter['label']);
				$out .= '<td>'. implode(' ', $filter) .'</td>';

				$out .= '</tr>';
			}
			$out .= '</table>';
		}
		return $out;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Selector.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Selector.php']);
}
