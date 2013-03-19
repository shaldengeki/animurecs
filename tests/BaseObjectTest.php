<?php

/*
  BaseObjectTest.php
  Tests for base object.
*/

require_once('global/config.php');
require_once('global/core/database.php');
require_once('global/application.php');
require_once('global/core/base_object.php');
require_once('global/traits/feedable.php');
require_once('global/traits/commentable.php');
require_once('global/models/user.php');

class BaseObjectTestObserver {
  public function update($event, BaseObject $parent, array $updateParams=Null) {
    $parent->beforeCreateTestParam = 40.2;
    $parent->afterCreateTestParam = 41.2;
    $parent->beforeUpdateTestParam = $updateParams['beforeUpdateTestParam'];
    $parent->afterUpdateTestParam = $updateParams['afterUpdateTestParam'];
    $parent->beforeDeleteTestParam = 44.2;
    $parent->afterDeleteTestParam = 45.2;
  }
}

class BaseObjectTest extends PHPUnit_Framework_TestCase {
  private $baseObject, $app;

  public function __construct() {
    $this->app = new Application();
    $this->baseObject = $this->getMockForAbstractClass('BaseObject', array($this->app));
    $this->baseObject->expects($this->any())
                      ->method('allow')
                      ->will($this->returnValue(True));
  }
  public function testmodelName() {
    $this->assertStringStartsWith('Mock_BaseObject', $this->baseObject->modelName());
  }
  public function testhumanizeParameter() {
    $this->assertEquals('helloThereYoungPadawan', $this->baseObject->humanizeParameter('hello_there_young_padawan'), 'underscores removed and first letters capitalized');
    $this->assertEquals(0.5, $this->baseObject->humanizeParameter(0.5), 'numeric input left untouched');
    $this->assertEquals('', $this->baseObject->humanizeParameter(''), 'empty string left untouched');
    $this->assertEquals('12345', $this->baseObject->humanizeParameter('12345'), 'numeric string left untouched');
    $this->assertEquals('false', $this->baseObject->humanizeParameter('false'));
  }
  public function testset() {
    $this->assertEquals('test value', $this->baseObject->set(array('test_parameter' => 'test value'))->testParameter);
    $this->assertEquals(3.5, $this->baseObject->set(array('test_parameter' => 3.5))->testParameter);
    $this->assertEquals(false, $this->baseObject->set(array('testing_0' => false))->testing0);
    $this->assertEquals('test value', $this->baseObject->set(array('123' => 'test value'))->{123});
    $this->assertInstanceOf('DateTime', $this->baseObject->set(array('created_at' => '1/1/2001 05:23:12'))->createdAt);
    $this->assertInstanceOf('DateTime', $this->baseObject->set(array('updated_at' => '1/1/2001 05:23:12'))->updatedAt);
  }
  public function testproperlyFormedBaseObjectPassesValidation() {
    $this->assertTrue($this->baseObject->validate(array('id' => 1, 'created_at' => '1/1/2001 05:23:12', 'updated_at' => '1/1/2001 05:23:12')));
  }
  /**
   * @expectedException ValidationException
   */
  public function testemptyArrayThrowsValidationException() {
    $this->baseObject->validate(array());
  }
  /**
   * @expectedException ValidationException
   */
  public function testnegativeIDThrowsValidationException() {
    $this->baseObject->validate(array('id' => -1, 'created_at' => '1/1/2001 05:23:12', 'updated_at' => '1/1/2001 05:23:12'));
  }
  /**
   * @expectedException ValidationException
   */
  public function testnonIntegralIDThrowsValidationException() {
    $this->baseObject->validate(array('id' => 3.5, 'created_at' => '1/1/2001 05:23:12', 'updated_at' => '1/1/2001 05:23:12'));
  }
  /**
   * @expectedException ValidationException
   */
  public function testinvalidCreatedAtThrowsValidationException() {
    $this->baseObject->validate(array('id' => 1, 'created_at' => '1/1/2001 05:23:62', 'updated_at' => '1/1/2001 05:23:12'));
  }
  /**
   * @expectedException ValidationException
   */
  public function testinvalidUpdatedAtThrowsValidationException() {
    $this->baseObject->validate(array('id' => 1, 'created_at' => '1/1/2001 05:23:12', 'updated_at' => '1/1/2001 05:23:62'));
  }
  public function testbeforeCreate() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.beforeCreate', $observer);
    $this->baseObject->beforeCreate(array('beforeCreateTestParam' => 40.2));
    $this->assertEquals(40.2, $this->baseObject->beforeCreateTestParam);
  }
  public function testafterCreate() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.afterCreate', $observer);
    $this->baseObject->afterCreate(array('beforeCreateTestParam' => 40.2));
    $this->assertEquals(41.2, $this->baseObject->afterCreateTestParam);
  }
  public function testbeforeUpdate() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.beforeUpdate', $observer);
    $this->baseObject->beforeUpdate(array('beforeUpdateTestParam' => 42.2));
    $this->assertEquals(42.2, $this->baseObject->beforeUpdateTestParam);
  }
  public function testafterUpdate() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.afterUpdate', $observer);
    $this->baseObject->afterUpdate(array('afterUpdateTestParam' => 43.2));
    $this->assertEquals(43.2, $this->baseObject->afterUpdateTestParam);
  }
  public function testbeforeDelete() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.beforeDelete', $observer);
    $this->baseObject->beforeDelete();
    $this->assertEquals(44.2, $this->baseObject->beforeDeleteTestParam);
  }
  public function testafterDelete() {
    $observer = new BaseObjectTestObserver();
    $this->app->bind($this->baseObject->modelName().'.afterDelete', $observer);
    $this->baseObject->afterDelete();
    $this->assertEquals(45.2, $this->baseObject->afterDeleteTestParam);
  }
}