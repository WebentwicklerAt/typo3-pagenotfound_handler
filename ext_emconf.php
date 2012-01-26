<?php

########################################################################
# Extension Manager/Repository config file for ext: "pagenotfound_handler"
#
# Auto generated 19-09-2009 20:21
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Search for closest matching page',
	'description' => 'If a certain page is not found, redirect the user to the closest matching page or display a result based on indexed_search with matching keywords.',
	'category' => 'fe',
	'author' => 'Gernot Leitgab',
	'author_email' => 'leitgab@gmail.com',
	'shy' => '',
	'dependencies' => 'indexed_search',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.4',
	'constraints' => array(
		'depends' => array(
			'indexed_search' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:6:{s:32:"class.tx_pagenotfoundhandler.php";s:4:"3236";s:12:"ext_icon.gif";s:4:"90f6";s:17:"ext_localconf.php";s:4:"a67b";s:24:"ext_typoscript_setup.txt";s:4:"f047";s:17:"doc/manual.de.sxw";s:4:"e0c6";s:14:"doc/manual.sxw";s:4:"8ddb";}',
	'suggests' => array(
	),
);

?>