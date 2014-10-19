<?php

class AccountController extends Controller {
  public static $URL_BASE = "account";
  public static $MODEL = "";

  public function _beforeAction() {
    // this controller _only ever_ operates on the current user's session, so the ID is the action.
    $this->_app->action = ($this->_app->id !== "") ? $this->_app->id : "index";
  }
  public function _isAuthorized($action) {
    switch ($action) {
      case 'index':
      default:
        return True;
        break;
    }
  }

  public function index() {
    if ($this->_app->user->id === 0) {
      $this->_app->display_response(401, ['You are not currently logged in.']);
    }
    $this->_app->display_response(200, $this->_app->user->serialize());
  }
}
?>