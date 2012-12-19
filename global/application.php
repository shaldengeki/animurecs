<?php

class Application {
  private $_config, $_classes, $_observers=[];
  public $achievements=[];
  public $dbConn, $recsEngine, $serverTimeZone, $outputTimeZone, $user, $target, $startRender=Null;

  public $model,$action="";
  public $id=0;
  public $status,$class="";
  public $page=1;

  private function _loadDependency($relPath) {
    // requires an application dependency from its path relative to the DOCUMENT_ROOT
    // e.g. /global/config.php
    require_once($_SERVER['DOCUMENT_ROOT'].$relPath);
  }
  private function _connectDB() {
    if ($this->dbConn === Null) {
      $this->dbConn = new DbConn();
    }
    return $this->dbConn;
  }
  private function _connectRecsEngine() {
    if ($this->recsEngine === Null) {
      $this->recsEngine = new RecsEngine(Config::RECS_ENGINE_HOST, Config::RECS_ENGINE_PORT);
    }
    return $this->recsEngine;
  }
  private function _loadDependencies() {
    $this->_loadDependency("/global/config.php");

    // core models, including base_object.
    foreach (glob(Config::APP_ROOT."/global/core/*.php") as $filename) {
      require_once($filename);
    }

    // include all traits before models that depend on them.
    foreach (glob(Config::APP_ROOT."/global/traits/*.php") as $filename) {
      require_once($filename);
    }

    // generic base models that extend base_object.
    foreach (glob(Config::APP_ROOT."/global/base/*.php") as $filename) {
      require_once($filename);
    }

    // group models.
    foreach (glob(Config::APP_ROOT."/global/groups/*.php") as $filename) {
      require_once($filename);
    }

    $nonLinkedClasses = count(get_declared_classes());

    // models that have URLs.
    foreach (glob(Config::APP_ROOT."/global/models/*.php") as $filename) {
      require_once($filename);
    }

    session_start();
    $this->dbConn = $this->_connectDB();
    $this->recsEngine = $this->_connectRecsEngine();
    
    date_default_timezone_set(Config::SERVER_TIMEZONE);
    $this->serverTimeZone = new DateTimeZone(Config::SERVER_TIMEZONE);
    $this->outputTimeZone = new DateTimeZone(Config::OUTPUT_TIMEZONE);

    // _classes is a modelUrl:modelName mapping for classes that are to be linked.
    foreach (array_slice(get_declared_classes(), $nonLinkedClasses) as $className) {
      $blankClass = new $className($this, 0);
      if (!isset($this->_classes[$blankClass->modelUrl()])) {
        $this->_classes[$blankClass->modelUrl] = $className;
      }
    }

    // include all achievements.
    // achievements is an ID:object mapping of achievements.
    $nonAchievementClasses = count(get_declared_classes());
    foreach (glob(Config::APP_ROOT."/global/achievements/*.php") as $filename) {
      require_once($filename);
    }
    $achievementSlice = array_slice(get_declared_classes(), $nonAchievementClasses);
    foreach ($achievementSlice as $achievementName) {
      $blankAchieve = new $achievementName($this);
      $this->achievements[$blankAchieve->id] = $blankAchieve;

      // bind each achievement to its events.
      foreach ($blankAchieve->events() as $event) {
        $this->bind($event, $blankAchieve);
      }
    }
  }

  // bind/unbind/fire event handlers for objects.
  // event names are of the form modelName.eventName
  // e.g. User.afterCreate
  public function bind($event, $observer) {
    // binds a function to an event.
    // can be either anonymous function or string name of class method.
    if (!method_exists($observer, 'update')) {
      if (Config::DEBUG_ON) {
        throw new InvalidArgumentException(sprintf('Invalid observer: %s.', print_r($observer, True)));
      } else {
        return False;
      }
    }
    if (!isset($this->_observers[$event])) {
      $this->_observers[$event] = array($observer);
    } else {
      //check if this observer is bound to this event already
      $elements = array_keys($this->_observers[$event], $o);
      $notinarray = True;
      foreach ($elements as $value) {
        if ($observer === $this->_observers[$event][$value]) {
            $notinarray = False;
            break;
        }
      }
      if ($notinarray) {
        $this->_observers[$event][] = $observer;
      }
    }
    return array($event, count($this->_observers[$event])-1);
  }
  public function unbind($observer) {
    // callback is array of form [event_name, position]
    // alternatively, also accepts a string for event name.
    if (is_array($observer)) {
      if (count($observer) < 2) {
        return False;
      } elseif (!isset($this->_observers[$observer[0]])) {
        return True;
      }
      unset($this->_observers[$observer[0]][$observer[1]]);
      return !isset($this->_observers[$observer[0]][$observer[1]]);
    } else {
      if (!isset($this->_observers[$observer])) {
        return True;
      }
      unset($this->_observers[$observer]);
      return !isset($this->_observers[$observer]);
    }
  }
  public function fire($event, $object, $updateParams=Null) {
    if (!isset($this->_observers[$event])) {
      return;
    }
    foreach ($this->_observers[$event] as $observer) {
      if (!method_exists($observer, 'update')) {
        continue;
      }
      $observer->update($event, $object, $updateParams);
    }
  }
  public function display_error($code) {
    return $this->view('header').$this->view(intval($code)).$this->view('footer');
  }
  public function check_partial_include($filename) {
    // displays the standard 404 page if the user is requesting a partial directly.
    if (str_replace("\\", "/", $filename) === $_SERVER['SCRIPT_FILENAME']) {
      echo $this->display_error(404);
      exit;
    }
  }
  public function init() {
    $this->startRender = microtime(true);
    $this->_loadDependencies();

    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
      $this->user = new User($this, intval($_SESSION['id']));
      // if user's last action was 5 or more minutes ago, update his/her last-active time.
      if ($this->user->lastActive->diff(new DateTime("now", $this->serverTimeZone))->i >= 5) {
        $this->user->updateLastActive();
      }
    } else {
      $this->user = new User($this, 0, "Guest");
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
      $this->target = new $this->model($this, $this->id);
      if ($this->id !== 0) {
        try {
          $foo = $this->target->getInfo();
        } catch (Exception $e) {
          $blankModel = new $this->model($this);
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
        echo $this->target->render();
      }
    }
  }
  public function view($view="index", $params=Null) {
    // includes a provided application-level view.
    $file = joinPaths(Config::APP_ROOT, 'views', 'application', "$view.php");
    if (file_exists($file)) {
      ob_start();
      include($file);
      return ob_get_clean();
    }
    return False;
  }
  public function render($text, $params=Null) {
    // renders the given HTML text surrounded by the standard application header and footer.
    // passes $params into the header and footer views.
    $appVars = get_object_vars($this);
    if ($params !== Null && is_array($params)) {
      foreach ($params as $key=>$value) {
        $appVars[$key] = $value;
      }
    }
    echo $this->view('header', $appVars);
    echo $text;
    echo $this->view('footer', $appVars);
    exit;
  }
}

?>