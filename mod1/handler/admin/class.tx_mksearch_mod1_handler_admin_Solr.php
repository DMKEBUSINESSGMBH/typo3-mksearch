<?php

tx_rnbase::load('tx_mksearch_mod1_handler_Base');
tx_rnbase::load('tx_rnbase_mod_IModHandler');

/**
 * Backend Modul Index.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_handler_admin_Solr implements tx_rnbase_mod_IModHandler
{
    private $data = array();

    /**
     * Returns a unique ID for this handler. This is used to created the subpart in template.
     *
     * @return string
     */
    public function getSubID()
    {
        return 'AdminSolr';
    }

    /**
     * Returns the label for Handler in SubMenu. You can use a label-Marker.
     *
     * @return string
     */
    public function getSubLabel()
    {
        return '###LABEL_HANDLER_'.strtoupper($this->getSubID()).'###';
    }

    /**
     * This method is called each time the method func is clicked, to handle request data.
     *
     * @param tx_rnbase_mod_IModule $mod
     */
    public function handleRequest(tx_rnbase_mod_IModule $mod)
    {
        $submitted = tx_rnbase_parameters::getPostOrGetParameter('doDelete') || tx_rnbase_parameters::getPostOrGetParameter('doQuery');
        if (!$submitted) {
            return '';
        }

        $this->data = tx_rnbase_parameters::getPostOrGetParameter('data');
        $deleteQuery = trim($this->data['deletequery']);
        $SET = tx_rnbase_parameters::getPostOrGetParameter('SET');
        $core = intval($SET['solr_core']);
        if (!$core) {
            $mod->addMessage('###LABEL_SOLR_NOCORE_FOUND###', '###LABEL_COMMON_WARNING###');

            return;
        }

        try {
            $core = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', $core);
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($core);
            $result = $searchEngine->indexDeleteByQuery($deleteQuery);
            $searchEngine->commitIndex();
            $mod->addMessage('###LABEL_SOLR_DELETE_SUCCESSFUL###', '###LABEL_COMMON_INFO###');
        } catch (Exception $e) {
            tx_rnbase::load('tx_rnbase_util_Logger');
            $mod->addMessage(htmlspecialchars($e->getMessage()), '###LABEL_COMMON_ERROR###', 2);
            tx_rnbase_util_Logger::warn('[SolrAdmin] Exception for delete query.', 'mksearch', array('Exception' => $e->getMessage()));
        }
    }

    /**
     * Display the user interface for this handler.
     *
     * @param string                $template the subpart for handler in func template
     * @param tx_rnbase_mod_IModule $mod
     * @param array                 $options
     */
    public function showScreen($template, tx_rnbase_mod_IModule $mod, $options)
    {
        $markerArray = array();

        $cores = $this->findSolrCores($mod);
        if (empty($cores)) {
            return '###LABEL_SOLR_NOCORES_FOUND###';
        }

        $out = $this->showAdminPanel($template, $cores, $mod, $markerArray);

        return $out;
    }

    /**
     * Returns search form.
     *
     * @param string                $template
     * @param array                 $cores
     * @param tx_rnbase_mod_IModule $mod
     *
     * @return string
     */
    protected function showAdminPanel($template, $cores, tx_rnbase_mod_IModule $mod, &$markerArray)
    {
        tx_rnbase::load('tx_mksearch_mod1_util_Template');

        $formTool = $mod->getFormTool();

        $markerArray['###SEL_CORES###'] = $this->getCoreSelector($cores, $mod);
        $markerArray['###INPUT_DELETEQUERY###'] = $formTool->createTextArea('data[deletequery]', $this->data['deletequery']);
        $markerArray['###BTN_SEND###'] = $formTool->createSubmit('doDelete', '###LABEL_SOLR_SUBMIT_DELETE###', 'Do you really want to submit this DELETE query?');

        $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);

        return $out;
    }

    protected function findSolrCores(tx_rnbase_mod_IModule $mod)
    {
        tx_rnbase::load('tx_rnbase_util_SearchBase');

        $fields = $options = array();
        $options['enablefieldsfe'] = 1;
        // Solr-Core auf der aktuellen Seite suchen
        $fields['INDX.PID'][OP_EQ_INT] = $mod->getPid();
        $fields['INDX.ENGINE'][OP_EQ] = 'solr';
        $cores = tx_mksearch_util_ServiceRegistry::getIntIndexService()->search($fields, $options);

        return $cores;
    }

    /**
     * @param tx_rnbase_mod_IModule $mod
     */
    protected function getCoreSelector($cores, tx_rnbase_mod_IModule $mod)
    {
        $entries = array();
        foreach ($cores as $core) {
            $entries[$core->getUid()] = $core->getTitle().' ('.$core->getName().') '.$this->countDocs($core);
        }
        $menu = $mod->getFormTool()->showMenu($mod->getPid(), 'solr_core', $mod->getName(), $entries);

        return $menu['menu'];
    }

    /**
     * @param tx_mksearch_model_internal_Index $core
     */
    protected function countDocs($core)
    {
        $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($core);

        $fields['term'] = '*:*';
        $options['rows'] = '0';
        $info = '';
        try {
            $ret = $result = $searchEngine->search($fields, $options);
            $info = $ret['numFound'].' docs found';
        } catch (Exception $e) {
            $info = $e->getMessage();
        }

        return '('.$info.')';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/admin/class.tx_mksearch_mod1_handler_admin_Solr.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/admin/class.tx_mksearch_mod1_handler_admin_Solr.php'];
}
