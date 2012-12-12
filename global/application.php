<?php

class Application {
  private $_config, $_achievements, $_classes=[];
  public $dbConn, $recsEngine, $serverTimeZone, $outputTimeZone, $user, $target=Null;

  public $model,$action="";
  public $id=0;
  public $status,$class="";
  public $page=1;

  private function _loadDependency($relPath) {
    // requires an application dependency from its path relative to the DOCUMENT_ROOT
    // e.g. /global/config.php
    require_once($_SERVER['DOCUMENT_ROOT'].$relPath);
  }

  private function connectDB() {
    if ($this->dbConn === Null) {
      $this->dbConn = new DbConn(Config::MYSQL_HOST, Config::MYSQL_USERNAME, Config::MYSQL_PASSWORD, Config::MYSQL_DATABASE);
    }
    return $this->dbConn;
  }

  private function connectRecsEngine() {
    if ($this->recsEngine === Null) {
      $this->recsEngine = new RecsEngine(Config::RECS_ENGINE_HOST, Config::RECS_ENGINE_PORT);
    }
    return $this->recsEngine;
  }

  private function _loadDependencies() {
    $this->_loadDependency("/global/config.php");

    $this->_loadDependency("/global/bcrypt.php");
    $this->_loadDependency("/global/database.php");
    $this->_loadDependency("/global/curl.php");
    $this->_loadDependency("/global/recs_engine.php");

    $this->_loadDependency("/global/aliasable.php");
    $this->_loadDependency("/global/commentable.php");
    $this->_loadDependency("/global/feedable.php");

    $this->_loadDependency("/global/base_object.php");
    $this->_loadDependency("/global/base_list.php");
    $this->_loadDependency("/global/base_entry.php");
    $this->_loadDependency("/global/base_achievement.php");

    $nonLinkedClasses = count(get_declared_classes());

    $this->_loadDependency("/global/tag_type.php");
    $this->_loadDependency("/global/tag.php");

    $this->_loadDependency("/global/anime.php");
    $this->_loadDependency("/global/alias.php");
    $this->_loadDependency("/global/anime_list.php");
    $this->_loadDependency("/global/anime_entry.php");

    $this->_loadDependency("/global/user.php");

    $this->_loadDependency("/global/comment.php");
    $this->_loadDependency("/global/comment_entry.php");

    session_start();
    $this->dbConn = $this->connectDB();
    $this->recsEngine = $this->connectRecsEngine();
    
    date_default_timezone_set(Config::SERVER_TIMEZONE);
    $this->serverTimeZone = new DateTimeZone(Config::SERVER_TIMEZONE);
    $this->outputTimeZone = new DateTimeZone(Config::OUTPUT_TIMEZONE);

    // _classes is a modelUrl:modelName mapping for classes that are to be linked.
    foreach (array_slice(get_declared_classes(), $nonLinkedClasses) as $className) {
      $blankClass = new $className($this->dbConn, 0);
      if (!isset($this->_classes[$blankClass->modelUrl])) {
        $this->_classes[$blankClass->modelUrl] = $className;
      }
    }

    // include all achievements.
    // _achievements is an ID:name mapping of achievements.
    $nonAchievementClasses = count(get_declared_classes());
    foreach (glob($_SERVER['DOCUMENT_ROOT']."/global/achievements/*.php") as $filename) {
      require_once($filename);
    }
    $achievementSlice = array_slice(get_declared_classes(), $nonAchievementClasses);
    foreach ($achievementSlice as $achievementName) {
      $blankAchieve = new $achievementName($this->dbConn);
      $this->_achievements[$blankAchieve->id] = $achievementName;
    }

    $this->_loadDependency("/global/display.php");
    $this->_loadDependency("/global/misc.php");
  }

  public function display_error($code) {
    return $this->view('header').$this->view(403, $this->user, get_object_vars($this)).$this->view('footer');
  }

  public function init() {
    $this->_loadDependencies();

    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
      $this->user = new User($this->dbConn, intval($_SESSION['id']));
      // if user's last action was 5 or more minutes ago, update his/her last-active time.
      if ($this->user->lastActive->diff(new DateTime("now", $this->serverTimeZone))->i >= 5) {
        $this->user->updateLastActive();
      }
    } else {
      $this->user = new User($this->dbConn, 0, "Guest");
    }
    if (isset($_REQUEST['status'])) {
      $this->status = $_REQUEST['status'];
    }
    if (isset($_REQUEST['class'])) {
      $this->class = $_REQUEST['class'];
    }

    if (isset($_REQUEST['model']) && isset($this->_classes[$_REQUEST['model']])) {
      $this->model = $this->_classes[$_REQUEST['model']];
    }
    if (isset($_REQUEST['id'])) {
      $this->id = intval($_REQUEST['id']);
    }
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
      if ($this->id) {
        $this->action = 'show';
      } else {
        $this->action = 'index';
      }
    } else {
      $this->action = $_REQUEST['action'];
    }
    if (!isset($_REQUEST['page'])) {
      $this->page = 1;
    } else {
      $this->page = max(1, intval($_REQUEST['page']));
    }

    if (isset($this->model) && $this->model !== "") {
      if (!class_exists($this->model)) {
        redirect_to($this->user->url(), array("status" => "This thing doesn't exist!", "class" => "error"));
      }
      $this->target = new $this->model($this->dbConn, $this->id);
      if ($this->id !== 0) {
        try {
          $foo = $this->target->getInfo();
        } catch (Exception $e) {
          $blankModel = new $this->model($this->dbConn);
          redirect_to($blankModel->url("index"), array("status" => "The ".strtolower($this->model)." you specified does not exist.", "class" => "error"));
        }
        if ($this->action === "new") {
          $this->action = "edit";
        }
      } elseif ($this->action === "edit") {
        $this->action = "new";
      }
      if (!$this->target->allow($this->user, $this->action)) {
        echo $this->display_error(403);
        exit;
      } else {
        echo $this->target->render($this);
      }
    }
  }

  public function view($view="index", $currentUser=Null, $params=Null) {
    $file = joinPaths(Config::APP_ROOT, 'views', 'application', "$view.php");
    if (file_exists($file)) {
      ob_start();
      include($file);
      return ob_get_clean();
    }
    return False;
  }
  public function render($text, $params=Null) {
    $appVars = get_object_vars($this);
    if ($params !== Null && is_array($params)) {
      foreach ($params as $key=>$value) {
        $appVars[$key] = $value;
      }
    }
    echo $this->view('header', $this->user, $appVars);
    echo $text;
    echo $this->view('footer', $this->user, $appVars);
    exit;
  }
}

?>