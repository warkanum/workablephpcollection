<?php

/**
* Provides Path related functions. To get the current working path and convert between relative paths.
* 
* Static Class (Path)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 2.0
* @since 1
* @access public
* @updated: 2012-07-25
*/
abstract class Path
{
	/**
	*	Get the current url from the server.
	*	
	*	@param $queryStr Boolean Include the query string
	*	@return String The full url
	*	@access public
	*/
	public static function getCurrentURL($queryStr = false)
	{
		
		$url = "";
		if ($queryStr)
			$url = sprintf("%s%s%s", self::getProtocol(), $_SERVER['SERVER_NAME'],
				$_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING']);
		else
			$url = sprintf("%s%s%s", self::getProtocol(), $_SERVER['SERVER_NAME'],$_SERVER['REQUEST_URI']);
			
		return $url;
	}
	
	/**
	*	Get the local path related to the root directory.
	*	
	*	@return String The relative path
	*	@access public
	*/
	public static function getRelativeRoot()
	{
		/*
		$fullpath = realpath(__FILE__);
		$drpath = realpath($_SERVER['DOCUMENT_ROOT']);
		$ppath = str_replace($drpath,"",$fullpath); //strip it and return only the relative path
		$nrpath = "include/WPC/utils/".basename(__FILE__);
		$ppath = str_replace($nrpath,"",$ppath); //strip path to this file and get relative root
		return self::cleanPath($ppath);
		*/
		
		/* The new alias safe way, but this file must be relative depth to root */
		$scriptname = $_SERVER['SCRIPT_NAME'];
		$nrpath = __FILE__;
		$nrpath = dirname(dirname(dirname(dirname($nrpath)))); //since this file is in /include/WPC/utils/Path.php
		$currentfolder = basename($nrpath);
		if (substr_count($scriptname,$currentfolder) <= 0)
			return "";
			
		$endpos = strpos($scriptname, $currentfolder) + strlen($currentfolder);
		$npath = substr($scriptname, 0, $endpos) . "/";
	
		return self::cleanPath($npath);
	}
	
	/**
	*	Clean a path string by stripping the dubble or repeated back slashes
	*	@param $path String The path to clean
	*	@return String The cleaned path
	*	@access public
	*/
	public static function cleanPath($path)
	{
		return str_replace('//','/',$path);		
	}
	
	/**
	*	Get the web path derived from the relative root path
	*	@return String The web path of the relative root
	*	@access public
	*/
	public static function getLocalRoot()
	{
		$url = sprintf('%s/%s', realpath($_SERVER['DOCUMENT_ROOT']), self::getRelativeRoot());
		return self::cleanPath($url);		
	}
	
	/**
	*	Get the web path derived from the relative root path
	*	@param $include_protocol Boolean Should the http:// part be included?
	*	@return String The web path of the relative root
	*	@access public
	*/
	public static function getWebPath($include_protocol = true)
	{
		
		if ($include_protocol)
			$url = sprintf('%s%s%s', self::getProtocol(), $_SERVER['SERVER_NAME'], self::getRelativeRoot());
		else
			$url = sprintf('%s%s', $_SERVER['SERVER_NAME'], self::getRelativeRoot());
		
		//check last lash and remove it
		$lastChar = substr($url, strlen($url)-1, strlen($url));
		if (($lastChar == "\\") || ($lastChar == "/"))
			$url = substr($url, 0, strlen($url)-1);
			
		return $url;		
	}
	
	/**
	*	Get the protocol string (url prefix) 
	*	@return String Protocol/Prefix string
	*	@access public
	*/
	public static function getProtocol()
	{
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $protocol;	
	}
	
}

?>