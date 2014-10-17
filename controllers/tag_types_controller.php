<?php
class TagTypesController extends Controller {
  public static $URL_BASE = "tag_types";
  public static $MODEL = "TagType";

  public function _beforeAction() {
    if ($this->_app->id !== "") {
      $this->_target = TagType::Get($this->_app, ['name' => str_replace("_", " ", rawurldecode($this->_app->id))]);
    } else {
      $this->_target = new TagType($this->_app, 0);
    }
  }

  public function _isAuthorized($action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      // case 'approve':
      case 'new':
      case 'edit':
      case 'delete':
        if ($this->_app->user->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'show':
      case 'index':
        return True;
        break;
      default:
        return False;
        break;
    }
  }

  public function delete() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "This tag type could not be found.");
    }
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $tagTypeName = $this->_target->name;
    $deleteTagType = $this->_target->delete();
    if ($deleteTagType) {
      $this->_app->display_success(200, 'Successfully deleted '.$tagTypeName.'.');
    } else {
      $this->_app->display_error(500, 'An error occurred while deleting '.$tagTypeName.'.');
    }    
  }
  public function edit() {
    if (isset($_POST['tag_types']) && is_array($_POST['tag_types'])) {
      $updateTagType = $this->_target->create_or_update($_POST['tag_types']);
      if ($updateTagType) {
        $this->_app->display_success(200, "Successfully updated ".$this->_target->name.".");
      } else {
        $this->_app->display_error(500, "An error occurred while updating ".$this->_target->name.".");
      }
    }
    $this->_app->display_error(400, "You must provide tag type info to update.");
  }
  public function index() {
    if (isset($_POST['tag_types']) && is_array($_POST['tag_types'])) {
      $createTagType = $this->_target->create_or_update($_POST['tag_types']);
      if ($createTagType) {
        $this->_app->display_success(200, "Successfully created ".$this->_target->name.".");
      } else {
        $this->_app->display_error(500, "An error occurred while creating ".$_POST['tag_types']['name'].".");
      }
    }
    $perPage = 25;
    $pages = ceil(TagType::Count($this->_app)/$perPage);
    $tagTypesQuery = $this->_app->dbConn->table(TagType::$TABLE)->order('name ASC')->offset((intval($this->_app->page)-1)*$perPage)->limit($perPage)->query();
    $tagTypes = [];
    while ($tagType = $tagTypesQuery->fetch()) {
      $tagTypeObj = new TagType($this->_app, intval($tagType['id']));
      $tagTypeObj->set($tagType);
      $tagTypes[] = $tagTypeObj->serialize();
    }
    $this->_app->display_response(200, [
      'page' => $this->_app->page,
      'pages' => $pages,
      'tagTypes' => $tagTypes
    ]);
  }
  public function show() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "This tag type could not be found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }
}