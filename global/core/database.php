<?php
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
    parent::__construct($this->host, $this->username, $this->password, $this->database, $this->port);
    if (mysqli_connect_error()) {
      die('Could not connect to the database.');
    }
    $this->set_charset("utf8");
    if (class_exists("Memcached")) {
      $this->memcached = new Memcached();
      $this->memcached->addServer(Config::MEMCACHED_HOST, intval(Config::MEMCACHED_PORT));
    } else {
      // memcached isn't running. don't cache anything.
      $this->memcached = False;
    }
  }
  public function quoteSmart($value) {
    //escapes input values for insertion into a query.
    if( is_array($value) ) {
      return array_map(array($this, $this->quoteSmart), $value);
    } else {
      if( get_magic_quotes_gpc() ) {
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
      $result = $this->query($query)
        or die("Could not query MySQL database in ".$_SERVER['PHP_SELF'].".<br />
          Query: ".$query."<br />
          ".$this->error."<br />
          Time: ".time()."<br />
          Stack trace:<br /><pre>".print_r(debug_backtrace(), True)."</pre>");
    } else {
      $result = $this->query($query)
        or die("Could not query MySQL database in ".$_SERVER['PHP_SELF'].".<br />
           Time: ".time());
    }
    return $result;
  }
  public function queryFirstRow($query) {
    // pulls the first row returned from the query.
    // pull from memcached if it's up.
    $queryKey = md5("queryFirstRow".$query);
    if (!($this->memcached === False)) {
      $result = $this->memcached->get($queryKey);
      if ($this->memcached->getResultCode() == Memcached::RES_SUCCESS) {
        return $result;
      }
    }
    $result = $this->stdQuery($query);
    if ($result->num_rows < 1) {
      return False;
    }
    $returnValue = $result->fetch_assoc();
    $result->free();
    // store in memcached if it's up.
    if (!($this->memcached === False)) {
      $this->memcached->set($queryKey, $returnValue, Config::MEMCACHED_DEFAULT_LIFESPAN);
    }
    return $returnValue;
  }
  public function queryFirstValue($query) {
    // pull from memcached if it's up.
    $queryKey = md5("queryFirstRow".$query);
    if (!($this->memcached === False)) {
      $result = $this->memcached->get($queryKey);
      if ($this->memcached->getResultCode() == Memcached::RES_SUCCESS) {
        $resultKeys = array_keys($result);
        return $result[$resultKeys[0]];
      }
    }
    $result = $this->queryFirstRow($query);
    if (!$result || count($result) != 1) {
      return False;
    }
    $resultKeys = array_keys($result);
    // store in memcached if it's up.
    if (!($this->memcached === False)) {
      $this->memcached->set($queryKey, $result, Config::MEMCACHED_DEFAULT_LIFESPAN);
    }
    return $result[$resultKeys[0]];
  }
  public function queryAssoc($query, $idKey=Null, $valKey=Null) {
    // pull from memcached if it's up.
    $queryKey = md5("queryAssoc".$idKey.$query);
    if (!($this->memcached === False)) {
      $returnValue = $this->memcached->get($queryKey);
      if ($this->memcached->getResultCode() == Memcached::RES_SUCCESS) {
        return $returnValue;
      }
    }
    $result = $this->stdQuery($query);
    if (!$result) {
      return False;
    }
    if ($result->num_rows < 1) {
      return array();
    }
    $returnValue = array();
    while ($row = $result->fetch_assoc()) {
      if ($idKey === Null && $valKey === Null) {
        $returnValue[] = $row;
      } elseif ($idKey !== Null && $valKey === Null) {
        $returnValue[intval($row[$idKey])] = $row;
      } elseif ($idKey === Null && $valKey !== Null) {
        $returnValue[] = $row[$valKey];
      } else {
         $returnValue[intval($row[$idKey])] = $row[$valKey];
      }
    }
    $result->free();
    // store in memcached if it's up.
    if (!($this->memcached === False)) {
      $this->memcached->set($queryKey, $returnValue, Config::MEMCACHED_DEFAULT_LIFESPAN);
    }
    return $returnValue;
  }
  public function queryCount($query, $column="*") {
    $result = $this->queryFirstRow($query);
    if (!$result) {
      return False;
    }
    return intval($result['COUNT('.$column.')']);
  }
}
?>