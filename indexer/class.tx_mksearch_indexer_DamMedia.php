<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <nitzsche@das-medienkombinat.de>
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_rnbase_util_Logger');


/**
 * Indexer service for dam.media called by the "mksearch" extension.
 */
class tx_mksearch_indexer_DamMedia implements tx_mksearch_interface_Indexer {
	
	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType() {
		return array('dam', 'media');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {

		// Check if record is configured to be indexed
		if(!$this->isIndexableRecord($tableName, $sourceRecord, $options['filter.'])) {
			if(isset($options['deleteIfNotIndexable']) && $options['deleteIfNotIndexable']) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			} else return null;
		}

		$indexDoc->setUid($sourceRecord['sys_language_uid'] ? $sourceRecord['l18n_parent'] : $sourceRecord['uid']);

		if($sourceRecord['deleted']) {
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		$indexDoc->setTitle($sourceRecord['title']);
		$indexDoc->setTimestamp($sourceRecord['tstamp']);
		$indexDoc->setFeGroups(
			tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups(
				$sourceRecord['pid'],
				t3lib_div::trimExplode(',', $sourceRecord['fe_group'], true)
			)
		);

		$content = $sourceRecord['description'] ? $sourceRecord['description'] : $sourceRecord['title'];
		$indexDoc->setContent($content);
		$indexDoc->setAbstract($sourceRecord['abstract'] ? $sourceRecord['abstract'] : $content);
		
		$fields = $options['fields.'];
		foreach($fields AS $localFieldName => $indexFieldName) {
			$indexDoc->addField($indexFieldName, $sourceRecord[$localFieldName], 'keyword');
		}
		// Wie sollen die Binärdaten indiziert werden? Solr Cell oder Tika?
		$indexMethod = $this->getIndexMethod($options);
		if(!method_exists($this, $indexMethod)) {
			tx_rnbase_util_Logger::warn('Configured index method not supported: ' . $indexMethod, 'mksearch');
			return false;
		}
		
		//den kompletten, relativen Pfad zum Dam Dokument indizieren
		$indexDoc->addField('file_relpath_s', $sourceRecord['file_path'] . $sourceRecord['file_name']);
		
		$this->$indexMethod($tableName, $sourceRecord, $indexDoc, $options);
		return $indexDoc;
	}
	/**
	 * Indexing binary data by Solr CELL
	 * @param table $tableName
	 * @param array $sourceRecord
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 */
	private function indexSolr($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		$binaryOptions = array();
		$binaryOptions['sourcefile'] = PATH_site.$sourceRecord['file_path'].$sourceRecord['file_name'];
		$binaryOptions['file_mime_type'] = $sourceRecord['file_mime_type'];
		$binaryOptions['file_mime_subtype'] = $sourceRecord['file_mime_subtype'];
		$binaryOptions['file_type'] = $sourceRecord['file_type'];
		$indexDoc->addSECommand('indexBinary', $binaryOptions);
	}
	/**
	 *
	 * @param table $tableName
	 * @param array $sourceRecord
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 */
	private function indexTika($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		$file = PATH_site.$sourceRecord['file_path'].$sourceRecord['file_name'];
		tx_rnbase::load('tx_mksearch_util_Tika');
		if(!tx_mksearch_util_Tika::getInstance()->isAvailable()) {
			tx_rnbase_util_Logger::warn('Apache Tika not available!', 'mksearch');
			return;
		}
		$tikaFields = $options['tikafields.'];
		$tikaFields = is_array($tikaFields) ? $tikaFields : array();
		$contentField = $tikaFields['content'];
		if($contentField) {
			$content = tx_mksearch_util_Tika::getInstance()->extractContent($file);
			$indexDoc->addField($contentField, $content);
			if(empty($sourceRecord['abstract']))
				$indexDoc->setAbstract($content, $indexDoc->getMaxAbstractLength());
		}
		$langField = $tikaFields['language'];
		if($langField) {
			$lang = tx_mksearch_util_Tika::getInstance()->extractLanguage($file);
			$indexDoc->addField($langField, $lang);
		}
		$metaFields = $tikaFields['meta.'];
		if(is_array($metaFields)) {
			$meta = tx_mksearch_util_Tika::getInstance()->extractMetaData($file);
			foreach($metaFields As $tikaField => $indexField) {
				if(array_key_exists($tikaField, $meta))
					$indexDoc->addField($indexField, $meta[$tikaField]);
			}
		}
	}
	/**
	 * Prüft anhand der Konfiguration, ob der übergebene DAM-Datensatz indiziert werden soll.
	 * Aktuell kann dies über die Dateiendung und/oder das Verzeichnis festgelegt werden.
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @param array $options
	 */
	protected function isIndexableRecord($tableName, $sourceRecord, $options) {
		$ret = true;
		$filters = $options[$tableName.'.'];
		$filters = is_array($filters) ? $filters : array();
		if($tableName == 'tx_dam') {
			foreach ($filters As $filterName => $filterValue) {
				switch ($filterName) {
					// Auf Dateiendung Prüfen
					// 	Kommagetrennt	mit byFileExtension
					//	Als Array		mit byFileExtension.
					case 'byFileExtension':
						$filterValue = t3lib_div::trimExplode(',',$filterValue);
						$filterValue = is_array($filters['byFileExtension.'])
							? array_merge(array_values($filters['byFileExtension.']), $filterValue)
							: $filterValue;
					case 'byFileExtension.':
						$ret = in_array($sourceRecord['file_type'], $filterValue);
						break;
					// Auf den Pfad hin prüfen! Achtung: Funktioniert nicht in Kombination:
					//	trifft preg_match nicht zu, wird in_array nicht mehr geprüft!
					//	entwerder 	preg_match 	mit byDirectory
					//	oder		in_array 	mit byDirectory.
					case 'byDirectory':
						$pattern = $filterValue;
						// TODO: Validate pattern
						$directory = $sourceRecord['file_path'];
						$ret = preg_match($pattern, $directory) != 0;
						break;
					case 'byDirectory.':
						// wir prüfen mit array_search, da wir den key noch brauchen.
						if(($key = array_search($sourceRecord['file_path'], $filterValue)) !== false) {
							$ret = intval($filterValue[$key.'.']['disallow']) ? false : true;
						}
						// wenn keine treffer gefunden wurden, prüfen wir, ob es ein unterordner davon ist.
						elseif($filterValue['checkSubFolder']) {
							unset($filterValue['checkSubFolder']); // brauchen wir nicht mehr
							foreach($filterValue as $key => $folder) {
								if(t3lib_div::isFirstPartOfStr($sourceRecord['file_path'], $folder)){
									$ret = intval($filterValue[$key.'.']['disallow']) ? false : true;
									break;
								}
							}
						}
						// dieser ordner wurde nicht konfiguriert, wir ignorieren ihn
						else {
							$ret = false;
						}
						break;
				}
				if(!$ret) break;
			}
		}
		return $ret;
	}

	private function getIndexMethod($options) {
		$ret = 'indexSolr';
		if(array_key_exists('indexMode', $options) && strtolower($options['indexMode']) == 'tika') {
			$ret = 'indexTika';
		}
		return $ret;
	}

	/**
	 * Return the default Typoscript configuration for this indexer.
	 *
	 * Note that this config is not used for actual indexing
	 * but only serves as assistance when actually configuring an indexer!
	 * Hence all possible configuration options should be set or
	 * at least be mentioned to provide an easy-to-access inline documentation!
	 *
	 * @return string
	 *
	 */
	public function getDefaultTSConfig() {
		return <<<CFG
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#	my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }

# Configuration for indexing mode: tika/solr
# - tika means local data extraction with tika.jar (Java required on local server!)
# - solr means data extraction on remote Solr-Server. Binary data is streamed by http.
indexMode = solr
# optional array of key value pairs that will be sent with the post (see Solr Cell documentation)
#solr.indexOptions.params


### delete from or abort indexing for the record if isIndexableRecord or no record?
 deleteIfNotIndexable = 0

# define filters for DAM records. All filters must match to index a record.
filter.tx_dam {
  # a regular expression
  byDirectory = /^fileadmin\/.*\//
  # Diese Ordner werden geprüft, wenn byDirectory wahr oder nicht gesetzt ist.
  byDirectory {
    # Dateien dürfen auch in Unterordnern liegen.
    checkSubFolder = 1
    1 = fileadmin/denied/
    1.disallow = 1
    2 = fileadmin/allowed/
    2.disallow = 0
  }
  # commaseparated strings
  byFileExtension = pdf, html
  byFileExtension {
  }
  # TODO: Workspace
}

# Define which DAM fields to index and to which fields
fields {
  file_name = file_name_s
  abstract = abstract_s
}
tikafields {
  # tikafield = indexfield
  content = content
  language = lang_s
  meta.Content-Encoding = encoding_s
  meta.Content-Length = filesize_i
}

CFG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php']);
}