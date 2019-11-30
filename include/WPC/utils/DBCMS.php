<?php

abstract class DBCMS
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

    
    private static $shutdown_handler_registered = false;
    
    public static $ERRORS_TO_PHP = true;
    public static $ERRORS_KEEP = false;
	public static $MSSQL_STATEMENT_OPTIONS = array("Scrollable" => "static");
    public static $MSSQL_FETCH_ARTYPE = SQLSRV_FETCH_BOTH; // SQLSRV_FETCH_ASSOC, SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_BOTH
    
 
    /**
    *   The destructor will close on mssql connections for us when the class unloads
    *   @access public
    */
    public static function destruct() 
    {
        self::closeAll();
    }

    
    /**
    *   Setup the object.
    *   Must be Initially called.
    *   @access public
    */
    public static function init() 
    {
        if (!self::$shutdown_handler_registered)
        {
            $shutdown_handler_registered = true;
            register_shutdown_function(array('DBCMS', 'destruct'));
        }
        
        self::reset(); //reset everything.
    }

    
    /**
    *   Resets the database variables.
    *   Initially called.
    *   @access public
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
    *   Get the status of the connection.

    *   
    *   @access public
    *   @return Boolean True if connected, false if not.
    */
    public static function isConnected() 
    {
        if (isset(self::$connection[self::$conn_id]) 
        	&& (is_object(self::$connection[self::$conn_id]) || is_resource(self::$connection[self::$conn_id])))
        {
            self::$openconnection[self::$conn_id] = true;
            return self::$openconnection[self::$conn_id];   
        }
        else
        {
            self::$openconnection[self::$conn_id] = false;
            return false;
        }
    }

    
    /**
    *   Get connection object if connected.
    *   (mssqli object)
    *   @access public
    *   @return mssqli Class Returns the mssqli connection object class.
    */
    public static function getConnection() 
    {
        if (!self::isConnected())
        {
            self::internal_err_add(sprintf('DBCMS Error (%s, %s): Database connection not open!', __LINE__, basename(__FILE__)));
            return NULL;
        }
        return self::$connection[self::$conn_id];
    }

    
    /**
    *   Get connection object if connected.
    *   (mssqli object)
    *   @access public
    *   @return mssqli Class Returns the mssqli connection object class.
    */
    public static function hasError()
    {
        if (!self::isConnected())
        {
            self::$has_error = true;    
            self::internal_err_add(sprintf('DBCMS Error (%s, %s): No connection has been created!', __LINE__, basename(__FILE__)));
        }
            
        return self::$has_error;    
    }

    
    /**
    *   Check the connection and create one if none exists
    *   (mssqli object)
    *   @access public
    *   @return Nothing
    */
    public static function check() 
    {
        if (isset(self::$connection[self::$conn_id]) && (is_object(self::$connection[self::$conn_id]) || is_resource(self::$connection[self::$conn_id])))
        {
            //connections exists. Do nothing.
            return true;
        }
        else
        {
            if (self::create())
            {
                //update the charset if specified.
                //if (strlen(self::$db_charset) > 1)
                //   self::qq("SET NAMES ".self::$db_charset.";"); //set the database charset.
            }   
            return false;

        }
    }

    
    /**
    *   Create the connection object.
    *   (mssqli object)
    *   @param $override Boolean Overrides the current connection with a new one.
    *   @access public
    *   @return mssqli Class Returns the mssqli connection object class.
    */
    public static function create($override = false) 
    {
        $created = false;
        
        if (!self::isConnected() || $override)
        {
			$connectionInfo = array( "Database"=>self::$db_database[self::$conn_id]
				, "UID"=>self::$db_user[self::$conn_id]
				, "PWD"=>self::$db_passwd[self::$conn_id] );
			
            self::$connection[self::$conn_id] = sqlsrv_connect(self::$db_server[self::$conn_id], $connectionInfo);
            self::$openconnection[self::$conn_id] = true;
            
			/* Disable warnings as errors behavior. */
			sqlsrv_configure("WarningsReturnAsErrors", 0);
			
            if ((is_resource(self::$connection[self::$conn_id]) || is_object(self::$connection[self::$conn_id])) 
            	&& (sqlsrv_errors() != NULL) )
            {
                self::$openconnection[self::$conn_id] = false;
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): Database connection failed, %s!',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true) ));
                self::$has_error = true;
                self::$connection[self::$conn_id] = NULL;
                
                $created = false;
            }
            elseif (is_resource(self::$connection[self::$conn_id]) || is_object(self::$connection[self::$conn_id])) 
            {
                self::$openconnection[self::$conn_id] = true;
                self::$has_error = false; 
                $created = true;  
            }
            else 
            {
                self::$openconnection[self::$conn_id] = false;
                self::$has_error = true;
                $created = false;
            }
        }
        else
        {
            self::internal_err_add(sprintf('DBCMS Error (%s, %s): Connection already open!', __LINE__, basename(__FILE__)));
        }
        
        return $created;
    }

    
    /**
    *   Prepares a mssqli statement and return the statement.
    *   (mssqli statement object)
    *   @param $override Boolean Overrides the current connection with a new one.
    *   @access public
    *   @return mssqli Statement Returns the mssqli statement object class.
    */
    public static function st($query, $ar_params = NULL) 
    {
        $statement = NULL;
        self::check();
        if (self::isConnected())

        {
        	if (count($ar_params) > 0)
            	$statement = sqlsrv_prepare(self::$connection[self::$conn_id], $query, $ar_params, self::$MSSQL_STATEMENT_OPTIONS);
			else
				$statement = sqlsrv_prepare(self::$connection[self::$conn_id], $query, null , self::$MSSQL_STATEMENT_OPTIONS);
            if (!$statement || (sqlsrv_errors() != NULL) || !is_resource($statement))

            {
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): Statement error, %s',
                 __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));

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
    *   Handle a statement error. Also return true if errors was found.
    *   @param $override 'mssqli Statement' Statement object to check for error on.
    *   @access public
    *   @return Boolean Returns true if errors was found and false if not errors was found.
    */
    public static function stError($statement) 
    {
        self::check();
        //make sure this is what we are looking for.
        if (self::isConnected() && (is_object($statement) || is_resource($statement) ))
        {
            if (!$statement || (sqlsrv_errors() != NULL))
            {
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): Statement error, %s',
                 __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));

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
    *   Get the last insert autoincrement value/id.
    *   @access public
    *   @return Int/float Return the id of the last inserted entry.
    */
    public static function lastID()
    {
        self::check();
        //if (self::$connection[self::$conn_id])
        //    return self::$connection[self::$conn_id]->insert_id;

        return 0;
    }

    
    /**
    *   Create a quick statement based query and return the results.
    *   @param $query mssql query string.
    *   @param  Optional string with the binding types
    *   @param  Additional variables being bound.
    *   @access public
    *   @return Boolean Returns an array with the table data or total rows effected depending on the query type.
    */
    public static function q($query) 
    {
        self::check();
        if (!self::isConnected()) return false;
		$args_ref = array();
		
		if (func_num_args() > 1) 
		{
			$x = func_get_args();
			$args = array_merge(array(func_get_arg(1)),
				array_slice($x, 2));
			foreach($args as $k => &$arg) {
				$args_ref[$k] = &$arg; 
			}
		}
		
		
		if (count($args_ref) > 0)
			$st = sqlsrv_prepare(self::$connection[self::$conn_id],$query, $args_ref, self::$MSSQL_STATEMENT_OPTIONS);
		else
			$st = sqlsrv_prepare(self::$connection[self::$conn_id],$query, null, self::$MSSQL_STATEMENT_OPTIONS);
		
        if ($st != false) 
        {
			//$ar_metadata = sqlsrv_field_metadata($st);
			//$ar_cols = array();
			//$l_cnt = 0;
			//foreach ($ar_metadata as $k => $l_metadata)
			//{
			//	$ar_cols[$l_cnt] = $l_metadata["Name"];
			//	$l_cnt++;
			//}
			
            if (sqlsrv_execute($st) === false) 
            {
                self::$has_error = true;
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): Statement error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));

                 
                if (self::$debug) 
                {
                    debug_print_backtrace();
                }
                return false;
            }
 			
			$rows_affected = sqlsrv_rows_affected($st);
            if ($rows_affected > -1) 
            {
                self::$has_error = false;   
                return $rows_affected;
            }

            $result = array();
            while ($row = sqlsrv_fetch_array($st, self::$MSSQL_FETCH_ARTYPE)) 
			{
                $result[] = $row;
            }
            //$st->close(); 
            self::$has_error = false;
            return $result;
        } else {
            self::internal_err_add(sprintf('DBCMS Error (%s, %s): Quick qeury error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));

                     
            self::$has_error = true;     

            
            if (self::$debug) 
                debug_print_backtrace();

                
            return false;
        }
    }

    
    /**
    *   Run a quick query and return the results set
    *   @param $query mssql query string.
    *   @access public
    *   @return Boolean Returns an array with the table data or total rows effected depending on the query type.
    */
    public static function dbquery($query)
    {
        self::check();
        $result = NULL;
		$args = array();
		
		if (func_num_args() > 1) 
		{
			$x = func_get_args();
			$args = array_merge(array(func_get_arg(1)),
				array_slice($x, 2));
		}
		

        if (self::isConnected())
        {
        	if (count($args) > 0)
				$result = sqlsrv_query(self::$connection[self::$conn_id], $query, $args, self::$MSSQL_STATEMENT_OPTIONS);
			else
				$result = sqlsrv_query(self::$connection[self::$conn_id], $query, null, self::$MSSQL_STATEMENT_OPTIONS);

            if (sqlsrv_errors() != NULL)
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): Query error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true) ));
        }
		
        return $result;
    }

    /**
    *   Fetch assoc array from results set
    *   @param $results mssqli Results object
    *   @access public
    *   @return Boolean Returns an array with the table data or NULL if no more rows.
    */
    public static function dbfetch($results)
    {
        self::check();
        $arr = NULL;

        if (self::isConnected() && (is_object($results) || is_resource($results)))
        {
            $arr = sqlsrv_fetch_array($results, self::$MSSQL_FETCH_ARTYPE);

            if (sqlsrv_errors() != NULL)
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): dbfetch error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true) ));

        }
        return $arr;
    }

    
    /**
    *   Fetch affected rows
    *   @param $results
    *   @access public
    *   @return Boolean Returns an array with the table data or NULL if no more rows.
    */
    public static function dbrows($results)
    {
        self::check();
        $res = NULL;
        
        if (self::isConnected() && (is_object($results) || is_resource($results)))
        {
            $res = sqlsrv_num_rows($results);

            if (sqlsrv_errors() != NULL)
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): dbfetch error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));
        }
        return $res;
    }

    
    /**
    *   Free the results
    *   @param $results
    *   @access public
    *   @return Boolean Returns an array with the table data or NULL if no more rows.
    */
    public static function dbfree($results)
    {
        self::check();
        $res = NULL;

        if (self::isConnected() && (is_object($results) || is_resource($results)))
        {
            $res = sqlsrv_free_stmt($results);

            if (sqlsrv_errors() != NULL)
                self::internal_err_add(sprintf('DBCMS Error (%s, %s): dbfetch error, %s',
                     __LINE__, basename(__FILE__), print_r( sqlsrv_errors(), true)));

        }
        return $res;
    }


    /**
    *   mssql Escape
    *   @param $value The value to escape
    *   @access public
    *   @return Boolean Returns escaped value
    */
    public static function dbescape($value)
    {
        self::check();
        $res = $value;
		//for now leave empty
        return $res;
    }


    
    /**
    *   Get a list of errors generated by the database connection
    *   @access public
    *   @return Array List of error strings
    */
    public static function getErrors()
    {
        return self::$errors;
    }

    
    /**
    *   Print a list of errors generated by the database connection
    *   @access public
    *   @return Array List of error strings
    */
    public static function printErrors()
    {
        foreach (self::$errors as $e)
        {
            printf('<li>%s</li>', $e);
        }
    }

    /**
    *   Clear list of errors generated by the database connection
    *   @access public
    *   @return Bolean
    */
    public static function clearErrors()
    {
        self::$errors = array();
    }

    
    /**
    *   Close database connection
    *   @access public
    */
    public static function close() 
    {
        self::$openconnection[self::$conn_id] = false;
        if (self::$connection[self::$conn_id])
        {
			sqlsrv_close(self::$connection[self::$conn_id]);
            self::$connection[self::$conn_id] = NULL;

            self::$openconnection[self::$conn_id] = false;
            //unset(self::$connection[self::$conn_id]);
        }
    }

    
    /**
    *   Closes all active database connection
    *   @access public
    */
    public static function closeAll() 
    {
        foreach (self::$conn_list as $conn_id)
        {
            DBCMS::setConnectionID($conn_id);
            self::close();  
        }
    }

    
    /**
    *   Get the username
    *   @access public
    *   @return String Database username
    */
    public static function getUser() 
    {
        return self::$db_user[self::$conn_id];
    }

    
    /**
    *   Get the server socket (ip:port)
    *   @access public
    *   @return String Database server socket
    */
    public static function getServer() 
    {
        return self::$db_server[self::$conn_id];
    }

    
    /**
    *   Get the server Port
    *   @access public
    *   @return String Database server Port
    */
    public static function getPort() 
    {
        return self::$db_port[self::$conn_id];
    }
    
    /**
    *   Get the database name
    *   @access public
    *   @return String Database name
    */
    public static function getDatabase() 
    {
        return self::$db_database[self::$conn_id];
    }

    
    /**
    *   Set the database user
    *   @access public
    *   @param $value The username
    */
    public static function setUser($value) 
    {
        self::$db_user[self::$conn_id] = $value;
    }

    
    /**
    *   Set the database password
    *   @access public
    *   @param $value The password
    */
    public static function setPassword($value) 
    {
        self::$db_passwd[self::$conn_id] = $value;
    }

    
    /**
    *   Set the database server socket (ip:port)
    *   @access public
    *   @param $value The server socket string (ip:port) 
    */
    public static function setServer($value) 
    {
        self::$db_server[self::$conn_id] = $value;
    }

    
      /**
    *   Set the database server Port
    *   @access public
    *   @param $value The server Port
    */
    public static function setPort($value) 
    {
        self::$db_port[self::$conn_id] = $value;
    }
    
    /**
    *   Set the database name
    *   @access public
    *   @param $value The database name 
    */
    public static function setDatabase($value) 
    {
        self::$db_database[self::$conn_id] = $value;
    }

    
    /**
    *   Enabled debugging information
    *   @access public
    *   @param $debug Boolean Enable debugging
    */
    public static function setDebug($debug)
    {
        self::$debug = $debug;
    }

    
    /**
    *   Set the database charset for this connection.
    *   Settings this to a empty string will make the connection use the default charset.
    *   @access public
    *   @param $debug Boolean Enable debugging
    */
    public static function setCharset($charset)
    {
        self::$db_charset = $charset;
    }

    
    /**
    *   Get the database charset for this connection.
    *   @access public
    *   @return String Charset
    */
    public static function getCharset() 
    {
        return self::$db_charset;
    }

    
    /**
    *   Set the connection identifier.
    *   If you want to use different connections. The identifier of that connection must be set.
    *   Remeber to set server, username and password every time you change this unless you change back to existing connection.
    *   Default connection will always be 1
    *   @access public
    *   @param $identifier Integer The uniquely identifies the connection.
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
    *   Gets the connection ID currently in use.
    *   The default id is always 1 unless user changed to connection id.
    *   @access public
    *   @return String Charset
    */
    public static function getConnectionID() 
    {
        return self::$conn_id;
    }
    
   
    private static function internal_err_add($error_str)
    {
        if (self::$ERRORS_TO_PHP)
        {
            trigger_error($error_str, E_USER_WARNING);
            if (!self::$ERRORS_KEEP)
            {
                self::clearErrors();
            }
        }
        else 
        {
            array_push(self::$errors, $error_str);
            /*if (!self::$ERRORS_KEEP)
            {
                self::clearErrors();
            }*/
        }
    }
    
}

?>