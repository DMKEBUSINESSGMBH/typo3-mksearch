<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 das Medienkombinat
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

/**
 * Die eigentlich nur dazu fq immer leer zu lassen
 * und limit auf 0 zu setzen da wir alle facetten wollen, mehr nicht.
 *
 * @author Hannes Bochmann
 */
class tx_mksearch_filter_FacetBase extends tx_mksearch_filter_SolrBase
{
    /**
     * Die eigentliche Konfiguration der facetten sollte
     * über den request Handler geschehen und nicht im Filter.
     *
     * @param array                    $fields
     * @param array                    $options
     * @param \Sys25\RnBase\Frontend\Request\RequestInterface $request
     *
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(&$fields, &$options, \Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        //erstmal die prinzipielle Suche von unserem Elter initialisieren lassen
        if (parent::initFilter($fields, $options, $request)) {
            //dann setzen wir die Werte fest, da Facetten weder echte Ergebnisse benötigen
            //noch eingeschränkt werden wollen. Sollen sie doch eingeschränkt werden
            //dann einfach einen Filter verwenden der "fq" nicht statisch auf nichts setzt
            $options['limit'] = 0; //nie wirklich suchen
            $options['facet'] = 'true';

            return true; //damit der Filter als valide betrachtet wird
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_filter_SolrBase::handleFq()
     *
     * nie einschränken außer die Standard FQs
     */
    protected function handleFq(&$options, &$parameters, &$configurations, $confId)
    {
        self::addFilterQuery($options, self::getFilterQueryForFeGroups());

        // respect Root Page
        $this->handleFqForSiteRootPage($options, $configurations, $confId);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SearchForm.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SearchForm.php'];
}
