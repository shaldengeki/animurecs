<?php

class AppException extends Exception {
  private $app, $messages;
  public function __construct($app, $messages=Null, $code=0, Exception $previous=Null) {
    if (is_array($messages)) {
      $this->messages = $messages;
    } else {
      $this->messages = array($messages);
    }
    parent::__construct($this->formatMessages(), $code, $previous);
    $this->app = $app;
    $this->app->statsd->increment('AppException');
  }
  public function messages() {
    return $this->messages;
  }
  public function formatMessages() {
    // displays a list of this exception's messages.
    if (count($this->messages) > 0) {
      return implode("\n", $this->messages);
    } else {
      return "";
    }
  }
  public function listMessages() {
    // returns an unordered HTML list of this exception's messages.
    if (count($this->messages) > 0) {
      return "<ul><li>".implode("</li><li>", $this->messages)."</li></ul>";
    } else {
      return "";
    }
  }
  public function __toString() {
    return get_class($this).":\n".$this->getFile().":".$this->getLine()."\nMessages: ".$this->formatMessages()."\nStack trace:\n".$this->getTraceAsString()."\n";
  }
  public function display() {
    // displays end user-friendly output explaining the exception that occurred.
    echo "A server error occurred, and I wasn't able to complete your request. I've let the staff know something's wrong - apologies for the problems!";
  }
}

