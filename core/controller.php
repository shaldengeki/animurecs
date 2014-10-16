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

  // base of the url. for a controller handling "/users/shaldengeki/edit" this would be "users".
  public static $URL_BASE;

  // target model name. for, say, UserController, it'd be "User".
  public static $MODEL;

  public $_app, $_target = Null;
  public $_actions = [];

  public function __construct($app) {
    $this->_app = $app;
    $this->_target = Null;
    $this->_actions = array_diff(get_class_methods($this), get_class_methods('Controller'));
  }
  public function __get($property) {
    // this is only ever called when we attempt to perform an undefined action on the controller.
    throw new UndefinedActionException($this, $property);
  }
  public function _beforeAction() {
    // called before attempting to call action method.
    // by default, initializes target to an instance of the targeted model.
    $modelName = static::$MODEL;
    $id = intval($this->_app->id);
    if ($id > 0) {
      $this->_target = $modelName::FindById($this->_app, intval($this->_app->id));
    } else {
      $this->_target = new $modelName($this->_app, $id);
    }
  }
  public function _performAction($action) {
    // performs a given action, after checking for authorization.
    if (!isset($this->_actions, $action)) {
      throw new UndefinedActionException($this, $action);
    }
    if (!$this->_isAuthorized($action)) {
      throw new UnauthorizedException($this, $action);
    }
    $this->{$action}();
  }

  // returns a boolean reflecting whether or not the given action is authorized.
  abstract public function _isAuthorized($action);
}
?>