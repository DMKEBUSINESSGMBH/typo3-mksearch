<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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


require_once tx_rnbase_util_Extensions::extPath('mksearch', 'lib/Apache/Solr/Document.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_indexer_ttcontent_Normal');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_ttcontent_Normal_testcase extends tx_mksearch_tests_Testcase
{

    /**
     * @group unit
     */
    public function testPrepareSearchDataIncludesPageMetaKeywords()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            array('getPageContent', 'isIndexableRecord', 'hasDocToBeDeleted')
        );
        $indexer->expects($this->once())
            ->method('isIndexableRecord')
            ->will($this->returnValue(true));
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));
        $indexer->expects($this->any())
            ->method('getPageContent')
            ->with(456)
            ->will($this->returnValue(array('keywords' => 'first,second')));

        list($extKey, $cType) = $indexer->getContentType();

        $record = array('uid' => 123, 'pid' => 456, 'CType' => 'list', 'bodytext' => 'lorem');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['addPageMetaData'] = 1;
        $options['addPageMetaData.']['separator'] = ',';
        $options['includeCTypes.'] = array('search','mailform','list');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        $indexDocData = $indexDoc->getData();

        self::assertEquals(array('first', 'second'), $indexDocData['keywords_ms']->getValue());
    }

    /**
     * @group unit
     */
    public function testIndexDataCallsIndexPageDataIfConfigured()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            array('indexPageData', 'getModelToIndex')
        );

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = array('uid' => 123);
        $model = tx_rnbase::makeInstance('tx_rnbase_model_Base', $record);
        $model->setTableName('tt_Content');
        $options = self::getDefaultOptions();
        $options['indexPageData'] = 1;

        $indexer->expects($this->once())
            ->method('indexPageData')
            ->with($indexDoc, $options);
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->will(
                $this->returnValue(
                    $this->getModel($model)
                )
            );

        $indexer->indexData($model, 'tt_content', $record, $indexDoc, $options);
    }

    /**
     * @group unit
     */
    public function testIndexDataCallsIndexPageDataNotIfNotConfigured()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            array('indexPageData', 'getModelToIndex')
        );

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = array('uid' => 123);
        $model = tx_rnbase::makeInstance('tx_rnbase_model_Base', $record);
        $model->setTableName('tt_Content');
        $options = self::getDefaultOptions();
        $options['indexPageData'] = 0;

        $indexer
        ->expects($this->never())
            ->method('indexPageData');
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->will(
                $this->returnValue(
                    $this->getModel($model)
                )
            );

        $indexer->indexData($model, 'tt_content', $record, $indexDoc, $options);
    }

    /**
     * @group unit
     */
    public function testIndexPageData()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            array('getPageContent')
        );

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $model = tx_rnbase::makeInstance('tx_rnbase_model_Base', array('uid' => 123));
        $model->setTableName('tt_Content');
        $indexer->_set('modelToIndex', $model);

        $options = array(
            'pageDataFieldMapping.' => array(
                'title' => 'title_t',
                'description' => 'description_t',
            )
        );

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->will($this->returnValue(array(
                'title' => 'Homepage',
                'description' => 'Starting point',
                'subtitle' => 'not to be indexed'
            )));

        $this->callInaccessibleMethod($indexer, 'indexPageData', $indexDoc, $options);

        $indexedData = $indexDoc->getData();

        self::assertCount(3, $indexedData, 'more fields than expected indexed');
        self::assertEquals(
            'mksearch.test',
            $indexedData['content_ident_s']->getValue(),
            'content_ident_s wrong'
        );
        self::assertEquals(
            'Homepage',
            $indexedData['page_title_t']->getValue(),
            'page title wrong indexed'
        );
        self::assertEquals(
            'Starting point',
            $indexedData['page_description_t']->getValue(),
            'page description wrong indexed'
        );
    }

    /**
     * @return multitype:string
     */
    private static function getDefaultOptions()
    {
        $options = array();
        $options['CType.']['_default_.']['indexedFields.'] = array(
            'bodytext', 'imagecaption' , 'altText', 'titleText'
        );

        return $options;
    }
}
