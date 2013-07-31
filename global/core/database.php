<?php
class DbException extends Exception {
  public function __construct($message = null, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }
  public function display() {
    // displays end user-friendly output explaining the exception that occurred.
    echo "A database error occurred: ".$this->message.". The staff has been notified; sorry for the inconvenience!";
  }
}

class DbConn extends PDO {
  //basic database connection class that provides input-escaping and standardized query error output.
  use Loggable;

  public $queryLog;
  private $host, $port, $username, $password, $database;

  public function __construct($host=Config::MYSQL_HOST, $port=Config::MYSQL_PORT, $username=Config::MYSQL_USERNAME, $password=Config::MYSQL_PASSWORD, $database=Config::MYSQL_DATABASE, $fetchMode=PDO::FETCH_ASSOC) {
    $this->host = $host;
    $this->port = intval($port);
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    try {
      parent::__construct('mysql:host='.$this->host.';port='.$this->port.';dbname='.$this->database.';charset=utf8', $this->username, $this->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => $fetchMode
      ]);
    } catch (PDOException $e) {
      throw new DbException('Could not connect to the database', 0, $e);
    }

    $this->queryLog = [];
    $this->reset();
  }

  public function reset() {
    // clears query parameters.
    $this->type = "SELECT";
    $this->table = $this->offset = $this->limit = $this->lastInsertId = Null;
    $this->fields = $this->joins = $this->sets = $this->wheres = $this->values = $this->groups = $this->havings = $this->orders = $this->params = [];
    return $this;
  }

  public function table($table) {
    $this->table = $table;
    return $this;
  }

  public function fields() {
    $this->fields = array_merge($this->fields, func_get_args());
    return $this;
  }

  public function join($join, $type="INNER") {
    $this->joins[] = implode(" ", [$type, "JOIN", $join]);
    return $this;
  }

  public function set($sets) {
    foreach ($sets as $key=>$value) {
      if (is_numeric($key)) {
        // non-named field. pass it in raw.
        $this->sets[] = $value;
      } else {
        $this->sets[] = $key."="."?";
        $this->params[] = $value;
      }
    }
    return $this;
  }

  public function where(array $wheres=Null) {
    if ($wheres === Null) {
      return $this;
    }
    foreach ($wheres as $key=>$value) {
      if (is_numeric($key)) {
        if (is_array($value)) {
          // user has provided an entry of the form
          // ["UPPER(`name`) = ?", name]
          $this->wheres[] = $value[0];
          if (is_array($value[1])) {
            $this->params = array_merge($this->params, $value[1]);
          } else {
            $this->params[] = $value[1];
          }
        } else {
          // non-named field. pass it in raw.
          $this->wheres[] = $value;
        }
      } else {
        if (is_array($value)) {
          // this is an IN field.
          $this->wheres[] = $key." IN (".implode(",", array_fill(0, count($value), "?")).")";
          $this->params = array_merge($this->params, $value);
        } else {
          $this->wheres[] = $key."="."?";
          $this->params[] = $value;
        }
      }
    }
    return $this;
  }

  public function values() {
    foreach (func_get_args() as $value) {
      $this->values[] = "(".implode(",", array_fill(0, count($value), "?")).")";
      $this->params = array_merge($this->params, $value);
    }
    return $this;
  }

  public function match($fields, $query) {
    if (is_array($fields)) {
      $fields = implode(",", $fields);
    }
    $this->wheres[] = "MATCH(".$fields.") AGAINST(? IN BOOLEAN MODE)";
    $this->params[] = $query;
    return $this;
  }

  public function group() {
    $this->groups = array_merge($this->groups, func_get_args());
    return $this;
  }

  public function having() {
    $this->havings = array_merge($this->havings, func_get_args());
    return $this;
  }

  public function order() {
    $this->orders = array_merge($this->orders, func_get_args());
    return $this;
  }

  public function offset($offset) {
    $this->offset = intval($offset);
    return $this;
  }

  public function limit($limit) {
    $this->limit = intval($limit);
    return $this;
  }

  public function queryString() {
    $fields = $this->fields ? $this->fields : ["*"];
    $queryList = [$this->type];

    if ($this->type === "SELECT" || $this->type === "DELETE") {
      if ($this->type === "SELECT") {
        $queryList[] = implode(",", $fields);
      }
      $queryList[] = "FROM";
    } elseif ($this->type === "INSERT") {
      $queryList[] = "INTO";
    }
    $queryList[] = $this->table;
    $queryList[] = implode(" ", $this->joins);

    if ($this->type === "INSERT" && $this->values) {
      $queryList[] = "(".implode(",", $fields).") VALUES ".implode(",", $this->values);
    } elseif ($this->sets) {
      $queryList[] = "SET ".implode(",", $this->sets);
    }
    if ($this->wheres) {
      $queryList[] = implode(" ", ["WHERE", implode("&&", $this->wheres)]);
    }
    if ($this->groups) {
      $queryList[] = implode(" ", ["GROUP BY", implode(",", $this->groups)]);
    }
    if ($this->havings) {
      $queryList[] = implode(" ", ["HAVING", implode("&&", $this->havings)]);
    }
    if ($this->orders) {
      $queryList[] = implode(" ", ["ORDER BY", implode(",", $this->orders)]);
    }
    if ($this->offset || $this->limit) {
      $queryList[] = "LIMIT";
      if ($this->offset) {
        $queryList[] = $this->offset;
        if ($this->limit) {
          $queryList[] = ",";
        }
      }
      if ($this->limit) {
        $queryList[] = $this->limit;
      }
    }
    return implode(" ", $queryList);
  }

  public function query($query=Null) {
    // executes a query with standardized error message.
    $query = $query === Null ? $this->queryString() : $query;

    if (Config::DEBUG_ON) {
      $this->queryLog[] = $query;
    }
    try {
      $prepQuery = parent::prepare($query);
      if ($this->canLog()) {
        $this->logger->err($query);
      }
      $result = $prepQuery->execute($this->params);
    } catch (Exception $e) {
      $exceptionText = "Could not query MySQL database in ".$_SERVER['PHP_SELF'].".\nError: ".print_r($prepQuery->errorInfo(), True)."\nQuery: ".$query."\nParameters: ".print_r($this->params, True);
      throw new DbException($exceptionText, 0, $e);
    }
    if (!$result) {
      $exceptionText = "Could not query MySQL database in ".$_SERVER['PHP_SELF'].".\nError: ".print_r($prepQuery->errorInfo(), True)."\nQuery: ".$query."\nParameters: ".print_r($this->params, True);
      throw new DbException($exceptionText, 0, $e);
    }
    $this->reset();
    return $prepQuery;
  }
  public function raw($query) {
    return $this->query($query);
  }

  public function update() {
    $this->type = "UPDATE";
    return $this->query();
  }
  public function delete() {
    $this->type = "DELETE";
    return $this->query();
  }
  public function insert() {
    $this->type = "INSERT";
    parent::beginTransaction();
    $this->query();
    $this->lastInsertId = parent::lastInsertId();
    return parent::commit();
  }
  public function firstRow() {
    // pulls the first row returned from the query.
    $result = $this->query();
    if (!$result || $result->rowCount() < 1) {
      throw new DbException("No rows were found matching query: ".$this->queryString());
    }
    $returnValue = $result->fetch();
    $result->closeCursor();
    return $returnValue;
  }
  public function firstValue() {
    // pulls the first key from the first row returned by the query.
    $result = $this->firstRow();
    if (!$result || count($result) != 1) {
      throw new DbException("No rows were found matching query: ".$this->queryString());
    }
    $resultKeys = array_keys($result);
    return $result[$resultKeys[0]];
  }
  public function assoc($idKey=Null, $valKey=Null) {
    // pulls an associative array of columns for the first row returned by the query.
    $result = $this->query();
    if (!$result) {
      throw new DbException("No rows were found matching query: ".$this->queryString());
    }
    if ($result->rowCount() < 1) {
      return [];
    }
    $returnValue = [];
    while ($row = $result->fetch()) {
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
    $result->closeCursor();
    return $returnValue;
  }
  public function count($column="*") {
    $result = $this->firstRow();
    if (!$result) {
      throw new DbException("No rows were found matching query: ".$this->queryString());
    }
    return intval($result['COUNT('.$column.')']);
  }
}
?>