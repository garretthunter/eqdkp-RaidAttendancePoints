<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        mysql.php
 * Began:       Tue Dec 17 2002
 * Date:        $Date: 2008-05-17 17:19:30 -0700 (Sat, 17 May 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     db
 * @version     $Rev: 530 $
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}
 
/**
* SQL_DB class, MySQL version
* Abstracts MySQL database functions
*/
include_once($eqdkp_root_path . 'includes/db/dbal.php');

define('DBTYPE', 'mysql');

class dbal_mysql extends dbal
{
    var $mysql_version;

/*
    function dbal_mysql()
    {
        $this->dbal();
    }
*/
    
    /**
     * Connects to a MySQL database
     * 
     * @param     string     $dbhost       Database server
     * @param     string     $dbname       Database name
     * @param     string     $dbuser       Database username
     * @param     string     $dbpass       Database password
     * @param     bool       $pconnect     Use persistent connection
     * @return    mixed                    Link ID upon a successful connect, sql_error otherwise
     */
    function sql_connect($dbhost, $dbname, $dbuser, $dbpass='', $pconnect = false)
    {
        $this->pconnect = $pconnect;
        $this->dbhost = $dbhost;
        $this->dbname = $dbname;
        $this->dbuser = $dbuser;
//gehPDO
        $this->charset = 'utf8';
        $this->dsn = "mysql:host=$dbhost;dbname=$dbname;charset=$this->charset";
        $this->opt = [
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES      => false,
        ];
//gehPDO        
        
		// Attempt to make a database connection

//gehPDO        
//        $this->link_id = ($this->pconnect) ? @mysql_pconnect($this->dbhost, $this->dbuser, $dbpass) : @mysql_connect($this->dbhost, $this->dbuser, $dbpass);
//
        // NOTE: It doesn't matter if it's null or not - if it's null, then it's not a resource        
//        if ( is_resource($this->link_id) && $this->dbname != '' )
//        {
//            if ( @mysql_select_db($this->dbname, $this->link_id) )
//            {
//                return $this->link_id;
//            }
//        }
	try {
	  $this->link_id = new PDO($this->dsn, $this->dbuser, $dbpass, $this->opt);

	  return $this->link_id;
	  
	} catch (PDOException $e) {
	
	  echo 'Connection failed: ' . $e->getMessage();
	  
	}
//gehPDO        

        return $this->sql_error('');
    }
    
	/**
	* SQL Transaction
	* @access private
	*/
	function _sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
//gehPDO
//				return @mysql_query('BEGIN', $this->link_id);
				return $this->link_id->beginTransaction();
			break;

			case 'commit':
//gehPDO
//				return @mysql_query('COMMIT', $this->link_id);
				return $this->link_id->commit();
			break;

			case 'rollback':
//gehPDO
//				return @mysql_query('ROLLBACK', $this->link_id);
				return $this->link_id->rollBack();
			break;
		}

		return true;
	}

    /**
     * Basic query function
     * 
     * @param     bool       $setting      whether execution should halt on an error (true) or continue (false)
     * @param     string     $query        The SQL query string
     * @param     array      $params       If present, replce :params in $query with the value of {@link build_query}
     * @return    mixed                    If the query was a success returns Query ID, otherwise an error string (if error_die == false), else boolean false
     */
    function sql_query($query, $params = false)
    {
        global $table_prefix;
        
        if ( $query != '' )
        {
            // Remove pre-existing query resources
            unset($this->query_id);
            
            // FIXME: This should *only* replace at the start of a word boundary.
            $query = preg_replace('#__([^\s]+)#', $table_prefix . '\1', $query);
            
            if ( is_array($params) && count($params) > 0 )
            {
                $params = $this->sql_build_query(preg_replace('/^(INSERT|REPLACE|UPDATE).+/', '\1', $query), $params);
                
                $query  = str_replace(':params', $params, $query);
            }
    
            // Do the query
//gehPDO        
//            $this->query_id = @mysql_query($query, $this->link_id);

            try {
                $this->query_id = $this->link_id->query($query);
            } catch (PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage() . "Query = $query<br />";
            }
//gehPDO        

/* gehPDO
            // If the query didn't work    
            if ( $this->query_id === false )
            {
                $message = $this->sql_error($query);

                if ( DEBUG )
                {
                    echo $message;
                }
                // FIXME: I don't think this is a good idea. If there's an error and it's not debugging, then it should be a hard error.
                return false;
            }
gehPDO */            
            // SQL Reporting
            if ( DEBUG == 2 )
            {
                $this->queries[] = $query;
            }
            
            // Unset records for the query ID
//gehPDO (don't know why this is done. kicking out errors)
//            unset($this->record[$this->query_id]);
//            unset($this->record_set[$this->query_id]);
//gehPDO (don't know why this is done. kicking out errors)
        }
        else
        {
            return false;
        }
        
        return ($this->query_id) ? $this->query_id : false;
    }
    
