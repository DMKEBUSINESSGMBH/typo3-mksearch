<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
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
 * Miscellaneous methods.
 */
class tx_mksearch_util_Misc
{
    /**
     * Convert a Timestamp to a ISO formatted DateTime string in GMT time zone.
     *
     * @param \DateTime $datetime
     *
     * @return string in format Y-m-d\TH:i:s\Z
     */
    public static function getIsoDate(\DateTime $datetime)
    {
        return $datetime->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Convert a Timestamp to a ISO formatted DateTime string in GMT time zone.
     *
     * @param int $tstamp
     * @param int $offset
     *
     * @return string in format Y-m-d\TH:i:s\Z
     */
    public static function getISODateFromTimestamp($tstamp, $offset = 0)
    {
        return self::getIsoDate(
            new \DateTime('@'.($tstamp + $offset))
        );
    }

    /**
     * Allgemeine Include/exclude-Prüfung. Derzeit auf PID-Ebene.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     *
     * @deprecated unbedingt tx_mksearch_util_Indexer::getInstance()->isOnIndexablePage nutzen.
     * das unterstützt sowohl pages als auch pageTrees.
     */
    public static function isOnValidPage($sourceRecord, $options)
    {
        if (!is_array($options)) {
            return true;
        }
        if (array_key_exists('include.', $options) && is_array($options['include.'])) {
            $aPages = (array_key_exists('pages', $options['include.']) && strlen(trim($options['include.']['pages']))) ? \Sys25\RnBase\Utility\Strings::intExplode(',', $options['include.']['pages']) : false;
            if (!is_array($aPages)) {
                $aPages = $options['include.']['pages.'] ?? [];
            }

            if (is_array($aPages) && count($aPages)) {
                // Wenn das Element auf einer dieser Seiten eingebunden ist, indizieren!
                return in_array($sourceRecord['pid'], $aPages);
            }
        }
        if (array_key_exists('exclude.', $options) && is_array($options['exclude.'])) {
            $aPages = (array_key_exists('pages', $options['exclude.']) && strlen(trim($options['exclude.']['pages']))) ? \Sys25\RnBase\Utility\Strings::intExplode(',', $options['exclude.']['pages']) : false;
            if (!is_array($aPages)) {
                $aPages = $options['exclude.']['pages.'] ?? [];
            }
            if (is_array($aPages) && count($aPages)) {
                // Wenn das Element auf einer dieser Seiten eingebunden ist,
                // NICHT indizieren!
                return !in_array($sourceRecord['pid'], $aPages);
            }
        }

        return true;
    }

    /**
     * Liefert einen UTF8 codierten String.
     *
     * @TODO: wäre in der \Sys25\RnBase\Utility\Strings besser aufgehoben?
     *
     * @param mixed $t
     *
     * @return mixed
     */
    public static function utf8Encode($mixed)
    {
        // Nur prüfen, wenn es sich um einen String handelt.
        if (!is_string($mixed)) {
            return $mixed;
        }
        // String prüfen und ggf. encodieren

        return \Sys25\RnBase\Utility\Strings::isUtf8String($mixed) ? $mixed : utf8_encode($mixed);
    }

    /**
     * Convert HTML to plain text.
     *
     * Removes HTML tags and HTML comments and converts HTML entities
     * to their applicable characters.
     *
     * @param string $text
     * @param array  $options
     *
     * @return string Converted string (utf8-encoded)
     */
    public static function html2plain($text, array $options = [])
    {
        if (!is_string($text)) {
            return $text;
        }

        // sollen zeilenumbrüche übernommen werden? default is OFF
        $whitespaces = isset($options['lineendings']) && $options['lineendings'] ? true : false;
        $whitespaces = $whitespaces ? '[ \t\f]' : '\s';

        $replaces = [
            // whitespaces durch leerzeichen ersetzen
            '/('.$whitespaces.'+)/' => ' ',
            // alle HTML Tags entfernen
            '/((<.*?>)+)/' => ' ',
            // html kommentare entfernen
            '/<!--.*?-->/' => ' ',
        ];

        // replace double spaces
        if (!empty($options['removedoublespaces'])) {
            $replaces['/ +/'] = ' ';
        }

        return trim(
            html_entity_decode(
                preg_replace(
                    array_keys($replaces),
                    array_values($replaces),
                    $text
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /**
     * Sanitizes the given term and removes all unwanted
     * chars. These are for example some for the solr
     * search syntax.
     *
     * @param string $sTerm
     *
     * @return string
     *
     * @todo sollte hier nicht Apache_Solr_Service::escape() genutzt werden!?
     * siehe auch die escape Methode aus der Apache Solr TYPO3 Extension wie Phrasen
     * unterstützt werden könnten? Sollte aber alles nur fir nicht dismax interessant sein.
     * @todo dont remove the characters but escape them like sanitizeFq() does.
     */
    public static function sanitizeTerm($sTerm)
    {
        // wir brauchen 3 backslashes (\\\) um einen einfachen zu entwerten.
        // der erste entwertet den zweiten für die hochkommas. der zweite
        // entwertet den dritten für regex.
        // ansonsten sind das alle Zeichen, die in Solr nicht auftauchen
        // dürfen da sie zur such-syntax gehören
        // genau dürfen nicht auftauchen: + - & | ! ( ) { } [ ] ^ " ~ * ? : \
        // außerdem nehmen wir folgende raus um die Suche zu verfeinern:
        // , . / # ' % < >
        // eigentlich sollte dies aber ebenfalls durch Solr filter realisiert
        // werden
        return preg_replace('/[%,.&*+-\/\'!?#()\[\]\{\}"^|<>:\\\~]+/', '', $sTerm);
    }

    /**
     * Sanitizes the given term and removes all unwanted
     * chars. These are for example some for the solr
     * search syntax.
     *
     * does the same as sanitizeTerm, but dows not escape
     * , . / # ' % < >
     *
     * @param string $sTerm
     *
     * @return string
     *
     * @see Apache_Solr_Service::escape()
     */
    public static function sanitizeFq($value)
    {
        $match = ['\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';'];
        $replace = ['\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;'];

        return str_replace($match, $replace, $value);
    }

    /**
     * IP-based Access restrictions.
     *
     * @param string $remoteAddress
     * @param string $devIPmask
     *
     * @return bool
     */
    public static function isDevIpMask($remoteAddress = '', $devIPmask = '')
    {
        $devIPmask = trim(strcmp($devIPmask, '') ? $devIPmask : $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        $remoteAddress = trim(strcmp($remoteAddress, '') ? $remoteAddress : \Sys25\RnBase\Utility\Misc::getIndpEnv('REMOTE_ADDR'));

        return \Sys25\RnBase\Utility\Network::cmpIP($remoteAddress, $devIPmask);
    }

    /**
     * Parse the configuration of the given models.
     *
     * @param string $sTs
     */
    public static function parseTsConfig($sTs)
    {
        return \Sys25\RnBase\Utility\TypoScript::parseTsConfig($sTs);
    }

    /**
     * Bereinigt ein Array von allen Werten die leer sind.
     * Leere Arrays innerhalb des zu bereinigenden Arrays bleiben unberührt.
     * Die Keys werden by default nicht zurückgesetzt!
     *
     * @author 2011 mwagner
     *
     * @param array $aArray
     * @param bool  $bResetIndex setzt die Array Keys zurück, falls sie numerisch sind
     *
     * @return array
     */
    public static function removeEmptyValues(array $aArray, $bResetIndex = false)
    {
        $aEmptyElements = array_keys($aArray, '');
        foreach ($aEmptyElements as $key) {
            unset($aArray[$key]);
        }

        return $bResetIndex ? array_merge($aArray) : $aArray;
    }
}
