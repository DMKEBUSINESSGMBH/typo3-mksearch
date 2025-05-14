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

use Sys25\RnBase\Frontend\Request\ParametersInterface;

/**
 * Methods for accessing configuration options etc.
 */
class tx_mksearch_util_SearchBuilder
{
    /**
     * Setzt jedes Wort in Anführungszeichen.
     * Dabei werden operatoren wie + und - beachtet.
     */
    private static function quoteTerms(array $terms): array
    {
        foreach ($terms as &$term) {
            // minus und plus dürfen nicht mit in die quotes
            $operator = $term[0];
            $term = match ($operator) {
                '-', '+' => $operator.'"'.substr($term, 1).'"',
                default => '"'.$term.'"',
            };
        }

        return $terms;
    }

    /**
     * Setzt hinter jedes Wort eine tilde für die fuzzy search.
     */
    private static function fuzzyTerms(array $terms, string $slop = '0.2'): array
    {
        foreach ($terms as &$term) {
            $term = $term.'~'.$slop;
        }

        return $terms;
    }

    /**
     * Removes all solr control characters.
     */
    private static function sanitizeTerms(array $terms): array
    {
        if (self::emptyTerm($terms)) {
            return $terms;
        }

        foreach ($terms as $key => &$term) {
            // remove empty strings. can happen when the term contains only control chars
            $term = tx_mksearch_util_Misc::sanitizeTerm($term);
            if (self::emptyTerm($term)) {
                unset($terms[$key]);
            }
        }

        return $terms;
    }

    /**
     * wraps terms in wildcards.
     */
    private static function wrapTermsInWildcards(array $terms): array
    {
        if (self::emptyTerm($terms)) {
            return $terms;
        }

        foreach ($terms as &$term) {
            $term = '*'.$term.'*';
        }

        return $terms;
    }

    /**
     * Wurde nach OR gesucht?
     * Dann müssen wir beim DisMaxRequestHandler Min-should-match reduzieren.
     *
     * @todo: tests schreiben
     *
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    public static function handleMinShouldMatch(array &$fields, array &$options, &$parameters, &$configurations, string $confId): void
    {
        // brauchen wir nur wen ein term angegeben wurde
        if (!self::emptyTerm($fields['term'])) {
            // @todo: in generiche methode auslagern, welche werte anhand combination und confid ausliest!
            $combination = $parameters->get('combination');
            if ($combination) {
                // mm aus der config holen
                $mm = $configurations->get($confId.'mm.'.$combination);
                // default nutzen wir aus none, wenn gesetzt
                $mm = $mm ?: $configurations->get($confId.'mm.'.MKSEARCH_OP_NONE);
                if ($mm) {
                    $options['mm'] = $mm;
                }
            }

            //             switch ($combination) {
            //                 case MKSEARCH_OP_NONE:
            //                 case MKSEARCH_OP_OR:
            //                     $options['mm'] = '20%';
            //             }
        }
    }

    /**
     * Bei aktiviertem DisMaxRequestHandler und fuzzy muss die query leer sein.
     * Der term muss dann im filter query stehen ( fq=text:fuzzystring~0.2^1.0 ).
     *
     * @todo: tests schreiben
     *
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param string                               $confId
     */
    public static function handleDismaxFuzzySearch(array &$fields, array &$options, &$parameters, &$configurations, $confId): void
    {
        if (!self::emptyTerm($fields['term'])
            && is_array($params = $parameters->get('options'))
            && $params['fuzzy']
        ) {
            switch ($parameters->get('combination')) {
                case MKSEARCH_OP_FREE:
                    break;
                default:
                    $options['qf'] = $options['qf'] ? $options['qf'].' ' : '';
                    // @TODO: das feld indem gesucht werden soll konfigurierbar machen
                    // am besten generich wie handleMinShouldMatch umstellen
                    $options['qf'] .= 'text:'.$fields['term'];
                    $fields['term'] = '';
            }
        }
    }

