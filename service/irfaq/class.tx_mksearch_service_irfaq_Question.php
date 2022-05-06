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

/**
 * Service for accessing models from database.
 */
class tx_mksearch_service_irfaq_Question extends tx_mksearch_service_Base
{
    /**
     * returns all categories of the given question.
     *
     * @param int $iExpert
     *
     * @return array[tx_mksearch_model_irfaq_Question]
     */
    public function getByExpert($iExpert)
    {
        $fields = [];
        $options = [];
        $fields['IRFAQ_QUESTION.expert'][OP_EQ_INT] = $iExpert;

        return $this->search($fields, $options);
    }

    /**
     * returns all questions with the given category.
     *
     * @param int $iCategory
     *
     * @return array[tx_mksearch_model_irfaq_Question]
     */
    public function getByCategory($iCategory)
    {
        $fields = [];
        $options = [];
        $fields['IRFAQ_QUESTION_CATEGORY_MM.uid_foreign'][OP_EQ_INT] = $iCategory;

        return $this->search($fields, $options);
    }

    /**
     * Liefert die zugehörige Search-Klasse zurück.
     *
     * @return string
     */
    public function getSearchClass()
    {
        return 'tx_mksearch_search_irfaq_Question';
    }
}
