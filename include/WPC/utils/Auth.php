<?php
/*
	Site Login Class by Warkanum (H.Püth)
*/
require_once('CFG.php');
require_once('DBC.php');
require_once('Validation.php');
/*
	Provides user login facilities
*/
class Auth
{

	private static $passwd_encrypt = true;
	
	private static $userCan = array();
	private static $userID;
	private static $login_lifetime = 480; //minutes
	private static $PREFIX_ID = "site002sedtrade";
	private static $DB_TABLE = "auth";
	private static $DB_TABLE_AB = "auth_abilities";
	private static $DB_TABLE_LOG = "auth_log";
	private static $DB_TABLE_TOKEN = "auth_token";
	
	private function __construct() 
	{
	}
	
	//set the one way encryption on.
	public static function setEncryption($en_on)
	{
		if ($en_on)
			self::$passwd_encrypt = true;
		else
			self::$passwd_encrypt = false;
	}
	
	//Get the status of one way encryption.
	public static function getEncryption()
	{
			return self::$passwd_encrypt;
	}
	
	/* Check if there is a active session login. */
	public static function authenticate()
	{
		$userid = Validation::session(self::$PREFIX_ID.'auth_userid', 0);
		$authtime = Validation::session(self::$PREFIX_ID.'auth_time', 0);
		$sessionid = Validation::session(self::$PREFIX_ID.'auth_sessionid', 0);
		
		if ( ($userid > 0) && ($sessionid > 0))
		{
			$_SESSION[self::$PREFIX_ID.'auth_time'] = mktime(idate("H"), idate("i") + self::$login_lifetime);
			return true;
		}
		
		self::kill();
		
		return false;
	}
	
