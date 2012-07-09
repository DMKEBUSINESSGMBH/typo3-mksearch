<?php

// DO NOT REMOVE OR CHANGE THESE 2 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/mksearch/mod1/');
$BACK_PATH='../../../../typo3/';

/**
 * Wir erstellen zurätzlich noch den REQUIRE_PATH.
 * Der BACK_PATH wird relativ benötigt,
 * da er auch für Links und Grafikausgaben im Backend-HTML genutzt wird.
 * Die require_once Aufrufe müssen dann den REQUIRE_PATH anstelle des BACK_PATHS nutzen!
 *
 * Default ist der REQUIRE_PATH gleich dem BACK_PATH.
 * Ist der REQUIRE_PATH allerdings nicht lesbar,
 * wird die Extension sicher über einen symbolischen Link im Typo3 integriert.
 * In diesem Fall müssen wir uns den Pfad speziell besorgen.
 */
$REQUIRE_PATH = $BACK_PATH;
if (!is_readable($REQUIRE_PATH))
{
	$PATH_thisScript = str_replace('//', '/', str_replace('\\', '/',
			(PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
			(isset($_SERVER['ORIG_PATH_TRANSLATED']) ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) ?
			(isset($_SERVER['ORIG_PATH_TRANSLATED']) ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) :
			(isset($_SERVER['ORIG_SCRIPT_FILENAME']) ? $_SERVER['ORIG_SCRIPT_FILENAME'] : $_SERVER['SCRIPT_FILENAME']))
		);

	// Aufruf direkt über das backendmodul
	if ($strpos = strpos($PATH_thisScript, '/typo3conf/'))
	{
		$REQUIRE_PATH = substr($PATH_thisScript, 0, $strpos + 1) .'typo3/';
	}
	// Aufruf direkt aus dem typo3 backend
	elseif($strpos = strpos($PATH_thisScript, '/typo3/'))
	{
		$REQUIRE_PATH = substr($PATH_thisScript, 0, $strpos + 1) .'typo3/';
	}

	if (!is_readable($REQUIRE_PATH))
	{
		echo '<h1>Es konnte kein lesbarer REQUIRE_PATH gefunden werden</h1>';
		echo '<h2>BACK_PATH</h2>'.'<pre>'.$BACK_PATH.'</pre>';
		echo '<h2>REQUIRE_PATH</h2>'.'<pre>'.$REQUIRE_PATH.'</pre>';
		echo '<h2>PATH_THISSCRIPT</h2>'.'<pre>'.$PATH_thisScript.'</pre>';
		exit('EXIT: '.__FILE__.'&'.__METHOD__.' Line: '.__LINE__);
	}
}

$MCONF['name'] = 'web_txmksearchM1';
$MCONF['script']='index.php';

$MCONF['access'] = 'user,group';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mksearch/mod1/locallang_mod.xml';

define('ICON_OK', -1);
define('ICON_INFO', 1);
define('ICON_WARN', 2);
define('ICON_FATAL', 3);