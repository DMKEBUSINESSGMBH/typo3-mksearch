<?php
/**
 * 	@package tx_mkkvbb
 *  @subpackage tx_mkkvbb_tests_action
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('Tx_Phpunit_TestCase');
tx_rnbase::load('tx_mksearch_action_SearchSolr');
tx_rnbase::load('Tx_Phpunit_Framework');

/**
 * Testfälle für tx_mkkvbb_action_Substitution
 *
 * @author hbochmann
 * @package tx_mkkvbb
 * @subpackage tx_mkkvbb_tests_action
 */
class tx_mksearch_tests_action_SearchSolr_testcase extends Tx_Phpunit_TestCase {
	
	/**
	 * unsere link id. entweder die id oder der alias wenn gesetzt
	 * @var string or integer
	 */
	protected $linkId  = '';
	
	public function setUp() {
		// logoff für phpmyadmin deaktivieren. ist nicht immer notwendig
		// aber sollte auch nicht stören!
		/*
		 * Error in test case test_handleRequest aus mkforms
		 * in file C:\xampp\htdocs\typo3\typo3conf\ext\phpmyadmin\res\class.tx_phpmyadmin_utilities.php
		 * on line 66:
		 * Message:
		 * Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\typo3\typo3conf\ext\phpunit\mod1\class.tx_phpunit_module1.php:112)
		 *
		 * Diese Fehler passiert, wenn die usersession ausgelesen wird. der feuser hat natürlich keine.
		 * Das Ganze passiert in der t3lib_userauth->fetchUserSession.
		 * Dort wird t3lib_userauth->logoff aufgerufen, da keine session vorhanden ist.
		 * phpmyadmin klingt sich da ein und schreibt daten in die session.
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'])){
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $k=>$v){
				if($v = 'tx_phpmyadmin_utilities->pmaLogOff'){
					unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$k]);
				}
			}
		}
		
		$framework = new Tx_Phpunit_Framework('dummy');
		$framework->createFakeFrontEnd();
		
		//reset to see if filled correct
		$GLOBALS['TSFE']->additionalHeaderData = null;
		
		//damit links generiert werden können
		$GLOBALS['TSFE']->sys_page = tx_rnbase_util_TYPO3::getSysPage();
		
		//wir brauchen noch die id/alias für den link, der genriert wird
		$this->linkId = $GLOBALS['TSFE']->page['alias'] ? 
						$GLOBALS['TSFE']->page['alias'] : 
						$GLOBALS['TSFE']->page['uid'];
	}
	
	/**
	 * @param unknown_type $aParams
	 */
	private function getAction($aMockFunctions = array(),$aConfig = array(), &$out = '', $aParams = array()) {
		//build mock
		$action = $this->getMock('tx_mksearch_action_SearchSolr',array_keys($aMockFunctions));
		
		foreach ($aMockFunctions as $sMockFunction => $aMockFunctionConfig) {
			$action->expects($aMockFunctionConfig['expects'])
			->method($sMockFunction)
			->will($this->returnValue($aMockFunctionConfig['returnValue']));
		}
		
		//configure action
		$configurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		
		//@TODO: warum wird die klasse tslib_cObj nicht gefunden!? (mw: eternit local)
		require_once(t3lib_extMgm::extPath('cms', 'tslib/class.tslib_content.php'));
		$configurations->init(
				$aConfig,
				$configurations->getCObj(1),
				'mksearch','mksearch'
			);
			
		//noch extra params?
		if(!empty($aParams))
			foreach ($aParams as $sName => $mValue)
				$parameters->offsetSet($sName,$mValue);
			
		$configurations->setParameters($parameters);
		$action->setConfigurations($configurations);
		
		$out = $action->handleRequest($parameters, $configurations, $configurations->getViewData());
			
		return $action;
	}

	/**
	 */
	public function testHandleRequestWithDisabledAutocomplete(){
		//action initialisieren
		$aConfig['searchsolr.'] = array(
			'nosearch' => 1,//keine echte Suche
			'autocomplete.' => array(
				'enable' => 0
			)
		);
		//mock getIndex() so its not called for real
		$aMockFunctions = array(
			'getIndex' => array(
				'expects' => $this->never(),
				'returnValue' => true,
			)
		);
		$out = true;
		$action = $this->getAction($aMockFunctions,$aConfig,$out);
		
		$this->assertNull($out,'es wurde nicht null geliefert. vllt doch gesucht?');
		//view daten sollten nicht gesetzt sein
		$this->assertFalse($action->getConfigurations()->getViewData()->offsetExists('result'),'es wurde doch ein result gesetzt in den view daten. doch gesucht?');
		
		$this->assertEmpty($GLOBALS['TSFE']->additionalHeaderData,'Daten für JS doch gesetzt?');
	}
	
	/**
	 */
	public function testHandleRequestWithEnabledAutocomplete(){
		//action initialisieren
		$aConfig['searchsolr.'] = array(
			'nosearch' => 1,//keine echte Suche
			'autocomplete.' => array(
				'enable' => 1,
				'minLength' => 2,
				'elementSelector' => 'myElementSelector',
				'actionLink.' => array(
					'useKeepVars' => 1,
					'useKeepVars.' => array(
						'add' => '::type=540'
					),
					'noHash' => 1,
				)
			)
		);
		//mock getIndex() so its not called for real
		$aMockFunctions = array(
			'getIndex' => array(
				'expects' => $this->never(),
				'returnValue' => true,
			)
		);
		$out = true;
		$action = $this->getAction($aMockFunctions,$aConfig,$out);
		
		$this->assertNull($out,'es wurde nicht null geliefert. vllt doch gesucht?');
		//view daten sollten nicht gesetzt sein
		$this->assertFalse($action->getConfigurations()->getViewData()->offsetExists('result'),'es wurde doch ein result gesetzt in den view daten. doch gesucht?');
		
		$sJs = '
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(myElementSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "?id='.$this->linkId.'&type=540&mksearch%5Bajax%5D=1&mksearch%5BusedIndex%5D=0&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.uid);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 2
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();
		</script>
		';
		$this->assertEquals(1, count($GLOBALS['TSFE']->additionalHeaderData),'mehr header daten als erwartet!');
		$this->assertEquals($sJs,$GLOBALS['TSFE']->additionalHeaderData[md5($sJs)],'Daten für JS falsch');
	}
	
	/**
	 */
	public function testHandleRequestWithEnabledAutocompleteAndInclusionOfSomeJqueryLibs(){
		//action initialisieren
		$aConfig['searchsolr.'] = array(
			'nosearch' => 1,//keine echte Suche
			'autocomplete.' => array(
				'enable' => 1,
				'minLength' => 2,
				'elementSelector' => 'myElementSelector',
				'actionLink.' => array(
					'useKeepVars' => 1,
					'useKeepVars.' => array(
						'add' => '::type=540'
					),
					'noHash' => 1,
				),
				'includeJquery' => 1,
				'includeJqueryUiCore' => 1,
				'includeJqueryUiAutocomplete' => 0,
			),
		);
		//mock getIndex() so its not called for real
		$aMockFunctions = array(
			'getIndex' => array(
				'expects' => $this->never(),
				'returnValue' => true,
			)
		);
		$out = true;
		$action = $this->getAction($aMockFunctions,$aConfig,$out);
		
		$this->assertNull($out,'es wurde nicht null geliefert. vllt doch gesucht?');
		//view daten sollten nicht gesetzt sein
		$this->assertFalse($action->getConfigurations()->getViewData()->offsetExists('result'),'es wurde doch ein result gesetzt in den view daten. doch gesucht?');
		
		$sJs = '
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(myElementSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "?id='.$this->linkId.'&type=540&mksearch%5Bajax%5D=1&mksearch%5BusedIndex%5D=0&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.uid);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 2
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();
		</script>
		';
		
		$this->assertEquals(3, count($GLOBALS['TSFE']->additionalHeaderData),'mehr header daten als erwartet!');
		$this->assertEquals($sJs,$GLOBALS['TSFE']->additionalHeaderData[md5($sJs)],'Daten für JS falsch');
		$this->assertEquals('<script type="text/javascript" src="typo3conf/ext/mksearch/res/js/jquery-1.6.2.min.js"></script>',$GLOBALS['TSFE']->additionalHeaderData['jquery-1.6.2.min.js'],'Daten für JS jquery falsch');
		$this->assertEquals('<script type="text/javascript" src="typo3conf/ext/mksearch/res/js/jquery-ui-1.8.15.core.min.js"></script>',$GLOBALS['TSFE']->additionalHeaderData['jquery-ui-1.8.15.core.min.js'],'Daten für JS jquery ui falsch');
	}
	
	/**
	 */
	public function testHandleRequestWithEnabledAutocompleteAndInclusionOfAllJqueryLibs(){
		//action initialisieren
		$aConfig['searchsolr.'] = array(
			'nosearch' => 1,//keine echte Suche
			'autocomplete.' => array(
				'enable' => 1,
				'minLength' => 2,
				'elementSelector' => 'myElementSelector',
				'actionLink.' => array(
					'useKeepVars' => 1,
					'useKeepVars.' => array(
						'add' => '::type=540'
					),
					'noHash' => 1,
				),
				'includeJquery' => 1,
				'includeJqueryUiCore' => 1,
				'includeJqueryUiAutocomplete' => 1,
			)
		);
		//mock getIndex() so its not called for real
		$aMockFunctions = array(
			'getIndex' => array(
				'expects' => $this->never(),
				'returnValue' => true,
			)
		);
		$out = true;
		$action = $this->getAction($aMockFunctions,$aConfig,$out);
		
		$this->assertNull($out,'es wurde nicht null geliefert. vllt doch gesucht?');
		//view daten sollten nicht gesetzt sein
		$this->assertFalse($action->getConfigurations()->getViewData()->offsetExists('result'),'es wurde doch ein result gesetzt in den view daten. doch gesucht?');
		
		$sJs = '
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(myElementSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "?id='.$this->linkId.'&type=540&mksearch%5Bajax%5D=1&mksearch%5BusedIndex%5D=0&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.uid);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 2
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();
		</script>
		';
		
		$this->assertEquals(4, count($GLOBALS['TSFE']->additionalHeaderData),'mehr header daten als erwartet!');
		$this->assertEquals($sJs,$GLOBALS['TSFE']->additionalHeaderData[md5($sJs)],'Daten für JS falsch');
		$this->assertEquals('<script type="text/javascript" src="typo3conf/ext/mksearch/res/js/jquery-1.6.2.min.js"></script>',$GLOBALS['TSFE']->additionalHeaderData['jquery-1.6.2.min.js'],'Daten für JS jquery falsch');
		$this->assertEquals('<script type="text/javascript" src="typo3conf/ext/mksearch/res/js/jquery-ui-1.8.15.core.min.js"></script>',$GLOBALS['TSFE']->additionalHeaderData['jquery-ui-1.8.15.core.min.js'],'Daten für JS jquery ui falsch');
		$this->assertEquals('<script type="text/javascript" src="typo3conf/ext/mksearch/res/js/jquery-ui-1.8.15.autocomplete.min.js"></script>',$GLOBALS['TSFE']->additionalHeaderData['jquery-ui-1.8.15.autocomplete.min.js'],'Daten für JS jquery ui falsch');
	}
	
	/**
	 */
	public function testHandleRequestWithEnabledAutocompleteAndConfiguredUsedIndex(){
		//action initialisieren
		$aConfig['searchsolr.'] = array(
			'nosearch' => 1,//keine echte Suche
			'autocomplete.' => array(
				'enable' => 1,
				'minLength' => 2,
				'elementSelector' => 'myElementSelector',
				'actionLink.' => array(
					'useKeepVars' => 1,
					'useKeepVars.' => array(
						'add' => '::type=540'
					),
					'noHash' => 1,
				)
			),
			'usedIndex' => 2
		);
		//mock getIndex() so its not called for real
		$aMockFunctions = array(
			'getIndex' => array(
				'expects' => $this->never(),
				'returnValue' => true,
			)
		);
		$out = true;
		$action = $this->getAction($aMockFunctions,$aConfig,$out);
		
		$this->assertNull($out,'es wurde nicht null geliefert. vllt doch gesucht?');
		//view daten sollten nicht gesetzt sein
		$this->assertFalse($action->getConfigurations()->getViewData()->offsetExists('result'),'es wurde doch ein result gesetzt in den view daten. doch gesucht?');
		
		$sJs = '
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(myElementSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "?id='.$this->linkId.'&type=540&mksearch%5Bajax%5D=1&mksearch%5BusedIndex%5D=2&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.uid);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 2
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();
		</script>
		';
		$this->assertEquals(1, count($GLOBALS['TSFE']->additionalHeaderData),'mehr header daten als erwartet!');
		$this->assertEquals($sJs,$GLOBALS['TSFE']->additionalHeaderData[md5($sJs)],'Daten für JS falsch');
	}
}