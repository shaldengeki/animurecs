<?php
class TagsController extends Controller {
  public static $URL_BASE = "tags";
  public static $MODEL = "Tag";

  public function _beforeAction() {
    if ($this->_app->id !== "") {
      $this->_target = Tag::Get($this->_app, ['name' => str_replace("_", " ", rawurldecode($this->_app->id))]);
    } else {
      $this->_target = new Tag($this->_app, 0);
    }
  }

  public function _isAuthorized($action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      /* Only for staff members. */
      case 'new':
      case 'edit':
      case 'delete':
        if ($this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;

      /* Only for logged-in members. */
      case 'token_search':
        if ($this->_app->user->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* Public views. */
      case 'related_tags':
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
      $this->_app->display_error(404, "This tag could not be found.");
    }
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $tagName = $this->_target->name;
    $deleteTag = $this->delete();
    if ($deleteTag) {
      $this->_app->display_success(200, 'Successfully deleted '.$tagName.'.');
    } else {
      $this->_app->display_error(500, 'An error occurred while deleting '.$tagName.'.');
    }
  }
  public function edit() {
    if (isset($_POST['tags']) && is_array($_POST['tags'])) {
      $updateTag = $this->_target->create_or_update($_POST['tag']);
      if ($updateTag) {
        $this->_app->display_success(200, "Successfully updated ".$this->_target->name.".");
      } else {
        $this->_app->display_error(500, "An error occurred while updating ".$this->_target->name.".");
      }
    }
    $this->_app->display_error(400, "You must provide tag info to update.");
  }
  public function index() {
    if (isset($_POST['tags']) && is_array($_POST['tags'])) {
      $updateTag = $this->_target->create_or_update($_POST['tag']);
      if ($updateTag) {
        $this->_app->display_success(200, "Successfully created tag: ".$this->_target->name.".");
      } else {
        $this->_app->display_error(500, "An error occurred while creating tag: ".$_POST['tags']['name'].".");
      }
    }
    $perPage = 25;
    if ($this->_app->user->isAdmin()) {
      $pages = ceil(Tag::Count($this->_app)/$perPage);
      $tagQuery = $this->_app->dbConn->table(Tag::$TABLE)->order('name ASC')->offset((intval($this->_app->page)-1)*$perPage)->limit($perPage)->query();
    } else {
      $pages = ceil(Tag::Count($this->_app, ['approved_on != ""'])/$perPage);
      $tagQuery = $this->_app->dbConn->table(Tag::$TABLE)->where(['approved_on != ""'])->order('name ASC')->offset((intval($this->_app->page)-1)*$perPage)->limit($perPage)->query();
    }
    $tags = [];
    while ($tag = $tagQuery->fetch()) {
      $tagObj = new Tag($this->_app, intval($tag['id']));
      $tagObj->set($tag);
      $tags[] = $tagObj->serialize();
    }
    $this->_app->display_response(200, [
      'page' => $this->_app->page,
      'pages' => $pages,
      'tags' => $tags
    ]);
  }
  public function related_tags() {
    /* Given a current page of anime that the user is viewing that are all tagged with a particular tag,
      return a list of tags, sorted by tag type, with the number of anime on the page that have that tag.
    */
    /* TODO: work predictions into tagCountsByType */
    $perPage = 25;
    if ($this->_app->user->loggedIn()) {
      try {
        $predictedRatings = $this->_app->recsEngine->predict($this->_app->user, $this->_target->anime, 0, count($this->_target->anime));
      } catch (CurlException $e) {
        $this->_app->log_exception($e);
        $predictedRatings = False;
      }
      if (is_array($predictedRatings)) {
        arsort($predictedRatings);
      } else {
        $predictedRatings = $this->_target->anime;
      }
      $predictions = array_slice($predictedRatings, (intval($this->_app->page)-1)*$perPage, intval($perPage), True);
      $group = new AnimeGroup($this->_app, array_keys($predictions));
    } else {
      $group = new AnimeGroup($this->_app, array_keys(array_slice($this->_target->anime, (intval($this->_app->page)-1)*$perPage, intval($perPage), True)));
      $predictions = [];
    }

    $group->tags()->load('info');
    $tagCounts = [];
    foreach ($group->tagCounts() as $id=>$countArray) {
      $tagCounts[$id] = $countArray['count'];
    }

    $numTypes = 0;
    $tagTypes = [];
    $tagCountsByType = [];
    foreach ($group->tags() as $tag) {
      if (!isset($tagTypes[$tag->type->id])) {
        $tagTypes[$tag->type->id] = $numTypes;
        $tagCountsByType[] = [
          'type' => ['id' => $tag->type->id, 'name' => $tag->type->name],
          'tags' => [['id' => $tag->id, 'name' => $tag->name, 'count' => $tagCounts[$tag->id]]]
        ];
        $numTypes++;
      } else {
        $tagCountsByType[$tagTypes[$tag->type->id]]['tags'][] = ['id' => $tag->id, 'name' => $tag->name, 'count' => $tagCounts[$tag->id]];
      }
    }

    $this->_app->display_response(200, $tagCountsByType);
  }
  public function show() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "This tag could not be found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }
  public function token_search() {
    $tags = [];
    if (isset($_REQUEST['term'])) {
      $tags = $this->_app->dbConn->table(static::$TABLE)->fields('id', 'name')->match('name', $_REQUEST['term'])->order('name ASC')->assoc();
    }
    $this->_app->display_response(200, $tags);
  }
}
?>