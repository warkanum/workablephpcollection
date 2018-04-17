<?php
//Database setup.

require_once('WPC/utils/DBC.php');
require_once('WPC/utils/CFG.php');
require_once('config.php'); //include configurations

//Load all the config file stuff into config class.
foreach ($syscfg as $key => $val)
{
	CFG::set($key, $val, true);	
}

if (isset($dbcfg))
{
	DBC::init();
	DBC::setServer($dbcfg['host']);
	DBC::setUser($dbcfg['user']);
	DBC::setPassword($dbcfg['passwd']);
	DBC::setDatabase($dbcfg['database']);
	DBC::setCharset(CFG::get('charset-encoding', 'utf8'));
	//the new update DBC class will automatically attempt to create a connection if one is required.
	//this reduces connections for static pages.
		
}

//For new php not to complain about data, we set the timezone
if (phpversion() > 5.1)
{
	date_default_timezone_set($syscfg['timezone']); //Africa/Harare
}


error_reporting(E_ALL);
ini_set('display_errors', ($syscfg['showerrors'] ? 'On' : 'Off'));
ini_set('log_errors', ($syscfg['logerrors'] ? 'On' : 'Off'));
ini_set('error_log', $syscfg['error_log']);
ini_set('memory_limit','384M');

?>