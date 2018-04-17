<?php
require_once('DBC.php');
require_once('Validation.php');
/*
	Site config class by Warkanum (H.Puth)
	(CFG)
*/
class CFG 
{
	private static $filename; 
	private static $_user = array();
	private static $_global = array();  //Ghost settings will not be loaded or stored to file
	private	static $tablename = "config";
	/*
		CREATE TABLE IF NOT EXISTS `config` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Config ID',
		  `name` varchar(255) NOT NULL COMMENT 'Config Name',
		  `value` varchar(2048) NOT NULL COMMENT 'Config Value',
		  `description` varchar(4096) DEFAULT NULL COMMENT 'Config Description',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `name` (`name`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Configuration Table' AUTO_INCREMENT=2 ;
	*/
	
	public function __construct() { 
	}
	/* Sets the SQL table name used for config storage */
	public static function setTableName($name) 
	{
		self::$tablename = $name;
	}
	/* Get the config string, params: name of config */
	public static function get($name, $default = "") 
	{
		if (isset(self::$_global[$name]))
		{
			return self::$_global[$name];
		}
		if (!isset(self::$_user[$name]))
		{
			//printf("<b>Site Warning: </b> Could not find configuration element %s. FILE: %s LINE: %s <br /> \n", $name, __FILE__, __LINE__);
			return $default;
		}
		return self::$_user[$name];
	} 
	/* Get all the config strings as array */
	public static function getAll() 
	{
		return array_merge(self::$_global, self::$_user);
	}
	
	/* Get the user config strings as array */
	public static function getAllUser() 
	{
		return self::$_user;
	}
	/* Set the local config string, params: name, value, override */
	public static function set($name, $value, $override = 0) 
	{
		if (isset(self::$_user[$name]) && !$override) 
		{ 
			return;
		}
		else 
		{ 
			self::$_user[$name] = $value; 
		} 
	}
	/* Set the global config string, params: name, value, override */
	public static function setG($name, $value, $override = 0) 
	{
		if (isset(self::$_global[$name]) && !$override) { 
			return;
		}
		else { 
			self::$_global[$name] = $value;
		} 
	}
	/* Load the configureations from cfg/text file */
	public static function loadfile($file, $override = 1) 
	{
		if (file_exists($file))
		{
			self::$filename = $file;
			$fileHandle = fopen($file, "r");
			if ($fileHandle) 
			{
				while (($txtbuffer = fgets($fileHandle)) !== false) 
				{
					$pos = strpos($txtbuffer, "#");
					//$len = strlen($txtbuffer);
					if ($pos > 0)
						$txtbuffer = substr($txtbuffer, 0, $pos);
					
					$linepieces = explode("=", $txtbuffer);
					
					if (count($linepieces) >= 2)
					{
						self::set(trim($linepieces[0]), trim($linepieces[1]), $override);
					}
				}
				
				if (!feof($fileHandle))
					printf("Config failed to read completely! (file: %s)", $file);
			
				fclose($fileHandle);
			}
		}
	}
	
	/* Write the configureations to cfg/text file */
	public static function writefile($file = "") 
	{
		$ifile = self::$filename;
		if (strlen($file) > 2)
			$ifile = $file;
		if (strlen($ifile) > 2)
		{
			$fileHandle = fopen($file, "w");
			if ($fileHandle) 
			{
				foreach (self::$_user as $name => $value)
				{
					fwrite($fileHandle, sprintf("%s=%s\n", $name, $value));
				}
				fclose($fileHandle);
			}
		}
	}
	/*Load configuration from database*/
	public static function loadSQL()
	{
		if (DBC::isConnected())
		{
			
			$ar = DBC::q(sprintf("SELECT name, value, description FROM %s WHERE (user_id IS NULL) ORDER BY name, id;", self::$tablename));
			
			foreach($ar as $row)
			{
				self::set($row['name'], $row['value'], 1);
			}
		}
	}
	/*Save configuration to database*/
	public static function saveSQL()
	{
		if (DBC::isConnected())
		{
			foreach (self::$_user as $name => $value)
			{
				$ar = DBC::q(sprintf("INSERT INTO `%s` (name, value, user_id) VALUES (?,?, NULL) 
				 ON DUPLICATE KEY UPDATE value = ? ;", 
					self::$tablename), "sss", $name, $value, $value);
			}
		}
	}
	
	
	/**
	* Processes the feedback of the form
	*/
	public static function process()
	{
		//Also process the feedback from this form
		if ((Validation::request("btnSubmitSettings") != "") && (Validation::request("settings_form_name") != "") 
		&& is_array(Validation::request("settings_form_name")) && (Validation::request("settings_form_value") != "") 
		&& is_array(Validation::request("settings_form_value"))  )
		{
			foreach (Validation::request("settings_form_name") as $key => $name)
			{
				$vd = Validation::request("settings_form_value");
				if (isset($vd[$key]))
				{
					CFG::set($name, $vd[$key], 1);
				}
			}
			
			CFG::saveSQL(); //We must save the settings back to SQL
			//Now reload again
			header(sprintf("refresh:0.3;url=%s", basename($_SERVER['PHP_SELF']) ));
		}
	}
	
	/**
	* Show a form to edit the configuration with
	*/
	public static function showEditor()
	{
		
		$frm = new FormConstruct();
		$frm->saveHTML(true);
		$frm->begin("settings_form", "settings_form", basename($_SERVER['PHP_SELF']), "post");
		$frm->addHTML('<table width="100%;">');
		
		foreach (self::getAllUser() as $name => $value)
		{
			$frm->addHTML('<tr><td style="align: right;">');
			$frm->labelFor("settings_form_value", $name."", "lblName");
			$frm->input("hidden", "settings_form_name[]", "settings_form_name", $name);
			$frm->addHTML('</td><td style="align: left;">');
			$frm->input("text", "settings_form_value[]", "settings_form_value", $value, array("class" => "stdTextBox", "size" => "80"));
			$frm->addHTML("</td></tr>");
		}
		$frm->addHTML("</tr><td></td><td>");
		$frm->input("submit", "btnSubmitSettings", "btnSubmitSettings", "Update", array("class" => "smallButton"));
		$frm->addHTML("</td></tr>");
		$frm->addHTML("</table>");
		
		
		$frm->end();
		//print('<XMP>');
		$frm->getHTML(true);
		//print('</XMP>');
	}
	
} // end Cfg class
?>