//gehPDO
    /**
     * Return the number of rows in a query
     *
     * @param	string    $table    The table in quesetion
     * @param   string    $where    The where clause for the query
     * @return  int                 The number of rows returned from query
     */
    function sql_get_count($table, $where = '')
    {
        $query = "SELECT COUNT(*) FROM ". $table ." ". $where;
        $result = $this->sql_query($query);
        $numRows = $result->fetchColumn();
        $this->sql_freeresult($this->query_id);
    
        return $numRows;    
    }
//gehPDO
   
    /**
     * Return the first record (single column) in a query result
     */
    // TODO: This should eventually be superceded by a method to return an arbitrary number of results at any (valid) given offset.
    function sql_query_first($query)
    {
        $this->sql_query($query);
        $record = $this->sql_fetchrow($this->query_id, false);
        $this->sql_freeresult($this->query_id);
        
        return $record[0];
    }
    
    
    /**
     * Fetch a record
     * 
     * @param     int        $query_id    Query ID
     * @param     bool       $assoc       MYSQL_ASSOC if true, MYSQL_NUM if false
     * @return    mixed                   Record array or false
     */
    function sql_fetchrow($query_id = false, $assoc = true)
    {

        if ($query_id === false)
        {
            $query_id = $this->query_id;
        }

//gehPDO
//        $result_type = ( $assoc ) ? MYSQL_ASSOC : MYSQL_NUM;
        $result_type = ( $assoc ) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;
//gehPDO
        
        if ($query_id !== false)
        {
//gehPDO
//            $this->record[$query_id] = @mysql_fetch_array($query_id, $result_type);
          $this->record = $query_id->fetch($result_type);  
//gehPDO

//gehPDO
//            return $this->record[$query_id];
            return $this->record;
//gehPDO
        }

        return false;
    }
    
    /**
     * Fetch a record set
     * 
     * @param     int        $query_id     Query ID
     * @return    mixed                    Record set array or false
     */
    // TODO: This isn't currently used anywhere. Delete it? There's probably a few places where it may be useful
    function sql_fetchrowset($query_id = false)
    {
        if ($query_id === false)
        {
            $query_id = $this->query_id;
        }
        
        if ($query_id !== false)
        {
            unset($this->record_set[$query_id]);
            unset($this->record[$query_id]);
            while ( $this->record_set[$query_id] = @mysql_fetch_array($query_id) )
            {
                $result[] = $this->record_set[$query_id];
            }
            return $result;
        }

        return false;
    }
    
    /**
     * Find the number of returned rows
     * 
     * @param     int        $query_id     Query ID
     * @return    mixed                    Number of rows or false
     */
    function sql_numrows($query_id = 0)
    {
        if ( !$query_id )
        {
            $query_id = $this->query_id;
        }
        
        return ($query_id) ? @mysql_num_rows($query_id) : false;
    }
    
    /**
     * Finds the number of rows affected by a query
     */
    function sql_affectedrows()
    {
        return ( $this->link_id ) ? @mysql_affected_rows($this->link_id) : 0;
    }
    
    /**
     * Find the ID of the last row inserted
     */
    // NOTE: Removed intval() statement encasing mysql_insert_id
    function sql_nextid()
    {
//gehPDO
//        return ($this->link_id) ? @mysql_insert_id($this->link_id) : false; 
        return ($this->link_id) ? $this->link_id->lastInsertId() : false; 
    }
    
    /**
     * Free result data
     * 
     * @param     int        $query_id     Query ID
     * @return    bool
     */
    function sql_freeresult($query_id = false)
    {
        if ($query_id === false)
        {
            $query_id = $this->query_id;
        }

        if ($query_id !== false)
        {
//gehPDO
//            unset($this->record[$query_id]);
//            unset($this->record_set[$query_id]);
//            @mysql_free_result($query_id);
            $this->record=NULL;
//gehPDO
            return true;
        }

        return false;
    }


    /**
     * Make a string (or array of strings) more secure against SQL injection
     * 
     * @param     string     $string       string to escape, or the implode() delimiter if $array is set
     * @param     array      $array        an array to pass to _implode(), escaping its values
     * @return    string                   the escaped string value(s)
     */
    // FIXME: Overloaded method operates differently to standard form. Consider splitting into two methods (rewrite _implode into sql_escape_array ?)
    function sql_escape($string, $array = false)
    {
//gehPDO
//        $string = (is_array($array)) ? $this->_implode($string, $array) : @mysql_real_escape_string($string);
        $string = (is_array($array)) ? $this->_implode($string, $array) : $this->link_id->quote($string);
        
        return $string;
    }


    /**
     * Close sql connection
     * @access private
     */
    function _sql_close()
    {
        if ( $this->link_id )
        {
            if ( $this->query_id )
            {
                $this->sql_freeresult();
            }
            return @mysql_close($this->link_id);
        }

        return false;
    }

    /**
     * return sql error array
     * @access private
     */
    function _sql_error()
    {
        if (!$this->link_id)
        {
            return array(
                'message' => @mysql_error(),
                'code'    => @mysql_errno()
            );
        }

        return array(
            'message' => @mysql_error($this->link_id),
            'code'    => @mysql_errno($this->link_id)
        );
    }


