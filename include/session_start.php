<?php
	require_once('config.php'); 
	
	/****************SESSION****************************/
	$SESSION_STORE_PATH = "/tmp/.session_php_" . $syscfg['site_unique_name'];
	if (!file_exists($SESSION_STORE_PATH))
		mkdir($SESSION_STORE_PATH, 0777);
		
	session_save_path($SESSION_STORE_PATH);
	ini_set('session.gc_maxlifetime',(60*$syscfg['session_time']));
	ini_set('session.cookie_lifetime', (60*$syscfg['session_time']));
	session_name($syscfg['site_unique_name'].'_SESSION');
	session_start();
	/****************SESSION****************************/

?>