<?php

class ControllerException extends AppException {
  public function __construct($controller, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($controller->app, $messages, $code, $previous);
    $this->controller = $controller;
  }
}
class UndefinedActionException extends ControllerException {
  public function __construct($controller, $action, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($controller, $messages, $code, $previous);
    $this->action = $action;
  }
  public function __toString() {
    return implode("\n",[
      "UndefinedActionException:",
      $this->getFile().":".$this->getLine(),
      "Action: ".$this->action,
      "Controller: ".get_class($this->controller),
      "Controller actions:",
      print_r($this->controller->actions, True),
      "Messages: ".$this->formatMessages(),
      "Stack trace:",
      $this->getTraceAsString()
    ]);
  }
  public function display() {
    return "Undefined action: ".$this->action." on resource: ".get_class($this->controller);
  }
}
class UnauthorizedException extends ControllerException {
  public function __construct($controller, $action, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($controller, $messages, $code, $previous);
    $this->action = $action;
  }
  public function __toString() {
    return implode("\n",[
      "UnauthorizedException:",
      $this->getFile().":".$this->getLine(),
      "Action: ".$this->action,
      "Controller: ".get_class($this->controller),
      "User: ".$this->app->user->id,
      "Messages: ".$this->formatMessages(),
      "Stack trace:",
      $this->getTraceAsString()
    ]);
  }
  public function display() {
    return "You're not allowed to do: ".$this->action." on resource: ".get_class($this->controller).".";
  }
}

abstract class Controller {
  public function __construct($app) {
    $this->app = $app;
    $this->actions = array_diff(get_class_methods($this), get_class_methods('Controller'));
  }
  public function __get($property) {
    // this is only ever called when we attempt to perform an undefined action on the controller.
    throw new UndefinedActionException($this, $property);
  }
}
?>