/*
 * Deprecated Methods.
 *
 * These methods will disappear in a few versions' time. Please ensure your code uses the new method names!
 */
     // sql_close
    function close_db()
    {
        return $this->_sql_close();
    }
    
    // _sql_error
    function error()
    {
        return $this->_sql_error();
    }

    // sql_query
    function query($query, $params = null)
    {
        return $this->sql_query($query, $params);
    }

    // sql_query_first
    function query_first($query)
    {
        return $this->sql_query_first($query);
    }

    // sql_fetchrow
    // NOTE: Old check for query_id preserved. Drops case where id = 0.
    function fetch_record($query_id = 0, $assoc = true)
    {
        if(!$query_id)
        {
            $query_id = $this->query_id;
        }
        
        return $this->sql_fetchrow($query_id, $assoc);
    }

    // sql_fetchrowset
    // NOTE: Old check for query_id preserved. Drops case where id = 0.
    function fetch_record_set($query_id = 0)
    {
        if (!$query_id)
        {
            $query_id = $this->query_id;
        }
        
        return $this->sql_fetchrowset($query_id);
    }

    // sql_numrows
    function num_rows()
    {
        return $this->sql_numrows();
    }

    // sql_affectedrows
    function affected_rows()
    {
        return $this->sql_affectedrows();
    }

    // sql_nextid
    function insert_id()
    {
        return $this->sql_nextid();
    }

    // sql_freeresult
    // NOTE: Old check for query_id preserved. Drops case where id = 0.
    function free_result($query_id = 0)
    {
        if (!$query_id)
        {
            $query_id = $this->query_id;
        }

        return $this->sql_freeresult($query_id);
    }
}