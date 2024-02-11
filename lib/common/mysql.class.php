<?php

include_once 'base.class.php';

class MySQL extends Base
{
    protected $version = 1.3;

    public $connected    = false;
    public $totalQueries = 0;
    public $totalRows    = 0;
    public $lastErrno    = null;
    public $lastError    = null;

    private $resource;
    private $host;
    private $username;
    private $password;
    private $dbname;

    public function __construct($debug = null)
    {
        parent::__construct($debug);
    }

    public function connect($host, $username, $password, $dbname, $persistent = false)
    {
        $this->debug(1,"connecting to $host($dbname), user:$username, persistent:$persistent");

        if (!$this->resource = @mysqli_connect($host, $username, $password, $dbname)) {
           $this->debug(1,"unable to establish connection to database");
           return false;
        }

        $this->connected = true;
        $this->host      = $host;
        $this->username  = $username;
        $this->password  = $password;
        $this->dbname    = $dbname;

        $this->debug(1,"connected to database ($dbname)");

        return true;
    }

    public function reconnect()
    {
        $this->disconnect();

        return $this->connect($this->host, $this->username, $this->password, $this->dbname);
    }

    public function disconnect()
    {
        $this->connected = false;

        return mysqli_close($this->resource);
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function bindExecute($statement, $types, $data)
    {
        $execResult = false;

        if (!$this->connected) {
            $this->debug(1,"query requested, but database not connected");
            return $execResult;
        }

        $this->debug(9,"connected, bindquery($statement) types($types)");

        $stmt = mysqli_prepare($this->resource,$statement);

        if ($stmt === false) {
            $this->debug(1,"malformed statement in prepare ($statement)");
            return $execResult;
        }

        // If we're not a multidimension array, we'll fabricate one with one element to loop through
        if (count($data) == count($data,1)) { $data = array($data); }

        foreach ($data as $rowId => $rowVars) {
           $varRefs = array();
           foreach (array_keys($rowVars) as $fieldPosition) { $varRefs[$fieldPosition] = &$rowVars[$fieldPosition]; }

           $bindResult = call_user_func_array(array($stmt,'bind_param'),array_merge(array($types),$varRefs));

           if ($bindResult === false) {
              $this->lastErrno = 0;
              $this->lastError = "Could not bind parameters at position $rowId";
              break;
           }

           $execResult = mysqli_stmt_execute($stmt);

           if ($execResult === false) {
              $this->lastErrno = mysqli_stmt_errno($stmt);
              $this->lastError = mysqli_stmt_error($stmt);
              break;
           }
        }

        if ($execResult) {
           $this->queryRows(mysqli_stmt_affected_rows($stmt));
           $this->totalQueries(1);
        }

        mysqli_stmt_close($stmt);

        return $execResult;
    }

    public function execute($statement)
    {
        if (!$this->connected) {
            $this->debug(1,"query requested, but database not connected");
            return false;
        }

        $this->debug(9,"connected, query($statement)");

        $result = mysqli_query($this->resource,$statement);

        if (!empty($result)) {
            $queryrows = (preg_match('/^\s*select/i', $statement)) ? $this->numRows($result) : 0;
            $this->queryRows($queryrows);
            $this->totalQueries(1);
        }

        return $result;
    }

    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    public function fetchObject($result)
    {
        return mysqli_fetch_object($result);
    }

    public function freeResult($result)
    {
        mysqli_free_result($result);
    }

    public function query($param, $options = null)
    {
       $return = array();

       if (!$this->connected) {
          $this->debug(1,"query requested, but database not connected");
          return $return;
       }

       // Support for two types of calls.
       // 1) Called with one argument containing all relevant information, including the query as array
       // 2) Called with two arguments, the query as string and then the relevant options as array
       //==============================================================================================
       if (is_array($param)) {
          if (!key_exists('query', $param)) {
             return $return;
          }

          $query = $param['query'];
          $options = $param;
       }
       else {
          $query = $param;
       }

       $keyid    = isset($options['keyid'])     ? $options['keyid']     : false;
       $multi    = isset($options['multi'])     ? $options['multi']     : 1;
       $serial   = isset($options['serialize']) ? $options['serialize'] : 0;
       $callback = isset($options['callback'])  ? $options['callback']  : 0;

       $this->debug(9,"query($query) keyid($keyid) multi($multi) serial($serial) callback($callback)");

       $result = $this->execute($query);

       if (!$result) {
           $this->debug(9,"no results, query($query)");
           return $return;
       }

       if ($multi) {
           while ($rec = $this->fetchAssoc($result)) {
              if (!$keyid) {
                 $keyid = array_shift(array_keys($rec));
                 $this->debug(9,"no keyid set, keyid($keyid)");
              }

              $id = $rec[$keyid];

              if (!$id) { continue; }

              if (is_callable($callback)) {
                 call_user_func_array($callback,array($id,$rec,&$return));
                 continue;
              }

              $return[$id] = ($serial) ? serialize($rec) : $rec;
          }
       }
       else {
          $return = $this->fetchAssoc($result);
       }

       $this->freeResult($result);

       if (is_array($return)) { $this->debug(7,"loaded ".count($return)." elements"); }

       return $return;
    }

    public function insertId()
    {
       return mysqli_insert_id($this->resource);
    }

    public function numRows($result)
    {
       return mysqli_num_rows($result);
    }

    public function escapeString($string)
    {
       return mysqli_real_escape_string($this->resource,$string);
    }

    public function autoCommit($value)
    {
       return $this->execute("set autocommit = $value");
    }

    public function startTransaction()
    {
       $this->autoCommit(0);
       $result = $this->execute("start transaction");

       return $result;
    }

    public function endTransaction($commit = 1)
    {
       if ($commit) { $this->commit(); }
       else { $this->rollback(); }

       $result = $this->autoCommit(1);

       return $result;
    }

    public function commit()
    {
       return $this->execute("commit");
    }

    public function rollback()
    {
       return $this->execute("rollback");
    }


    public function queryRows($value = NULL)
    {
       if (is_int($value)) {
          $this->totalRows += $value;
       }
       return $this->totalRows;
    }

    public function affectedRows()
    {
       if (!$this->connected) { return; }

       $rows = mysqli_affected_rows($this->resource);

       return $rows;
    }

    public function totalQueries($value = NULL)
    {
       if (is_int($value)) {
          $this->totalQueries += $value;
       }
       return $this->totalQueries;
    }

    public function setTimezone($offset = null)
    {
       if (!$this->connected) { return; }

       if (!$offset) { return 0; }

       $offset = preg_replace('/[^0-9\:\-\+]/','',$offset);

       $rc = $this->execute("set time_zone = '$offset'");

       $this->debug(7,"setting database timezone to offset: $offset (rc:$rc)");

       return $rc;
    }

    public function error()
    {
       $lastErrno = (is_null($this->lastErrno)) ? mysqli_errno($this->resource) : $this->lastErrno;
       $lastError = (is_null($this->lastError)) ? mysqli_error($this->resource) : $this->lastError;

       // Clear the last error variables before returning the errors
       $this->lastErrno = null;
       $this->lastError = null;

       return array($lastErrno,$lastError);
    }
}