function ErrorHandler($errno, $errstr, $errfile, $errline, array $errcontext) {
  // error was suppressed with the @-operator
  if (0 === error_reporting()) {
    return false;
  }
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

class Application {
  /*
    Class that serves to store all the application-relevant parameters
    E.g. this request's model/action/id/format
    And configuration parameters
    Also serves as DI container (stores database, logger, recommendation engine objects)
  */
  private $_config, $_classes, $_observers=[], $_statsdConn;
  protected $totalPoints=Null;
  public $achievements,$delayedMessages=[];
  public $statsd, $logger, $cache, $dbConn, $recsEngine, $serverTimeZone, $outputTimeZone, $user, $target, $startRender, $csrfToken=Null;

  public $model,$action,$status,$class="";
  public $id=0;
  public $ajax=False;
  public $page=1;
  public $csrfField="csrf_token";

  private function _generateCSRFToken() {
    // generates a CSRF token, if one has not already been created for this Application object.
    if ($this->csrfToken === Null) {
      $this->csrfToken = hash('sha256', 'csrf-token:'.session_id());
    }
    return $this->csrfToken;
  }
  private function _loadDependency($absPath) {
    // requires an application dependency from its absolute path
    // e.g. /ABSOLUTE_PATH/global/config.php
    if (file_exists($absPath)) {
      include_once($absPath);
    } else {
      throw new AppException($this, 'required library not found at '.$absPath);
    }
  }
  private function _connectStatsD() {
    if ($this->statsd == Null) {
      $this->_statsdConn = new \Domnikl\Statsd\Connection\Socket('localhost', 8125);
      $this->statsd = new \Domnikl\Statsd\Client($this->_statsdConn, "shaldengeki.animurecs");
    }
    return $this->statsd;
  }
  private function _connectLogger() {
    if (Config::DEBUG_ON) {
      return log::factory('file', Config::LOG_FILE, 'Animurecs', array(), PEAR_LOG_DEBUG);
    } else {
      return log::factory('file', Config::LOG_FILE, 'Animurecs', array(), PEAR_LOG_WARNING);
    }
  }
  private function _connectCache() {
    if ($this->cache === Null) {
      $this->cache = new Cache();
    }
    return $this->cache;
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
    // Loads configuration and all application objects from library files.
    // Connects database, logger, recommendation engine.
    // Creates a list of objects that can be accessed via URL.

    $this->_loadDependency("./global/config.php");
    require_once(Config::APP_ROOT.'/vendor/autoload.php');

    $this->statsd = $this->_connectStatsD();

    require_once('Log.php');
    $this->logger = $this->_connectLogger();

    try {
      // core models, including base_object.
      foreach (glob(Config::APP_ROOT."/global/core/*.php") as $filename) {
        $this->_loadDependency($filename);
      }

      // include all traits before models that depend on them.
      foreach (glob(Config::APP_ROOT."/global/traits/*.php") as $filename) {
        $this->_loadDependency($filename);
      }

      // generic base models that extend base_object.
      foreach (glob(Config::APP_ROOT."/global/base/*.php") as $filename) {
        $this->_loadDependency($filename);
      }

      // group models.
      foreach (glob(Config::APP_ROOT."/global/groups/*.php") as $filename) {
        $this->_loadDependency($filename);
      }

      $nonLinkedClasses = count(get_declared_classes());

      // models that have URLs.
      foreach (glob(Config::APP_ROOT."/global/models/*.php") as $filename) {
        $this->_loadDependency($filename);
      }
    } catch (AppException $e) {
      $this->logger->alert($e);
      $this->display_error(500);
    }

    session_start();
    try {
      $this->cache = $this->_connectCache();
    } catch (CacheException $e) {
      $this->statsd->increment("CacheException");
      $this->logger->alert($e->__toString());
      $this->display_exception($e);
    }
    try {
      $this->dbConn = $this->_connectDB();
    } catch (DbException $e) {
      $this->statsd->increment("DbException");
      $this->logger->alert($e->__toString());
      $this->display_exception($e);
    }
    try {
      $this->recsEngine = $this->_connectRecsEngine();
    } catch (AppException $e) {
      $this->logger->warning($e->__toString());
      $this->display_exception($e);
    }
    
    date_default_timezone_set(Config::SERVER_TIMEZONE);
    $this->serverTimeZone = new DateTimeZone(Config::SERVER_TIMEZONE);
    $this->outputTimeZone = new DateTimeZone(Config::OUTPUT_TIMEZONE);

    // _classes is a modelUrl:modelName mapping for classes that are to be linked.
    foreach (array_slice(get_declared_classes(), $nonLinkedClasses) as $className) {
      if (!isset($this->_classes[$className::modelUrl()])) {
        $this->_classes[$className::modelUrl()] = $className;
      }
    }

    // include all achievements.
    // achievements is an ID:object mapping of achievements.
    $nonAchievementClasses = count(get_declared_classes());
    try {
      foreach (glob(Config::APP_ROOT."/global/achievements/*.php") as $filename) {
        $this->_loadDependency($filename);
      }
    } catch (AppException $e) {
      $this->logger->log($e, PEAR_LOG_ALERT);
      $this->display_error(500);
    }
    $achievementSlice = array_slice(get_declared_classes(), $nonAchievementClasses);
    foreach ($achievementSlice as $achievementName) {
      $blankAchieve = new $achievementName($this);
      $this->achievements[$blankAchieve->id] = $blankAchieve;
    }
    ksort($this->achievements);
  }
  private function _bindEvents() {
    // binds all event observers.

    // clear cache for a database object every time it's updated or deleted.
    $this->bind(array('BaseObject.afterUpdate', 'BaseObject.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parentClass = get_class($parent);
      $parent->app->cache->delete($parentClass::modelName()."-".intval($parent->id));
    }));
    $this->bind(array('Anime.afterUpdate', 'Anime.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parentClass = get_class($parent);
      $parent->app->cache->delete($parentClass::modelName()."-".intval($parent->id)."-tagIDs");
    }));
    $this->bind(array('AnimeList.afterUpdate', 'AnimeList.afterCreate', 'AnimeList.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete("Anime-".intval($updateParams['anime_id'])."-similar");
    }));
    $this->bind(array('AnimeEntry.afterUpdate', 'AnimeEntry.afterCreate', 'AnimeEntry.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete("Anime-".$parent->animeId()."-similar");
    }));


    // statsd metrics.

    $this->bind(array('Anime.afterCreate'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("anime.count");
    }));
    $this->bind(array('Anime.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("anime.count");
    }));

    $this->bind(array('Tag.afterCreate'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("tag.count");
    }));
    $this->bind(array('Tag.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("tag.count");
    }));

    $this->bind(array('AnimeList.afterUpdate', 'AnimeList.afterCreate', 'AnimeEntry.afterCreate'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("animelist.entries");
    }));
    $this->bind(array('AnimeEntry.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("animelist.entries");
    }));

    // user stats metrics.
    $this->bind(array('User.afterCreate', 'User.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->gauge("user.count", $parent->app->dbConn->queryCount("SELECT COUNT(*) FROM `users`"));
    }));
    $this->bind(array('User.logIn'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.login");
    }));
    $this->bind(array('User.logOut'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.logOut");
    }));
    $this->bind(array('User.requestFriend'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.friendrequests");
    }));
    $this->bind(array('User.confirmFriend'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.friendships");
    }));
    $this->bind(array('Comment.afterCreate', 'CommentEntry.afterCreate'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.comments");
    }));
    $this->bind(array('Comment.afterDelete', 'CommentEntry.afterDelete'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.comments");
    }));
    $this->bind(array('User.addAchievement'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.achievements");
      $parent->app->statsd->count("user.points.earned", $updateParams['points']);
    }));
    $this->bind(array('User.removeAchievement'), new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.achievements");
      $parent->app->statsd->count("user.points.earned", -1 * $updateParams['points']);
    }));

    // bind each achievement to its events.
    foreach ($this->achievements as $achievement) {
      foreach ($achievement->events() as $event) {
        $this->bind($event, $achievement);
      }
    }
  }

  public function checkCSRF() {
    // compare the POSTed CSRF token to this app's CSRF token.
    // if either is not set, returns False.
    if (!isset($this->csrfField) || !isset($this->csrfToken) || $this->csrfField === Null || $this->csrfToken === Null) {
      return False;
    }
    if (empty($_POST[$this->csrfField]) && isset($_REQUEST[$this->csrfField])) {
      $_POST[$this->csrfField] = $_REQUEST[$this->csrfField];
    }
    if (empty($_POST[$this->csrfField]) || $_POST[$this->csrfField] != $this->csrfToken) {
      return False;
    }
    return True;
  }
  // bind/unbind/fire event handlers for objects.
  // event names are of the form modelName.eventName
  // e.g. User.afterCreate
  public function bind($events, $observer) {
    // binds a function to an event or events.
    // can be either anonymous function or string name of class method.
    if (!method_exists($observer, 'update')) {
      if (Config::DEBUG_ON) {
        throw new AppException($this, sprintf('Invalid observer: %s.', print_r($observer, True)));
      } else {
        return False;
      }
    }
    if (!is_array($events)) {
      $events = array($events);
    }
    foreach ($events as $event) {
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
    http_response_code(intval($code));
    echo $this->view('header').$this->view(intval($code)).$this->view('footer');
    exit;
  }
  public function display_exception($e) {
    // formats a (subclassed) instance of AppException for display to the end user.
    echo $this->view('header').$e->display().$this->view('footer');
    exit;
  }
  public function check_partial_include($filename) {
    // displays the standard 404 page if the user is requesting a partial directly.
    if (str_replace("\\", "/", $filename) === $_SERVER['SCRIPT_FILENAME']) {
      $this->display_error(404);
    }
  }
  public function redirect($location, array $params=Null) {
    $paramString = "";

    // append any delayed messages onto the current status.
    if (count($this->delayedMessages) > 0) {
      if (isset($params['status'])) {
        array_unshift($this->delayedMessages, $params['status']);
      }
      $params['status'] = implode(" \n", $this->delayedMessages);
    }

    // determine which connector we need to append to the location url.
    if (strpos($location, "?") === False) {
      $connector = "?";
    } else {
      $connector = "&";
    }
    if ($params !== Null) {
      $paramString = $connector.http_build_query($params);
    }

    $redirect = "Location: ".$location.$paramString;
    header($redirect);
    exit;
  }
  public function jsRedirect($location, $redirect_array) {
    $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
    $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
    
    $redirect = Config::ROOT_URL."/".$location;
    if ($status != "") {
      if (strpos($location, "?") === FALSE) {
        $redirect .= "?status=".rawurlencode($status)."&class=".rawurlencode($class);
      } else {
        $redirect .= "&status=".rawurlencode($status)."&class=".rawurlencode($class);
      }
    }
    echo "window.location.replace(\"".$redirect."\");";
  }

  public function init() {
    // start of application logic.
    // loads dependencies, binds events, sets request variables, then attempts to render the current request.

    $this->startRender = microtime(true);
    set_error_handler('ErrorHandler', E_ALL & ~E_NOTICE);
    $this->_loadDependencies();
    $this->_bindEvents();

    $this->statsd->increment("hits");

    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
      $this->user = new User($this, intval($_SESSION['id']));
      // if user has not recently been active, update their last-active.
      if (!$this->user->isCurrentlyActive()) {
        $this->user->updateLastActive();
      }
    } else {
      $this->user = new User($this, 0);
    }

    // check to see if this request is being made via ajax.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && trim(strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])) == 'xmlhttprequest') {
      $this->ajax = True;
    }

    // protect against CSRF attacks.
    // only generate CSRF token if the user is logged in.
    if ($this->user->id !== 0) {
      $this->csrfToken = $this->_generateCSRFToken();
      // if request came in through AJAX, or there isn't a POST, don't run CSRF filter.
      if (!$this->ajax && !empty($_POST) && !$this->checkCSRF()) {
        $this->display_error(403);
      }
    }

    // set model, action, ID, status, class from request parameters.
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
      if (is_numeric($_REQUEST['id'])) {
        $this->id = intval($_REQUEST['id']);
      } else {
        $this->id = $_REQUEST['id'];
      }
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
    if (!isset($_REQUEST['format']) || $_REQUEST['format'] === "") {
      $this->format = 'html';
    } else {
      $this->format = escape_output($_REQUEST['format']);
    }

    if (isset($this->model) && $this->model !== "") {
      if (!class_exists($this->model)) {
        $this->redirect($this->user->url(), array("status" => "This thing doesn't exist!", "class" => "error"));
      }

      try {
        // kludge to allow model names in URLs.
        if (($this->model === "User" || $this->model === "Anime" || $this->model === "Tag") && $this->id !== "") {
            $this->target = new $this->model($this, Null, rawurldecode($this->id));
        } else {
          $this->target = new $this->model($this, intval($this->id));
        }
      } catch (DbException $e) {
        $this->statsd->increment("DbException");
        $this->display_error(404);
      }
      if ($this->target->id !== 0) {
        try {
          $foo = $this->target->getInfo();
        } catch (DbException $e) {
          $this->statsd->increment("DbException");
          $blankModel = new $this->model($this);
          $this->redirect($blankModel->url("index"), array("status" => "The ".strtolower($this->model)." you specified does not exist.", "class" => "error"));
        }
        if ($this->action === "new") {
          $this->action = "edit";
        }
      } elseif ($this->action === "edit") {
        $this->action = "new";
      }
      if (!$this->target->allow($this->user, $this->action)) {
        $targetClass = get_class($this->target);
        $error = new AppException($this, $this->user->username." attempted to ".$this->action." ".$targetClass::modelName()." ID#".$this->target->id);
        $this->logger->warning($error->__toString());
        $this->display_error(403);
      } else {
        try {
          $renderOutput = $this->target->render();
          echo $renderOutput;
          $this->statsd->timing("pageload", microtime(True) - $this->startRender);
          $this->statsd->memory('memory.peakusage');
          exit;
        } catch (AppException $e) {
          ob_end_clean();
          $this->logger->err($e->__toString());
          $this->display_exception($e);
        } catch (Exception $e) {
          ob_end_clean();
          $this->statsd->increment("Exception");
          $this->logger->err($e->__toString());
          $this->display_error(500);
        }
      }
    }
  }

  public function form(array $params=Null) {
    if (!isset($params['method'])) {
      $params['method'] = "post";
    }
    if (!isset($params['accept-charset'])) {
      $params['accept-charset'] = "UTF-8";
    }
    $formAttrs = [];
    foreach ($params as $key=>$value) {
      $formAttrs[] = escape_output($key)."='".escape_output($value)."'";
    }
    $formAttrs = implode(" ", $formAttrs);
    return "<form ".$formAttrs.">".$this->csrfInput()."\n";
  }
  public function input(array $params=Null) {
    if ($params == Null) {
      $params = [];
    }
    $inputAttrs = [];
    foreach ($params as $key=>$value) {
      $inputAttrs[] = escape_output($key)."='".escape_output($value)."'";
    }
    $inputAttrs = implode(" ", $inputAttrs);
    return "<input ".$inputAttrs." />\n";
  }
  public function csrfInput() {
    return $this->input(array(
      'type' => 'hidden',
      'name' => $this->csrfField,
      'value' => $this->csrfToken
    ));
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
    return $this->view('header', $appVars).$text.$this->view('footer', $appVars);
  }

  public function totalPoints() {
    // total number of points earnable by users.
    if ($this->totalPoints == Null) {
      $this->totalPoints = 0;
      foreach ($this->achievements as $achievement) {
        $this->totalPoints += $achievement->points;
      }
    }
    return $this->totalPoints;
  }
}

?>