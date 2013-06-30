<?php

/*
  DbConnTest.php
  Tests for database connection and querying object.
*/

require_once('global/config.php');
require_once('global/core/database.php');

class DbConnTest extends PHPUnit_Framework_TestCase {
  private $dbConn;

  public function __construct() {
    $this->dbConn = new DbConn();
  }

  /**
   * @expectedException DbException
   */
  public function testInvalidDatabaseConnectionThrowsDbException() {
    $newConn = new DbConn("this", "throws", "an", "exception", "please");
  }
  /**
   * @expectedException DbException
   */
  public function testInvalidQueryThrowsDbException() {
    $this->dbConn->query("FAKE-QUERY");    
  }
  public function testSimpleQuery() {
    $this->assertInstanceOf('mysqli_result', $this->dbConn->query("SHOW TABLES"));
  }
  public function testQueryFirstRow() {
    $queryResult = $this->dbConn->firstRow("SHOW TABLES");
    $this->assertInternalType('array', $queryResult);
    $this->assertCount(1, $queryResult);
  }
  public function testQueryFirstValue() {
    $queryResult = $this->dbConn->firstValue("SHOW TABLES");
    $this->assertInternalType('string', $queryResult);
    $this->assertTrue(strlen($queryResult) > 0, 'is not an empty string');
  }
  public function testQueryAssocWithDefaultArguments() {
    $queryResult = $this->dbConn->assoc("SHOW TABLES");
    $this->assertInternalType('array', $queryResult);
    $this->assertNotCount(0, $queryResult);
  }
  public function testQueryAssocWithProvidedArguments() {
    $queryResult = $this->dbConn->assoc("SHOW TABLES", "Tables_in_".Config::MYSQL_DATABASE, "Tables_in_".Config::MYSQL_DATABASE);
    $this->assertInternalType('array', $queryResult);
    $this->assertNotCount(0, $queryResult);
    foreach ($queryResult as $key=>$value) {
      $this->assertEquals($key, $value, 'key and value are equal for identical selectors');
    }
  }
  public function testQueryCountWithDefaultArguments() {
    $queryResult = $this->dbConn->queryCount("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".Config::MYSQL_DATABASE."'");
    $this->assertInternalType('int', $queryResult);
    $this->assertGreaterThan(0, $queryResult, 'is greater than 0');
  }
  public function testQueryCountWithProvidedArguments() {
    $queryResult = $this->dbConn->queryCount("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".Config::MYSQL_DATABASE."'", "*");
    $this->assertInternalType('int', $queryResult);
    $this->assertGreaterThan(0, $queryResult, 'is greater than 0');
  }

}
?>