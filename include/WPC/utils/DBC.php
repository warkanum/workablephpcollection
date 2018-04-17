<?php

/**
* Provides a data connection object via MySQLi
* Handles mysql connection and queries
* Static Class (DBC)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class DBC 
{
	
	private static $db_server = array();
	private static $db_database = array();
	private static $db_user = array();
	private static $db_passwd = array();
	private static $db_charset = "";
	private static $conn_id = 1; //default connection id is 1
	private static $conn_list = array(); //list all the connections set
	
	private static $debug;
	private static $connection;
	private static $openconnection;
	private static $has_error; 
	private static $errors;
	
	/**
	*	Setup the object.
	*	Must be Initially called.
	*	@access public
	*/
	public static function init() 
	{
		self::reset(); //reset everything.
		
	}
	
	/**
	*	Resets the database variables.
	*	Initially called.
	*	@access public
	*/
	public static function reset() 
	{ 
		self::$connection = array();
		self::$openconnection = array();
		self::setConnectionID(self::$conn_id);
		
		self::$db_server[self::$conn_id] = "";
		self::$db_database[self::$conn_id] = "";
		self::$db_user[self::$conn_id] = "";
		self::$db_passwd[self::$conn_id] = "";
		self::$debug = false;
		self::$has_error = false;
		self::$errors = array();
	}
	
	/**
	*	Get the status of the connection.
	*	
	*	@access public
	*	@return Boolean True if connected, false if not.
	*/
	public static function isConnected() 
	{
		if (self::$connection[self::$conn_id] && is_object(self::$connection[self::$conn_id]))
		{
			self::$openconnection[self::$conn_id] = true;
			return self::$openconnection[self::$conn_id];	
		}
		else
		{
			self::$openconnection[self::$conn_id] = true;
			return false;
		}
	}
	
	/**
	*	Get connection object if connected.
	*	(MySQLi object)
	*	@access public
	*	@return MySQLi Class Returns the MySQLi connection object class.
	*/
	public static function getConnection() 
	{
		if (!self::isConnected())
		{
			array_push(self::$errors, sprintf('DBC Error (%s, %s): Database connection not open!', __LINE__, basename(__FILE__)));
			return NULL;
		}
		return self::$connection[self::$conn_id];
	}
	
	/**
	*	Get connection object if connected.
	*	(MySQLi object)
	*	@access public
	*	@return MySQLi Class Returns the MySQLi connection object class.
	*/
	public static function hasError()
	{
		if (!self::isConnected())
		{
			self::$has_error = true;	
			array_push(self::$errors, sprintf('DBC Error (%s, %s): No connection has been created!', __LINE__, basename(__FILE__)));
		}
			
		return self::$has_error;	
	}
	
	/**
	*	Check the connection and create one if none exists
	*	(MySQLi object)
	*	@access public
	*	@return Nothing
	*/
	public static function check() 
	{
		if (self::$connection[self::$conn_id] && is_object(self::$connection[self::$conn_id]))
		{
			//connections exists. Do nothing.
		}
		else
		{
			self::create();
			
			//update the charset if specified.
			if (strlen(self::$db_charset) > 1)
				self::qq("SET NAMES ".self::$db_charset.";"); //set the database charset.
		}
	}
	
	/**
	*	Create the connection object.
	*	(MySQLi object)
	*	@param $override Boolean Overrides the current connection with a new one.
	*	@access public
	*	@return MySQLi Class Returns the MySQLi connection object class.
	*/
	public static function create($override = false) 
	{
		if (!self::isConnected() || $override)
		{
			self::$connection[self::$conn_id] = new mysqli(self::$db_server[self::$conn_id], self::$db_user[self::$conn_id], self::$db_passwd[self::$conn_id], self::$db_database[self::$conn_id]);
			self::$openconnection[self::$conn_id] = true;
			if (mysqli_connect_errno()) {
				self::$openconnection[self::$conn_id] = false;
				array_push(self::$errors, sprintf('DBC Error (%s, %s): Database connection failed, %s!',
					 __LINE__, basename(__FILE__), mysqli_connect_error()));
				self::$has_error = true;
			}
			else
			{
				self::$has_error = false;	
			}
		}
		else
		{
			array_push(self::$errors, sprintf('DBC Error (%s, %s): Connection already open!', __LINE__, basename(__FILE__)));
		}
	}
	
	/**
	*	Prepares a mysqli statement and return the statement.
	*	(MySQLi statement object)
	*	@param $override Boolean Overrides the current connection with a new one.
	*	@access public
	*	@return MySQLi Statement Returns the MySQLi statement object class.
	*/
	public static function st($query) 
	{
		$statement = NULL;
		self::check();
		if (self::$isConnected())
		{
			$statement = self::$connection[self::$conn_id]->prepare($query);
			if (!$statement || self::$connection[self::$conn_id]->errno)
			{
				array_push(self::$errors, sprintf('DBC Error (%s, %s): Statement error, %s',
				 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
				
				self::$has_error = true;	
			}
			else
			{
				self::$has_error = false;	
			}
		}
		return $statement;
	}
	
	/**
	*	Handle a statement error. Also return true if errors was found.
	*	@param $override 'MySQLi Statement' Statement object to check for error on.
	*	@access public
	*	@return Boolean Returns true if errors was found and false if not errors was found.
	*/
	public static function stError($statement) 
	{
		self::check();
		//make sure this is what we are looking for.
		if (self::$isConnected() && is_object($statement) && ($statement->errno))
		{
			if ($statement->errno)
			{
				array_push(self::$errors, sprintf('DBC Error (%s, %s): Statement error, %s',
				 __LINE__, basename(__FILE__), $statement->error));

				self::$has_error = true;
			}
			else
			{
				self::$has_error = false;
			}
				
			return true;
		}
		return false;
	}
	
	/**
	*	Get the last insert autoincrement value/id.
	*	@access public
	*	@return Int/float Return the id of the last inserted entry.
	*/
	public static function lastID()
	{
		self::check();
		if (self::$connection[self::$conn_id])
			return self::$connection[self::$conn_id]->insert_id;
			
		return 0;
	}
	
	/**
	*	Create a quick statement based query and return the results.
	*	@param $query Mysql query string.
	*	@param  Optional string with the binding types
	*	@param  Additional variables being bound.
	*	@access public
	*	@return Boolean Returns an array with the table data or total rows effected depending on the query type.
	*/
	public static function q($query) 
	{
		self::check();
		if (!self::isConnected()) return false;
		
        if ($st = self::$connection[self::$conn_id]->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_merge(array(func_get_arg(1)),
                    array_slice($x, 2));
                $args_ref = array();
                foreach($args as $k => &$arg) {
                    $args_ref[$k] = &$arg; 
                }
                call_user_func_array(array($st, 'bind_param'), $args_ref);
            }
            $st->execute();
 
            if ($st->errno) 
			{
				self::$has_error = true;
				array_push(self::$errors, sprintf('DBC Error (%s, %s): Statement error, %s',
					 __LINE__, basename(__FILE__), $st->error));
				 
              	if (self::$debug) 
				{
                	debug_print_backtrace();
             	}
             	return false;
            }
 
            if ($st->affected_rows > -1) 
			{
				self::$has_error = false;	
                return $st->affected_rows;
            }
			
            $params = array();
            $meta = $st->result_metadata();
            while ($field = $meta->fetch_field()) {
                $params[] = &$row[$field->name];
            }
            call_user_func_array(array($st, 'bind_result'), $params);
 
            $result = array();
            while ($st->fetch()) {
                $r = array();
                foreach ($row as $key => $val) {
                    $r[$key] = $val;
                }
                $result[] = $r;
            }
            $st->close(); 
			self::$has_error = false;
            return $result;
        } else {
			array_push(self::$errors, sprintf('DBC Error (%s, %s): Quick qeury error, %s',
					 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
					 
			self::$has_error = true;	 
			
            if (self::$debug) 
                debug_print_backtrace();
				
            return false;
        }
    }
	
	/**
	*	Run a quick query and return the results set
	*	@param $query Mysql query string.
	*	@access public
	*	@return Boolean Returns an array with the table data or total rows effected depending on the query type.
	*/
	public static function dbquery($query)
	{
		self::check();
		$result = NULL;
		
		if (self::isConnected())
		{
			$result = self::$connection[self::$conn_id]->query($query);
			if (self::$connection[self::$conn_id]->errno)
				array_push(self::$errors, sprintf('DBC Error (%s, %s): Query error, %s',
					 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
		}
		return $result;
	}
	
	/**
	*	Fetch assoc array from results set
	*	@param $results MySQLi Results object
	*	@access public
	*	@return Boolean Returns an array with the table data or NULL if no more rows.
	*/
	public static function dbfetch($results)
	{
		self::check();
		$arr = NULL;
		
		if (self::isConnected() && is_object($results))
		{
			$arr = $results->fetch_array(MYSQLI_BOTH);
		
			if (self::$connection[self::$conn_id]->errno)
				array_push(self::$errors, sprintf('DBC Error (%s, %s): dbfetch error, %s',
					 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
		}
		return $arr;
	}
	
	
	/**
	*	Fetch affected rows
	*	@param $results
	*	@access public
	*	@return Boolean Returns an array with the table data or NULL if no more rows.
	*/
	public static function dbrows($results)
	{
		self::check();
		$res = NULL;
		
		if (self::isConnected() && is_object($results))
		{
			$res = $results->num_rows;
		
			if (self::$connection[self::$conn_id]->errno)
				array_push(self::$errors, sprintf('DBC Error (%s, %s): dbfetch error, %s',
					 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
		}
		return $res;
	}
	
	/**
	*	Free the results
	*	@param $results
	*	@access public
	*	@return Boolean Returns an array with the table data or NULL if no more rows.
	*/
	public static function dbfree($results)
	{
		self::check();
		$res = NULL;
		
		if (self::isConnected() && is_object($results))
		{
			$res = $results->free_result();
		
			if (self::$connection[self::$conn_id]->errno)
				array_push(self::$errors, sprintf('DBC Error (%s, %s): dbfetch error, %s',
					 __LINE__, basename(__FILE__), self::$connection[self::$conn_id]->error));
		}
		return $res;
	}

	
	/**
	* Basically a combination of dbquery and dbfetch.
	*	@param $results Array with rows and columns
	*	@access public
	*	@return Array Returns an array with the table data or total rows effected depending on the query type.
	*/
	public static function qq($query)
	{
		self::check();
		$arr = array();
		$results = self::dbquery($query);
		while (($rows = self::dbfetch($results)) != NULL)
		{
			$arr[] = $rows;
		}
		
		return $arr;
	}
	
	/**
	*	Get a list of errors generated by the database connection
	*	@access public
	*	@return Array List of error strings
	*/
	public static function getErrors()
	{
		return self::$errors;
	}
	
	/**
	*	Print a list of errors generated by the database connection
	*	@access public
	*	@return Array List of error strings
	*/
	public static function printErrors()
	{
		foreach (self::$errors as $e)
		{
			printf('<li>%s</li>', $e);
		}
	}
	

	
	/**
	*	Close database connection
	*	@access public
	*/
	public static function close() 
	{
		self::$openconnection[self::$conn_id] = false;
		if (self::$connection[self::$conn_id]){
			self::$connection[self::$conn_id]->close();
			self::$connection[self::$conn_id] = NULL;

			self::$openconnection[self::$conn_id] = false;
			//unset(self::$connection[self::$conn_id]);
		}
	}
	
	/**
	*	Closes all active database connection
	*	@access public
	*/
	public static function closeAll() 
	{
		foreach (self::$conn_list as $conn_id)
		{
			DBC::setConnectionID($conn_id);
			self::close();	
		}
	}
	
	/**
	*	Get the username
	*	@access public
	*	@return String Database username
	*/
	public static function getUser() 
	{
		return self::$db_user[self::$conn_id];
	}
	
	/**
	*	Get the server socket (ip:port)
	*	@access public
	*	@return String Database server socket
	*/
	public static function getServer() 
	{
		return self::$db_server[self::$conn_id];
	}
	
	/**
	*	Get the database name
	*	@access public
	*	@return String Database name
	*/
	public static function getDatabase() 
	{
		return self::$db_database[self::$conn_id];
	}
	
	/**
	*	Set the database user
	*	@access public
	*	@param $value The username
	*/
	public static function setUser($value) 
	{
		self::$db_user[self::$conn_id] = $value;
	}
	
	/**
	*	Set the database password
	*	@access public
	*	@param $value The password
	*/
	public static function setPassword($value) 
	{
		self::$db_passwd[self::$conn_id] = $value;
	}
	
	/**
	*	Set the database server socket (ip:port)
	*	@access public
	*	@param $value The server socket string (ip:port) 
	*/
	public static function setServer($value) 
	{
		self::$db_server[self::$conn_id] = $value;
	}
	
	/**
	*	Set the database name
	*	@access public
	*	@param $value The database name 
	*/
	public static function setDatabase($value) 
	{
		self::$db_database[self::$conn_id] = $value;
	}
	
	/**
	*	Enabled debugging information
	*	@access public
	*	@param $debug Boolean Enable debugging
	*/
	public static function setDebug($debug)
	{
		self::$debug = $debug;
	}
	
	/**
	*	Set the database charset for this connection.
	*	Settings this to a empty string will make the connection use the default charset.
	*	@access public
	*	@param $debug Boolean Enable debugging
	*/
	public static function setCharset($charset)
	{
		self::$db_charset = $charset;
	}
	
	/**
	*	Get the database charset for this connection.
	*	@access public
	*	@return String Charset
	*/
	public static function getCharset() 
	{
		return self::$db_charset;
	}
	
	/**
	*	Set the connection identifier.
	*	If you want to use different connections. The identifier of that connection must be set.
	*   Remeber to set server, username and password every time you change this unless you change back to existing connection.
	*	Default connection will always be 1
	*	@access public
	*	@param $identifier Integer The uniquely identifies the connection.
	*/
	public static function setConnectionID($identifier)
	{
		if ($identifier < 1)
			$identifier = 1;
					
		self::$conn_id = $identifier;
		
		if (!isset(self::$openconnection[self::$conn_id]))
			self::$openconnection[self::$conn_id] = NULL;
			
		if (!isset(self::$connection[self::$conn_id]))
			self::$connection[self::$conn_id] = NULL;
			
		if (!isset(self::$db_server[self::$conn_id]))	
			self::$db_server[self::$conn_id] = "";
			
		if (!isset(self::$db_database[self::$conn_id]))	
			self::$db_database[self::$conn_id] = "";
			
		if (!isset(self::$db_user[self::$conn_id]))	
			self::$db_user[self::$conn_id] = "";
			
		if (!isset(self::$db_passwd[self::$conn_id]))	
			self::$db_passwd[self::$conn_id] = "";
			
		if (!in_array(self::$conn_id,self::$conn_list))
		{
			self::$conn_list[] = self::$conn_id;
		}
	}
	
	/**
	*	Gets the connection ID currently in use.
	*	The default id is always 1 unless user changed to connection id.
	*	@access public
	*	@return String Charset
	*/
	public static function getConnectionID() 
	{
		return self::$conn_id;
	}
}

?>