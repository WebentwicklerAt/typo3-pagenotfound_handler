<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## set page not found handling
$TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = 'USER_FUNCTION:EXT:pagenotfound_handler/class.tx_pagenotfoundhandler.php:tx_pagenotfoundhandler->main';
?>