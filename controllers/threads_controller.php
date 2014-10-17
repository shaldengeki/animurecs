<?php
class ThreadsController extends Controller {
  public static $URL_BASE = "threads";
  public static $MODEL = "Thread";

  public function _beforeAction() {
    if ($this->_app->id !== "") {
      $id = intval(explode("-", rawurldecode($this->_app->id))[0]);
      $this->_target = Thread::FindById($this->_app, $id);
    } else {
      $this->_target = new Thread($this->_app, 0);
    }
  }

  public function _isAuthorized($action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'remove_tag':
      case 'edit':
      case 'delete':
        if (($this->_target->user->id == $this->_app->user->id && $this->_target->user->id === $_POST['threads']['user_id']) || $this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'new':
        if ($this->_app->user->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'token_search':
      case 'feed':
      case 'show':
      case 'related':
        return True;
        break;
      case 'index':
        if ($this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }

  public function delete() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "This thread could not be found.");
    }
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $threadTitle = $this->_target->title;
    $deleteThread = $this->_target->delete();
    if ($deleteThread) {
      $this->_app->display_success(200, 'Successfully deleted '.$threadTitle.'.');
    } else {
      $this->_app->display_error(500, 'An error occurred while deleting '.$threadTitle.'.');
    }
  }
  public function edit() {
    if (isset($_POST['threads']) && is_array($_POST['threads'])) {
      $updateThread = $this->_target->create_or_update($_POST['thread']);
      if ($updateThread) {
        $this->_app->display_success(200, "Successfully updated ".$this->_target->title.".");
      } else {
        $this->_app->display_error(500, "An error occurred while updating ".$this->_target->title.".");
      }
    }
    $this->_app->display_error(400, "You must provide thread info to update.");    
  }
  public function feed() {
    $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
    $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
    $entries = [];
    foreach (array_sort_by_method($this->_target->entries($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc') as $entry) {
      $entries[] = $entry->serialize();
    }
    $this->_app->display_response(200, $entries);
  }
  public function index() {
    if (isset($_POST['threads']) && is_array($_POST['threads'])) {
      $createThread = $this->_target->create_or_update($_POST['thread']);
      if ($createThread) {
        $this->_app->display_success(200, "Successfully created ".$this->_target->title.".");
      } else {
        $this->_app->display_error(500, "An error occurred while creating ".$this->_target->title.".");
      }
    }
    $perPage = 25;
    $pages = ceil(Thread::Count($this->_app)/$perPage);
    $threadsQuery = $this->_app->dbConn->table(Thread::$TABLE)->order('updated_at DESC')->offset((intval($this->_app->page)-1)*$perPage)->limit($perPage)->query();
    $threads = [];
    while ($thread = $threadsQuery->fetch()) {
      $threadObj = new Thread($this->_app, intval($thread['id']));
      $threadObj->set($thread);
      $threads[] = $threadObj->serialize();
    }
    $this->_app->display_response(200, [
      'page' => $this->_app->page,
      'pages' => $pages,
      'threads' => $threads
    ]);
  }
  public function show() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "This thread could not be found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }
}
?>