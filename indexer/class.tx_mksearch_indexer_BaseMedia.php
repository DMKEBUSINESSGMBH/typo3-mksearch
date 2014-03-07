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
abstract class tx_mksearch_indexer_BaseMedia
	implements tx_mksearch_interface_Indexer {

	/**
	 * Liefert den namen zur Basistabelle
	 *
	 * @return string
	 */
	abstract protected function getBaseTableName();

	/**
	 * Den Relativen Server-Pfad zur Datei.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	abstract protected function getRelFileName($tableName, $sourceRecord);

	/**
	 * Liefert die Dateiendung.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	abstract protected function getFileExtension($tableName, $sourceRecord);

	/**
	 * Liefert den Pfad zur Datei (ohne Dateinamen).
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	abstract protected function getFilePath($tableName, $sourceRecord);

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {

		if ($this->getBaseTableName() !== $tableName) {
			return NULL;
		}

		// die uid muss vor dem setDeleted gesetzt sein
		$indexDoc->setUid($sourceRecord['sys_language_uid'] ? $sourceRecord['l18n_parent'] : $sourceRecord['uid']);

		// pre process hoock
		tx_rnbase_util_Misc::callHook('mksearch', 'indexerBaseMedia_preProcessSearchData',
			array(
				'table' => $tableName,
				'rawData' => &$sourceRecord,
				'indexDoc' => &$indexDoc,
				'options' => $options,
			),
			$this
		);
		// check, if the doc was skiped or has to be deleted
		if (is_null($indexDoc) || $indexDoc->getDeleted()) {
			return $indexDoc;
		}

		// Check if record is configured to be indexed
		if(!$this->isIndexableRecord($tableName, $sourceRecord, $options['filter.'])) {
			if(isset($options['deleteIfNotIndexable']) && $options['deleteIfNotIndexable']) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			} else return null;
		}

		if($sourceRecord['deleted'] || $sourceRecord['hidden']) {
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		// titel aus dem feld titel oder name holen, als fallback den dateinamen nutzen!
		$title = $sourceRecord['title'] ? $sourceRecord['title'] : $sourceRecord['name'];
		$title = $title ? $title : basename($this->getRelFileName($tableName, $sourceRecord));
		$indexDoc->setTitle($title);
		$indexDoc->setTimestamp($sourceRecord['tstamp']);

		$content = $sourceRecord['description'] ? $sourceRecord['description'] : $sourceRecord['alternative'];
		$content = $content ? $content : $title;
		$indexDoc->setContent($content);

		$abstract = $sourceRecord['abstract'] ? $sourceRecord['abstract'] : $sourceRecord['alternative'];
		$abstract = $abstract ? $abstract : $content;
		$indexDoc->setAbstract($abstract);

		//den kompletten, relativen Pfad zum Dam Dokument indizieren
		$indexDoc->addField('file_relpath_s', $this->getRelFileName($tableName, $sourceRecord));

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

		$this->$indexMethod($tableName, $sourceRecord, $indexDoc, $options);

		// post precess hock
		tx_rnbase_util_Misc::callHook('mksearch', 'indexerBaseMedia_postProcessSearchData',
			array(
				'table' => $tableName,
				'rawData' => &$sourceRecord,
				'indexDoc' => &$indexDoc,
				'options' => $options,
				'indexMethod' => $indexMethod,
			),
			$this
		);

		return $indexDoc;
	}
	/**
	 * Do not do anything here.
	 */
	private function indexNone($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {

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
		$binaryOptions['sourcefile'] = $this->getAbsFileName($tableName, $sourceRecord);
		$binaryOptions['file_type'] = $this->getFileExtension($tableName, $sourceRecord);
		if (isset($sourceRecord['file_mime_type'])) {
			$binaryOptions['file_mime_type'] = $sourceRecord['file_mime_type'];
		}
		if (isset($sourceRecord['file_mime_subtype'])) {
			$binaryOptions['file_mime_subtype'] = $sourceRecord['file_mime_subtype'];
		}
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
		$file = $this->getAbsFileName($tableName, $sourceRecord);
		tx_rnbase::load('tx_mksearch_util_Tika');
		if(!tx_mksearch_util_Tika::getInstance()->isAvailable()) {
			tx_rnbase_util_Logger::warn('Apache Tika not available!', 'mksearch');
			return;
		}
		$tikaFields = $options['tikafields.'];
		$tikaFields = is_array($tikaFields) ? $tikaFields : array();
		$contentField = $tikaFields['content'];
		if($contentField) {
			$tikaCommand = '';
			if(!$content = tx_mksearch_util_Tika::getInstance()->extractContent($file, $tikaCommand)) {
				tx_rnbase_util_Logger::warn(
					'Apache Tika returned empty content!',
					'mksearch',
					array(
						'file' 			=> $file,
						'tikaCommand' 	=> $tikaCommand
					)
				);
			}

			$indexDoc->addField($contentField, $content);
			$indexFields = $indexDoc->getData();
			if(empty($indexFields['abstract']) || !$indexFields['abstract']->getValue()) {
				$indexDoc->setAbstract($content, $indexDoc->getMaxAbstractLength());
			}
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
	 * Den Absoluten Server-Pfad zur Datei.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	protected function getAbsFileName($tableName, $sourceRecord) {
		return PATH_site.$this->getRelFileName($tableName, $sourceRecord);
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

		$fileExtension = $this->getFileExtension($tableName, $sourceRecord);
		$filePath = $this->getFilePath($tableName, $sourceRecord);
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
					$ret = in_array($fileExtension, $filterValue);
					break;
				// Auf den Pfad hin prüfen! Achtung: Funktioniert nicht in Kombination:
				//	trifft preg_match nicht zu, wird in_array nicht mehr geprüft!
				//	entwerder 	preg_match 	mit byDirectory
				//	oder		in_array 	mit byDirectory.
				case 'byDirectory':
					$pattern = $filterValue;
					// TODO: Validate pattern
					$directory = $filePath;
					$ret = preg_match($pattern, $directory) != 0;
					break;
				case 'byDirectory.':
					// wir prüfen mit array_search, da wir den key noch brauchen.
					if(($key = array_search($filePath, $filterValue)) !== false) {
						$ret = intval($filterValue[$key.'.']['disallow']) ? false : true;
					}
					// wenn keine treffer gefunden wurden, prüfen wir, ob es ein unterordner davon ist.
					elseif($filterValue['checkSubFolder']) {
						unset($filterValue['checkSubFolder']); // brauchen wir nicht mehr
						foreach($filterValue as $key => $folder) {
							if(t3lib_div::isFirstPartOfStr($filePath, $folder)){
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
		return $ret;
	}

	private function getIndexMethod($options) {
		$mode = empty($options['indexMode'])
			? 'solr'
			: strtolower($options['indexMode']);
		switch ($mode) {
			case 'tika':
				return 'indexTika';
			case 'none':
				return 'indexNone';
			case 'solr':
			default:
				return 'indexSolr';
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
		$table = $this->getBaseTableName();
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
deleteIfNotIndexable = 1

# define filters for DAM records. All filters must match to index a record.
filter.$table {
  # a regular expression
  byDirectory = /^fileadmin\/.*\//
  # Diese Ordner werden geprüft, wenn byDirectory wahr oder nicht gesetzt ist.
  byDirectory {
    # Dateien dürfen auch in Unterordnern liegen.
    checkSubFolder = 1
    # fileadmin global verbieten
    1 = fileadmin/
    1.disallow = 1
    # einzelne Ordner (inkl. Unterordner) erlauben
    2 = fileadmin/allowed/
    2.disallow = 0
  }
  # commaseparated strings
  byFileExtension = pdf, html
  # TODO: Workspace
}

# Define which fields to index and to which fields
fields {
  ### name field for DAM
  #file_name = file_name_s
  ### name field for FAL
  #name = file_name_s
}
tikafields {
  # tikafield = indexfield
  content = content
  language = lang_s
  meta.Content-Encoding = encoding_s
  meta.Content-Length = filesize_i
  meta.resourceName = resource_name_s
}

CFG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php']);
}