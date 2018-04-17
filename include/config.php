<?php
	global $dbcfg;
	$dbcfg = array();
	$dbcfg['socket'] = '127.0.0.1:3306';
	$dbcfg['host'] = '127.0.0.1';
	$dbcfg['port'] = '3306';
	$dbcfg['user'] = '';
	$dbcfg['passwd'] = '';
	$dbcfg['database'] = '';
	
	
	global $syscfg;
	$syscfg = array();
	
	$syscfg['charset-encoding'] = 'utf8';
	$syscfg['showerrors'] = true;
	$syscfg['logerrors'] = true;
	$syscfg['error_log'] = $_SERVER['DOCUMENT_ROOT']. '/php_error_log.txt';
	$syscfg['site_title'] = 'Site';
	$syscfg['site_unique_name'] = 'site1';
	$syscfg['session_time'] = 7200; //minutes (=1week)
	$syscfg['timezone'] = "Africa/Johannesburg";
	$syscfg['site_name_nice'] = 'Site';
	$syscfg['root_home_page'] = 'http://www.site.net';
	
	
?>