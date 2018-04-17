<?php

/**
* Provides a format validation and other request related functions
* Handles validation and requests
* Static Class (Validation)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class Validation 
{
		
	/**
	*	Check if a the given variable is a number and if not, return the default.
	*	
	*	@param $number The variable containing a number to check.
	*	@param $default Default number to return if not a valid number
	*	@return Numeric Return the number
	*	@access public
	*/
	public static function num_or($number, $default = 0) 
	{
		if (isset($number) && is_numeric($number))
			return $number;
		else
			return $default;
	}
	
	/**
	*	Get the request and check if it is valid, then return the data.
	*	
	*	@param $index The name of the REQUEST/POST/GET to get.
	*	@return The data from the request
	*	@access public
	*/
	public static function request($index, $default = "")
	{
		if (isset($_POST[$index]))
			return $_POST[$index];
		
		if (isset($_GET[$index]))
			return $_GET[$index];
			
		return $default;
	}
	
	/**
	*	Get the session and check if it is valid, then return the data.
	*	
	*	@param $index The name of the session variable to get.
	*	@return The data from the session
	*	@access public
	*/
	public static function session($index, $default = "")
	{
		if (isset($_SESSION[$index]))
			return $_SESSION[$index];
			
		return $default;
	}
	
	/**
	*	Get the cookies and check if it is valid, then return the data.
	*	
	*	@param $index The name of the cookie variable to get.
	*	@return The data from the cookie
	*	@access public
	*/
	public static function cookie($index, $default = "")
	{
		if (isset($_COOKIE[$index]))
			return $_COOKIE[$index];
			
		return $default;
	}
	
	/**
	*	Send header string to the client browser to navigate to another page when the timeout is reached.
	*	
	*	@param $url The new location to navigate to
	*	@param $timeout The amount of seconds to wait before redirecting
	*	@return The data from the request
	*	@access public
	*/
	public static function header_redirect($url, $timeout = 1)
	{
		header(sprintf("refresh:%s;url=%s", $timeout, $url)); 
	}
	
	/**
	*	Find a string within a string and return true if it exists.
	*	
	*	@param $src String The source string
	*	@param $what String The String to find
	*	@return Boolean True if the string is found, false if not
	*	@access public
	*/
	public static function strfind($src, $what)
	{
		$pos = strpos($src, $what);
		if ($pos === false)
			return false;
		else 
			return true;
	}
	
	/**
	*	Check if a variable is blank.
	*	
	*	@param $input Variable The input to check
	*	@return Boolean True if the the variable is blank
	*	@access public
	*/
	public function is_blank($input)
	{
		if (!isset($input)) return true; //it is not set, so blank.
		
		if ((trim($input) == "")  || (trim($input) == " ") 
			|| ($input == "0") || ($input == 0) || ($input == NULL) )
		{
			return true;
		}
		return false;
	}
	
	/**
	*	Convert a numeric to a currency format
	*	2 decimal places
	*	
	*	@param $price Numberic The number to convert
	*	@param $prefix String Prefix of the number
	*	@param $suffix String Suffix of the number
	*	@return String Formatted Number
	*	@access public
	*/
	public static function currency($price, $prefix = "R ", $suffix = "")
	{
		$price = sprintf("%01.2f",$price);
		return sprintf("%s%s%s",$prefix,number_format($price, 2, '.', ' '), $suffix); 
	}
	
	/**
	*	Check if a valid phone number
	*	
	*	
	*	@param $number Phone number to check
	*	@return Boolean True if the phone number if valid else false.
	*	@access public
	*/
	public static function val_phone($number)
	{
		if (preg_match('/^[0-9]{1,}$/', $number))
			return FALSE;
		else
			return TRUE;
	}
	
	/**
	*	Check if a valid email
	*	
	*	
	*	@param $email The email address to check
	*	@return Boolean True if valid else false.
	*	@access public
	*/
	public static function val_email($email)
	{
		$results = preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/',$email);
		if($results > 0) 
			return true;
		return false;
	}
	
	/**
	*	Strip a string to extract only the numbers
	*	
	*	@param $data The object to process
	*	@return Numeric The number extracted from the string
	*	@access public
	*/
	public static function only_numbers($data)
	{
		$data= ereg_replace("[^0-9]", "", $data);
		return $data;
	}
	
	/**
	*	Generate a random string with the specified length
	*	
	*	@param $length The length of the string to generate
	*	@return String The random generated string.
	*	@access public
	*/
	public static function random_str($length = 10)
	{
		srand(time(NULL));
		$str = "";
		$string = "abcdefghijklmnopqrstuvwxyz0123456789";
		for($i=0;$i<$length;$i++){
			$pos = rand(0,strlen($string));
			$str .= $string{$pos};
		}
		return $str;
	}
	
	/**
	*	Generate a unique string based on client info
	*
	*	@param $do_md5 Generate a MD5 string instead of raw string.
	*	@return String The random generated string.
	*	@access public
	*/
	public static function random_client_str($do_md5 = true)
	{
		srand(time(NULL));
		$str = "";
		$stamp = date("Ymdhis");
		$ip = $_SERVER['REMOTE_ADDR'];
		$ip2 = str_replace(".", "", $ip);
		$uid = uniqid(rand(),1);
		$uid = strip_tags(stripslashes($uid)); 
		$uid = str_replace(".","",$uid); 
		$uid = strrev(str_replace("/","",$uid)); 

		if ($do_md5)
			$str .= md5($uid . $ip2 . $stamp);
		else
			$str .= $uid . $ip2 . $stamp;
	
		return $str;
	}
	
	
	/**
	*	Generate a default date format string used by all WPC apps from a unix integer
	*	
	*	@param $unix_time bigint The unix time (optional now)
	*	@param $include_time boolean Include the time (optional include time)
	*	@return String The new date
	*	@access public
	*/
	public static function datetime_from_unix($unix_time = 0, $include_time = true)
	{
		if ($unix_time <= 0)
			$unix_time = time();
		
		if ($include_time)
			$nicetime = date('Y-m-d H:i:s', $unix_time);
		else
			$nicetime = date('Y-m-d', $unix_time);
			
		return $nicetime;
	}
	
	/**
	*	Generate a default date format string used by all WPC apps from a datetime string
	*	
	*	@param $string_time string String that represents the time
	*	@param $include_time boolean Include the time
	*	@return String The new date
	*	@access public
	*/
	public static function datetime_from_str($string_time = "", $include_time = true)
	{
		if (strlen($string_time) <= 1)
			$unix_time = time();
		else
			$unix_time = strtotime($string_time);
			
		if ($unix_time == false)
			$unix_time = time();
		
		if ($include_time)
			$nicetime = date('Y-m-d H:i:s', $unix_time);
		else
			$nicetime = date('Y-m-d', $unix_time);
			
		return $nicetime;
	}
	
	
	/**
	*	Check if a specific value is NULL and substitutes and returns a default value
	*	If the given value is not NULL, that value is returned.
	*	
	*	@param $value The reference to check
	*	@param $default The default value
	*	@return String The new value
	*	@access public
	*/
	public static function if_null($value, $default = "")
	{
		if ($value == NULL)
		{
			return $default;	
		}
		else
		{
			return $value;	
		}
	}
	
	
	/**
	*	Translate a value to null if it is empty
	*	If the given value is emptry,  NULL is returned.
	*	
	*	@param $value The reference to check
	*	@param $canbe Addition value considered as a null value
	*	@return String The new value
	*	@access public
	*/
	public static function empty_to_null($value, $canbe = "")
	{
		if (strlen($value) == 0)
			return NULL;
		elseif ($value === NULL)
			return NULL;
		elseif ($value == $canbe)
			return NULL;	
		else
			return $value;	
	}
	
	
}

?>