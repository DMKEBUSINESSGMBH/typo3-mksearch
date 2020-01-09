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

class tx_mksearch_filter_SearchByReferer extends tx_rnbase_filter_BaseFilter implements ListBuilderInfo
{
    /**
     * Initialize filter.
     *
     * @param array $fields
     * @param array $options
     */
    public function init(&$fields, &$options)
    {
        tx_rnbase_util_SearchBase::setConfigFields($fields, $this->getConfigurations(), $this->getConfId().'filter.fields.');
        // Optionen
        tx_rnbase_util_SearchBase::setConfigOptions($options, $this->getConfigurations(), $this->getConfId().'filter.options.');

        return $this->initFilter($fields, $options, $this->getParameters(), $this->getConfigurations(), $this->getConfId());
    }

    /**
     * Filter for search form.
     *
     * @param array                    $fields
     * @param array                    $options
     * @param tx_rnbase_parameters     $parameters
     * @param tx_rnbase_configurations $configurations
     * @param string                   $confId
     *
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(&$fields, &$options, &$parameters, &$configurations, $confId)
    {
        $config = $configurations->get($confId);
        $config = $config['filter.'];
        $referrer = (isset($config['refererDebug'])) ?
                        $config['refererDebug'] :
                        tx_rnbase_util_Misc::getIndpEnv('HTTP_REFERER');
        if ($referrer) {
            $config = $config['referers.'];
            if (is_array($config)) {
                foreach ($config as $k => $v) {
                    // Config found for current referrer?
                    if (isset($v['urlRegEx']) and preg_match($v['urlRegEx'], $referrer)) {
                        $matches = [];
                        // Search term found in referrer?
                        if (isset($v['searchTermRegEx']) and
                         preg_match($v['searchTermRegEx'], $referrer, $matches) and
                         isset($matches[1])
                        ) {
                            // Set fe_groups
                            global $GLOBALS;
                            $options['fe_groups'] = $GLOBALS['TSFE']->fe_user->groupData['uid'];

                            if (!isset($v['searchTermDelimiterRegEx'])) {
                                $v['searchTermDelimiterRegEx'] = '/\++/';
                            }
                            $terms = preg_split($v['searchTermDelimiterRegEx'], $matches[1]);
                            $sign = (isset($v['searchTermOperator']) and 'and' == $v['searchTermOperator']) ? true : null;
                            // Push search terms into search configuration
                            foreach ($terms as $t) {
                                $fields['__default__'][] = ['term' => $t, 'sign' => $sign];
                            }

                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get a message string for empty list. This is an language string. The key is
     * taken from ts-config: [item].listinfo.llkeyEmpty.
     *
     * @param array_object             $viewData
     * @param tx_rnbase_configurations $configurations
     *
     * @return string
     */
    public function getEmptyListMessage($confId, &$viewData, &$configurations)
    {
    }

    public function setMarkerArrays(&$markerArray, &$subpartArray, &$wrappedSubpartArray)
    {
    }

    public function getListMarkerInfo()
    {
        return null;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SearchByReferer.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SearchByReferer.php'];
}
