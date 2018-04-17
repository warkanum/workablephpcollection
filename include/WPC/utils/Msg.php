<?php

/**
* Provides a abstract static class to handle errors messages.
* A custom errors messages handler
* Static Class (Msg)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class Msg
{
	
	private static $lastErr;
	private static $allErr;
	private static $print_on_add = false; //Will product immediate java based messages.
	public static $CSS_CLASS_PREFIX = "ErrMsg_";
	
	/**
	*	Setup the object.
	*	Must be Initially called.
	*	@access public
	*/
	public static function init() 
	{
		self::$lastErr = "";
		self::$allErr = array();
		
		//load all the session saved messages.
		$session_name = self::$CSS_CLASS_PREFIX;
		$last_state_data = Validation::session($session_name."LAST_STATE", NULL);
		$last_state_lifetime = Validation::session($session_name."LAST_STATE_LIFETIME", 0);
		if ($last_state_data)
		{
			$ar_messages = unserialize($last_state_data);
			foreach ($ar_messages as $ar_msg)
			{
				self::add($ar_msg['msg'], $ar_msg['title'], $ar_msg['type']);
			}
			
			if ($last_state_lifetime > 0)
			{
				$last_state_lifetime--;
				$_SESSION[$session_name."LAST_STATE_LIFETIME"] = $last_state_lifetime;	
			}
			else
			{
				unset($_SESSION[$session_name."LAST_STATE_LIFETIME"]);
				unset($_SESSION[$session_name."LAST_STATE"]);	
			}
		}
	}
	
	/**
	*	Saves the last message to session for use on next loaded page
	*	@param $lifetime_pages int Lifetime of page loads this messages must be preserved.
	*	@access public
	*/
	public static function session_save($lifetime_pages = 0) 
	{
		$session_name = self::$CSS_CLASS_PREFIX;
		$session_data = serialize(self::$allErr);
		$_SESSION[$session_name."LAST_STATE"] = $session_data;
		$_SESSION[$session_name."LAST_STATE_LIFETIME"] = $lifetime_pages;
	}
		
	/**
	*	Add a error message to the queue
	*	
	*	@param $msg String that represents a error message.
	*	@param $title String that represents the message title.
	*	@param $type String that represents the message class or type.
	*	@access public
	*	@return Boolean True if connected, false if not.
	*/
	public static function add($msg, $title = "", $type = "error") 
	{
		self::$lastErr = array('msg' => $msg, 'title' => $title, 'type' => $type);
		
		if (self::$print_on_add) //print the message when adding it. Produces immediate messages
			self::printLastJs(self::$lastErr);
		
		array_push(self::$allErr, self::$lastErr);
	}
	
	/**
	*	Get the last error
	*	@access public
	*	@return Get the last err message added
	*/
	public static function getLast()
	{
		return self::$lastErr;
	}
	
	/**
	*	Print the last error
	*	@access public
	*/
	public static function printLast()
	{
		if (self::$lastErr)
		{
			printf('<div id="error_wrapper" class="%s_wrapper" >', self::$CSS_CLASS_PREFIX);
			printf('<span class="%s%s">%s: %s</span><br />', self::$CSS_CLASS_PREFIX,
				 self::$lastErr['type'], self::$lastErr['title'], self::$lastErr['msg']);
			print('</div>');
		}
	}
	
	/**
	*	Get all error messages by type
	*	All by default
	*	@access public
	*	@return Array of error messages
	*/
	public static function getAll($type = 'any')
	{
		$ar = array();
		foreach (self::$allErr as $e)
		{
			if ($e['type'] == $type)
				$ar[] = $e;	
			elseif ($type == 'any')
				$ar[] = $e;
		}
	}
	
	/**
	*	Print all error messages by type
	*	All by default
	*	@access public
	*/
	public static function printAll($type = 'any')
	{
		$ar = array();
		printf('<div id="error_wrapper" class="%s_wrapper" >', self::$CSS_CLASS_PREFIX);
		foreach (self::$allErr as $e)
		{
			if ($e['type'] == $type)
				printf('<span class="%s%s">%s: %s</span><br />', self::$CSS_CLASS_PREFIX, $e['type'], $e['title'], $e['msg']);
			elseif ($type == 'any')
				printf('<span class="%s%s">%s: %s</span><br />', self::$CSS_CLASS_PREFIX, $e['type'], $e['title'], $e['msg']);
		}
		print('</div>');
	}
	
	/**
	*	Print all error messages by type
	*	Prints them as java.
	*	All by default
	*	@access public
	*/
	public static function printAllj($type = 'any')
	{
		$ar = array();
		printf('<script type="text/javascript" >', self::$CSS_CLASS_PREFIX);
		foreach (self::$allErr as $e)
		{
			if ($e['type'] == $type)
				printf('WPC_ErrMsg("%s", "%s", "%s");', $e['msg'], $e['title'], $e['type']);
			elseif ($type == 'any')
				printf('WPC_ErrMsg("%s", "%s", "%s");', $e['msg'], $e['title'], $e['type']);
		}
		print('</script>');
	}
	
	
	/**
	*	Print a message as javascript. 
	*	@access public
	*/
	public static function printLastJs($messageAr)
	{
		if (is_array($messageAr))
		{
			printf('<script type="text/javascript" >
			WPC_ErrMsg("%s", "%s", "%s");
			</script>', $messageAr['msg'], $messageAr['title'], $messageAr['type']);
		}
	}
	
	/**
	*	Set the print on add flag.
	*	With this enabled, a messages will be printed as soon as it is added. 
	*	Will print the message as javascript.
	*	@param $poa Boolean Set to true to enabled.
	*	@access public
	*/
	public static function setPrintOnAdd($poa = false)
	{
		self::$print_on_add = $poa;	
	}
}

?>