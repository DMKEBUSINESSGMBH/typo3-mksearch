<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

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
     * @param array $fields
     * @param array $options
     *
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(&$fields, &$options, Sys25\RnBase\Frontend\Request\RequestInterface $request): bool
    {
        // erstmal die prinzipielle Suche von unserem Elter initialisieren lassen
        if (parent::initFilter($fields, $options, $request)) {
            // dann setzen wir die Werte fest, da Facetten weder echte Ergebnisse benötigen
            // noch eingeschränkt werden wollen. Sollen sie doch eingeschränkt werden
            // dann einfach einen Filter verwenden der "fq" nicht statisch auf nichts setzt
            $options['limit'] = 0; // nie wirklich suchen
            $options['facet'] = 'true';

            return true; // damit der Filter als valide betrachtet wird
        }

        return false;
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