	/* Log in */
	public static function authorise($username, $pwd)
	{
		if (self::$passwd_encrypt)
			$password = md5($pwd);
		else
			$password = $pwd;
			
		$res = DBC::q(sprintf('SELECT id, username FROM %s WHERE (username = ?) AND (password = ?) AND (status = 1) LIMIT 1;', self::$DB_TABLE),
			 'ss', $username, $password);
		if (is_array($res))
		{ 
			foreach ($res as $row)
			{
				if (($row['id'] > 0) && (strlen($row['username']) > 2))
				{
					$sessionid = self::getDBSessionID($row['id'], true);
					
					if ($sessionid > 0)
					{
						$_SESSION[self::$PREFIX_ID.'auth_time'] = mktime(idate("H"), idate("i") + self::$login_lifetime);
						$_SESSION[self::$PREFIX_ID.'auth_userid'] = $row['id'];
						$_SESSION[self::$PREFIX_ID.'auth_sessionid'] = $sessionid;
						
						self::logAccess($row['id'], 1);
						
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/*Log authorise attempts*/
	private static function logAccess($userID, $statuscode = 1)
	{
		
		$ip = $_SERVER['REMOTE_ADDR'];	
		
		$res = DBC::q(sprintf('INSERT INTO %s VALUES(NULL, ?, NOW(), ?, ?);', self::$DB_TABLE_LOG),
			 'isi', $userID, $ip, $statuscode);
			
		
	}
	
	/* Logout:  Kill all login data and log the user out. */
	public static function kill()
	{
		if (isset($_SESSION[self::$PREFIX_ID.'auth_time']))
			unset($_SESSION[self::$PREFIX_ID.'auth_time']);
			
		if (isset($_SESSION[self::$PREFIX_ID.'auth_userid']))
			unset($_SESSION[self::$PREFIX_ID.'auth_userid']);
			
		if (isset($_SESSION[self::$PREFIX_ID.'auth_sessionid']))
			unset($_SESSION[self::$PREFIX_ID.'auth_sessionid']);
	}
	
	/*Get or generate a session id to/from database*/
	public static function getDBSessionID($userid, $generate = false)
	{
		if (!$generate)
		{
			$res = DBC::q(sprintf('SELECT sessionid FROM %s WHERE (id = ?)', self::$DB_TABLE), 'i', $userid);
			if (is_array($res))
			{
				foreach ($res as $row)
				{
					if ($row['sessionid'] > 0)
						return $row['sessionid'];
				}
			}
			
			return 0;
		} 
		else 
		{
			srand(time());
			$sessionID = "";
			for ($i = 0; $i < 10; $i++)
			{
				$sessionID .= sprintf('%s', rand(0, 9));
			}
			
			$res = DBC::q(sprintf('UPDATE %s SET sessionid = ? WHERE (id = ?)', self::$DB_TABLE), 'ii', $sessionID, $userid);
			
			return $sessionID;
		}
	}
	
	/* Get the abilities the user can do by name. (access) */
	public static function can($what)
	{
		$userid = Validation::session(self::$PREFIX_ID.'auth_userid', 0);
		if ($userid > 0)
		{
			$res = DBC::q(sprintf('SELECT value FROM %s WHERE (user_id = ?) AND (ability = ?) 
				AND ((expire > UNIX_TIMESTAMP(now()) ) OR (expire = 0)) LIMIT 1', self::$DB_TABLE_AB), 'is', $userid, $what);
			
			if (is_array($res) && !DBC::hasError())
			{
				foreach ($res as $row)
				{
					if (isset($row['value']) && ($row['value'] > 0))
					{
						return $row['value'];
					}
				}
			}
			
		}
		
		return 0;
	}
	
	/* Add Ability for a user and returns the id. */
	public static function ability_add($user_id, $name, $value, $expire = 0)
	{
		if ($user_id > 0)
		{
			$res = DBC::q(sprintf('INSERT INTO %s (user_id, ability, value, expire) 
				VALUES (?, ?, ?, ?);'
				, self::$DB_TABLE_AB), 'isii', $user_id, $name, $value, $expire);
			
			if (!DBC::hasError())
			{
				return DBC::lastID();
			}
			
		}
		
		return 0;
	}
	
	/* Logoutand clear the sessions */
	public static function logout()
	{
		$good = false;
		if (isset($_SESSION[self::$PREFIX_ID.'auth_time']))
		{
			unset($_SESSION[self::$PREFIX_ID.'auth_time']);
			$good = true;
		}
		if (isset($_SESSION[self::$PREFIX_ID.'auth_userid']))
		{
			unset($_SESSION[self::$PREFIX_ID.'auth_userid']);
			$good = $good ? true : false;
		}
		if (isset($_SESSION[self::$PREFIX_ID.'auth_sessionid']))
		{
			unset($_SESSION[self::$PREFIX_ID.'auth_sessionid']);
			$good = $good ? true : false;
		}
		return $good;		
	}
	
	/* Get user datas */
	public static function userdata()
	{
		if (self::authenticate())
		{
			if (isset($_SESSION[self::$PREFIX_ID.'auth_userid']))
			{
				$res = DBC::q(sprintf('SELECT id, username, sessionid, email, firstname, lastname, regdate, profile_image 
				 FROM %s WHERE (id = ?) LIMIT 1;', self::$DB_TABLE),
					 'i', $_SESSION[self::$PREFIX_ID.'auth_userid']);
					 
				foreach ($res as $row)
				{
					return $row;
				}
			}
		}
		
		return NULL;
	}
	
	
	/* Update the user data */
	public static function update_userdata($username, $pwd, $email, $firstname, $lastname, $profile_image)
	{
		if (self::$passwd_encrypt)
			$password = md5($pwd);
		else
			$password = $pwd;
				
		if (isset($_SESSION[self::$PREFIX_ID.'auth_userid']))
		{
			$userid = $_SESSION[self::$PREFIX_ID.'auth_userid'];
			if (strlen($pwd) > 1)
			{
				
				$res = DBC::q(sprintf('UPDATE %s SET username = ?, password = ?, email = ?, firstname = ?, lastname = ?, profile_image = ? WHERE (id = ?) LIMIT 1;', self::$DB_TABLE),
				 'ssssssi', $username, $password, $email, $firstname, $lastname, $profile_image, $userid);
			}
			else
			{
				$res = DBC::q(sprintf('UPDATE %s SET username = ?, email = ?, firstname = ?, lastname = ?, profile_image = ?
				 WHERE (id = ?) LIMIT 1;', self::$DB_TABLE),
				 'sssssi', $username, $email, $firstname, $lastname, $profile_image, $userid);
			}
			
			if (!DBC::hasError())
				return true;
		}
		
		return false;
	}
	
	/* Create the user data */
	public static function create_userdata($username, $pwd, $email, $firstname, $lastname, $profile_image, $status = 0)
	{
		if (self::$passwd_encrypt)
			$password = md5($pwd);
		else
			$password = $pwd;
			
		//check username
		$arr0 = DBC::q(sprintf("SELECT 'hasuser' FROM %s WHERE username = ?;", self::$DB_TABLE), 's', $username);
		
		if ((count($arr0) > 0) && isset($arr0[0]) && isset($arr0[0]['hasuser']))
			return false;
			
		if (true)
		{
			$res = DBC::q(sprintf('INSERT INTO %s SET username = ?,password = ?, email = ?, firstname = ?, lastname = ?, profile_image = ?, regdate = NOW(), status = ?;', 
				self::$DB_TABLE),'ssssssi', $username, $password, $email, $firstname, $lastname
				, $profile_image, $status );
				
			$userID = DBC::lastID();
			$ip = $_SERVER['REMOTE_ADDR'];	
		
			$res = DBC::q(sprintf('INSERT INTO %s VALUES(NULL, ?, NOW(),ADDDATE(NOW(), 2), ?, 1);', 
				self::$DB_TABLE_TOKEN), 'is', $userID, $ip);

			if (!DBC::hasError())
				return $userID;
		}
		
		return false;
	}
	
	
	/* Returns an array of users with array of table columns except password column */
	public static function getAllUsers()
	{
		$users = array();
		$ar_usrs = DBC::qq(sprintf('SELECT id, username, sessionid, email, firstname
				, lastname, regdate, profile_image FROM %s;', self::$DB_TABLE));
						
		foreach ($ar_usrs as $r)
		{
			$users[] = $r;
		}
		
		return $users;
	}
	
	
} 
?>