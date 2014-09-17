<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 DMK E-Business GmbH
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_model_IndexerDocumentBase');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_model_IndexerDocumentBase_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * @group unit
	 */
	public function testSetAbstractRemovesHtml() {
		$indexerDocument = $this->getIndexerDocument();

		$abstract = '<p>test</p>';
		$indexerDocument->setAbstract($abstract);
		$data = $indexerDocument->getData();

		$this->assertEquals('test', $data['abstract']->getValue(), 'html not removed');
	}

	/**
	 * @group unit
	 */
	public function testSetAbstractUsesGivenLengthEvenIfMaxAbstractLengthIsSet() {
		$indexerDocument = $this->getIndexerDocument();

		$abstract = 'test';
		$indexerDocument->setAbstract($abstract, 3);
		$data = $indexerDocument->getData();

		$this->assertEquals('tes', $data['abstract']->getValue(), 'abstract not shortened');
	}

	/**
	 * @group unit
	 */
	public function testSetAbstractUsesMaxAbstractLengthIfNoLengthGiven() {
		$indexerDocument = $this->getIndexerDocument();

		$abstract = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores sasd';
		$indexerDocument->setAbstract($abstract);
		$data = $indexerDocument->getData();

		$expectedShortenedAbstract = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores ';
		$this->assertEquals($expectedShortenedAbstract, $data['abstract']->getValue(), 'abstract not shortened');
	}

	/**
	 * @group unit
	 */
	public function testSetAbstractHandlesMultiByteCharsCorrectWithGivenLength() {
		$indexerDocument = $this->getIndexerDocument();

		$abstract = 'Rückantwort';
		$indexerDocument->setAbstract($abstract, 4);
		$data = $indexerDocument->getData();
		$expectedAbstract = 'Rück';
		$this->assertEquals($expectedAbstract, $data['abstract']->getValue(), 'multibyte string in abstract not correct handled');
	}

	/**
	 * @return tx_mksearch_model_IndexerDocumentBase
	 */
	private function getIndexerDocument() {
		return tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', 'mksearch','dummy'
		);
	}
}