    /**
     * Setzt den Term entsprechend des combination parameters zusammen.
     * Mögliche Werte: 'none', 'free', 'or', 'and', 'exact'.
     *
     * Wurde nichts oder none übergeben wird die auswertung solr überlassen
     *
     * @param array $options
     */
    public static function searchSolrOptions($term = '', $combination = '', $options = []): ?string
    {
        if (self::emptyTerm($term)) {
            return '';
        }

        // die wörter aufsplitten
        $terms = Sys25\RnBase\Utility\Strings::trimExplode(' ', $term, 1);
        if (self::emptyTerm($terms)) {
            return '';
        }

        $quote = intval($options['quote'] ?? 0);
        $dismax = intval($options['dismax'] ?? 0);
        $fuzzy = intval($options['fuzzy'] ?? 0) || !empty($options['fuzzy'] ?? 0);
        $sanitize = intval($options['sanitize'] ?? 0);
        $wildcard = intval($options['wildcard'] ?? 0);

        // sanitize term?
        if (0 !== $sanitize) {
            switch ($combination) {
                // sanitize nie bei free
                case MKSEARCH_OP_FREE:
                    break;
                default:
                    $terms = self::sanitizeTerms($terms);
                    break;
            }
        }

        // wrap in wildcards?
        if (0 !== $wildcard) {
            switch ($combination) {
                // wildcards nie bei free
                case MKSEARCH_OP_FREE:
                    break;
                default:
                    $terms = self::wrapTermsInWildcards($terms);
                    break;
            }
        }

        // quotes
        if (0 !== $quote) {
            switch ($combination) {
                // exact und free nicht quoten
                case MKSEARCH_OP_EXACT:
                case MKSEARCH_OP_FREE:
                    break;
                default:
                    $terms = self::quoteTerms($terms);
                    break;
            }
        }

        // fuzzy
        if ($fuzzy) {
            $fuzzySlop = $options['fuzzySlop'] ?? '0.2';
            switch ($combination) {
                // fuzzy nicht bei free, bei exact wird es später gesetzt!
                case MKSEARCH_OP_FREE:
                case MKSEARCH_OP_EXACT:
                    break;
                default:
                    $terms = self::fuzzyTerms($terms, $fuzzySlop);
                    break;
            }
        }

        // ist ein Suchstring übrig geblieben?
        if (!self::emptyTerm($terms)) {
            switch ($combination) {
                case MKSEARCH_OP_AND:
                    // alle mit einem plus verbinden
                    // @TODO: wenn vor dem term bereits ein + steht und wir noch eines anfügen, bekommen wir eine exception von solr!
                    $return = '+'.implode(' +', $terms);
                    // wenn nicht dismax, klammern drum rum
                    $return = 0 !== $dismax ? $return : '('.$return.')';
                    break;
                case MKSEARCH_OP_EXACT:
                    // in anführungszeichen setzen, bei fuzzy auch die tilde anfügen!
                    $return = '"'.implode(' ', $terms).'"'.($fuzzy ? '~'.$fuzzySlop : '');
                    // wenn nicht dismax, klammern drum rum
                    $return = 0 !== $dismax ? $return : '('.$return.')';
                    break;
                case MKSEARCH_OP_FREE:
                    $return = implode(' ', $terms);
                    break;
                case MKSEARCH_OP_NONE:
                case MKSEARCH_OP_OR:
                    // solr sucht default mit dem or operator!
                default:
                    $return = implode(' ', $terms);
                    // wenn nicht dismax, klammern drum rum
                    $return = 0 !== $dismax ? $return : '('.$return.')';
                    break;
            }
        } else {
            $return = null;
        }

        return $return;
    }

    public static function emptyTerm($term): bool
    {
        if (is_array($term)) {
            return 0 == count($term);
        }

        // wir nutzen strlen und nicht empty damit auch bei "0" gesucht wird
        return 0 == strlen($term);
    }
}
