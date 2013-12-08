<?php

class Observer {
  // generic base class for event observers.
  // we expect a callback function in $updateFunction with signature (event, parent, updateParams)
  private $updateFunction;
  public function __construct($updateFunction) {
    $this->updateFunction = $updateFunction;
  }
  // function called upon event firing.
  public function update($event, BaseObject $parent, array $updateParams=Null) {
    call_user_func($this->updateFunction, $event, $parent, $updateParams);
  }
}
?>