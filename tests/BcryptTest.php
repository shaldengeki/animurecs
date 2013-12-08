<?php

/*
  BcryptTest.php
  Tests for bcrypt object.
*/

require_once('core/bcrypt.php');

class BcryptTest extends PHPUnit_Framework_TestCase {
  protected $bcrypt;

  public function __construct() {
    $this->bcrypt = new Bcrypt();
  }

  /**
   * @expectedException Exception
   */
  public function testInvalidParametersThrowsException() {
    $newConn = new Bcrypt("this should throw an exception please");
  }
  public function testValidNumberofRounds() {
    $newCrypt = new Bcrypt(10);
    $this->assertInstanceOf('Bcrypt', $newCrypt);
  }
  public function testHashReturnsValidHash() {
    $newHash = $this->bcrypt->hash("test input string");
    $this->assertInternalType('string', $newHash);
    $this->assertStringStartsWith('$2a$08$', $newHash, 'starts with blowfish #rounds prefix');
    $this->assertTrue(strlen($newHash) >= 60);
  }
  public function testHashValidatesProperly() {
    $inputString = "test input string";
    $newHash = $this->bcrypt->hash($inputString);
    $this->assertTrue($this->bcrypt->verify($inputString, $newHash));
  }
}
?>