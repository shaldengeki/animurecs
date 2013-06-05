<?php
class DbException extends Exception {
  public function __construct($message = null, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }
}

class DbConn extends mysqli {
  //basic database connection class that provides input-escaping and standardized query error output.
  
  public $queryLog;
  private $host, $port, $username, $password, $database, $memcached;

  public function __construct($host=Config::MYSQL_HOST, $port=Config::MYSQL_PORT, $username=Config::MYSQL_USERNAME, $password=Config::MYSQL_PASSWORD, $database=Config::MYSQL_DATABASE) {
    $this->host = $host;
    $this->port = intval($port);
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    $this->queryLog = [];
    try {
      parent::__construct($this->host, $this->username, $this->password, $this->database, $this->port);
    } catch (Exception $e) {
      throw new DbException('could not connect to the database', 0, $e);
    }
    $this->set_charset("utf8");
  }

  public function quoteSmart($value) {
    //escapes input values for insertion into a query.
    if(is_array($value)) {
      return array_map([$this, $this->quoteSmart], $value);
    } else {
      if(get_magic_quotes_gpc()) {
        $value = stripslashes($value);
      }
      if ($value === Null) {
        $value = 'NULL';
      } elseif (!is_numeric($value) || $value[0] == '0' ) {
        $value = "\"".$this->real_escape_string($value)."\"";
      }
      return $value;
    }
  }
  public function stdQuery($query) {
    // executes a query with standardized error message.
    if (Config::DEBUG_ON) {
      $this->queryLog[] = $query;
    }
    try {
      $result = $this->query($query);
    } catch (Exception $e) {
      $exceptionText = "Could not query MySQL database in ".$_SERVER['PHP_SELF'].".\nQuery: ".$query;
      throw new DbException($exceptionText, 0, $e);
    }
    if (!$result) {
      $exceptionText = "Could not query MySQL database in ".$_SERVER['PHP_SELF'].".\nQuery: ".$query;
      throw new DbException($exceptionText, 0, $e);
    }
    return $result;
  }
  public function queryFirstRow($query) {
    // pulls the first row returned from the query.
    $result = $this->stdQuery($query);
    if (!$result || $result->num_rows < 1) {
      throw new DbException("No rows were found matching query: ".$query);
    }
    $returnValue = $result->fetch_assoc();
    $result->free();
    return $returnValue;
  }
  public function queryFirstValue($query) {
    // pulls the first key from the first row returned by the query.
    $result = $this->queryFirstRow($query);
    if (!$result || count($result) != 1) {
      throw new DbException("No rows were found matching query: ".$query);
    }
    $resultKeys = array_keys($result);
    return $result[$resultKeys[0]];
  }
  public function queryAssoc($query, $idKey=Null, $valKey=Null) {
    // pulls an associative array of columns for the first row returned by the query.
    $result = $this->stdQuery($query);
    if (!$result) {
      throw new DbException("No rows were found matching query: ".$query);
    }
    if ($result->num_rows < 1) {
      return [];
    }
    $returnValue = [];
    while ($row = $result->fetch_assoc()) {
      if ($idKey === Null && $valKey === Null) {
        $returnValue[] = $row;
      } elseif ($idKey !== Null && $valKey === Null) {
        $returnValue[intval($row[$idKey])] = $row;
      } elseif ($idKey === Null && $valKey !== Null) {
        $returnValue[] = $row[$valKey];
      } else {
        if (is_numeric($row[$idKey])) {
          $row[$idKey] = intval($row[$idKey]);
        }
        $returnValue[$row[$idKey]] = $row[$valKey];
      }
    }
    $result->free();
    return $returnValue;
  }
  public function queryCount($query, $column="*") {
    $result = $this->queryFirstRow($query);
    if (!$result) {
      throw new DbException("No rows were found matching query: ".$query);
    }
    return intval($result['COUNT('.$column.')']);
  }
}
?>