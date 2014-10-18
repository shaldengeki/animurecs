<?php

class EmptyDependency {
  /*
    Empty dependency class that returns False on any accessor.
  */
  public function __isset($name) {
    return False;
  }
  public function __set($name, $value) {
    return;
  }
  public function __unset($name) {
    return False;
  }
  public function __get($name) {
    return False;
  }
  public function __call($name, $args) {
    return False;
  }
  public static function __callStatic($name, $args) {
    return False;
  }
}

class AppException extends Exception {
  private $app, $messages;
  public function __construct($app, $messages=Null, $code=0, Exception $previous=Null) {
    if (is_array($messages)) {
      $this->messages = $messages;
    } else {
      $this->messages = [$messages];
    }
    parent::__construct($this->formatMessages(), $code, $previous);
    $this->app = $app;
    // $this->app->statsd->increment('AppException');
  }
  public function messages() {
    return $this->messages;
  }
  public function formatMessages($separator=" ") {
    // displays a list of this exception's messages.
    if (count($this->messages) > 0) {
      return implode($separator, $this->messages);
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
    return implode("\n", [
      get_class($this).":",
      $this->getFile().":".$this->getLine(),
      "Messages: ".$this->formatMessages(),
      "Stack trace:\n".$this->getTraceAsString()
    ]);
  }
  public function display() {
    // displays end user-friendly output explaining the exception that occurred.
    echo "A server error occurred, and I wasn't able to complete your request. I've let the staff know something's wrong - apologies for the problems!";
  }
}

class InvalidModelException extends AppException {
  private $model;
  public function __construct($app, $model, $messages=Null, $code=0, Exception $previous=Null) {
    parent::__construct($app, $messages, $code, $previous);
    $this->model = $model;
  }
  public function __toString() {
    return implode("\n", [
      "Model: ".$this->model,
      parent::__toString()
    ]);
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
  private $_controllers=[],$_modelControllers = [],$_observers=[],$messages=[],$timings=[];
  private $_statsdConn=Null;

  protected $totalPoints=Null;

  public $achievements=[],$debugOutput=[];
  public $statsd, $logger, $cache, $dbConn, $recsEngine, $mailer, $serverTimeZone, $outputTimeZone, $user, $target, $startRender, $csrfToken=Null;

  public $controller,$action,$status,$class="";
  public $id=0;
  public $ajax=False;
  public $page=1;
  public $csrfField="csrf_token";

  // increment this when CSS/JS changes.
  public $assetsVersion = 0.79;

  private function _generateCSRFToken() {
    // generates a CSRF token, if one has not already been created for this Application object.
    if ($this->csrfToken === Null) {
      $this->csrfToken = hash('sha256', 'csrf-token:'.session_id());
    }
    return $this->csrfToken;
  }
  private function _setCSRFToken() {
    // sets a CSRF token in the session object, and in the cookie.
    $_SESSION['csrfToken'] = $this->csrfToken;
    setcookie('XSRF-TOKEN', $this->csrfToken, 0, '/', Config::COOKIE_DOMAIN, True, False);
  }
  private function _loadDependency($absPath) {
    // requires an application dependency from its absolute path
    // e.g. /ABSOLUTE_PATH/config.php
    if (file_exists($absPath)) {
      include_once($absPath);
    } else {
      throw new AppException($this, 'required library not found at '.$absPath);
    }
  }
  private function _loadDependencyDirectory($absPath) {
    // requires all php files within an absolute path
    // leave off the trailing slash!
    // e.g. /ABSOLUTE_PATH
    foreach (glob($absPath."/*.php") as $filename) {
      $this->_loadDependency($filename);
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
      return log::factory('file', Config::LOG_FILE, 'Animurecs', [], PEAR_LOG_DEBUG);
    } else {
      return log::factory('file', Config::LOG_FILE, 'Animurecs', [], PEAR_LOG_WARNING);
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
      $this->dbConn = new DatabaseConnection();
    }
    return $this->dbConn;
  }
  private function _connectRecsEngine() {
    if ($this->recsEngine === Null) {
      $this->recsEngine = new RecsEngine(Config::RECS_ENGINE_HOST, Config::RECS_ENGINE_PORT);
    }
    return $this->recsEngine;
  }
  private function _connectMailer() {
    if ($this->mailer === Null) {
      // Create the Transport
      $transporter = Swift_SmtpTransport::newInstance(Config::SMTP_HOST, Config::SMTP_PORT, 'ssl')
        ->setUsername(Config::SMTP_USERNAME)
        ->setPassword(Config::SMTP_PASSWORD);
      $this->mailer = Swift_Mailer::newInstance($transporter);
    }
    return $this->mailer;
  }
  private function _connectServices() {
    // Connects to external services.
    try {
      $this->_connectDB();
    } catch (DatabaseNotAvailableException $e) {
      $this->statsd->increment("DatabaseException");
      $this->logger->alert($e->__toString());
      $this->display_exception($e);
    }
    try {
      $this->mailer = $this->_connectMailer();
    } catch (AppException $e) {
      $this->statsd->increment("MailerException");
      $this->logger->alert($e->__toString());
      $this->display_exception($e);
    }
    try {
      $this->cache = $this->_connectCache();
    } catch (CacheException $e) {
      // we don't ~technically~ need memcached to run the site. Log an exception.
      $this->cache = new EmptyDependency();
      $this->statsd->increment("CacheException");
      $this->logger->alert($e->__toString());
    }
    try {
      $this->recsEngine = $this->_connectRecsEngine();
    } catch (AppException $e) {
      // we don't ~technically~ need the recommendations engine to run the site. Log an exception.
      $this->recsEngine = new EmptyDependency();
      $this->statsd->increment("RecsException");
      $this->logger->alert($e->__toString());
    }
  }
  private function _loadDependencies() {
    // Loads configuration and all application objects from library files.
    // Connects database, logger, recommendation engine.
    // Creates a list of objects that can be accessed via URL.

    require_once("config.php");
    require_once(Config::APP_ROOT.'/vendor/autoload.php');

    try {
      $this->statsd = $this->_connectStatsD();
    } catch (Exception $e) {
      // we don't ~technically~ need statsd to run the site. Log an exception.
      $this->statsd = new EmptyDependency();
      $this->logger->alert($e->__toString());
    }

    require_once('Log.php');
    $this->logger = $this->_connectLogger();

    try {
      // include all traits before models that depend on them.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/traits");

      // core models, including base_object.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/core");

      // generic base models that extend base_object.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/base");

      // group models.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/groups");

      // external services.
      $this->_connectServices();

      // models.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/models");

      $nonLinkedClasses = count(get_declared_classes());

      // controllers.
      $this->_loadDependencyDirectory(Config::APP_ROOT."/controllers");
    } catch (AppException $e) {
      $this->logger->alert($e);
      $this->display_error(500, "Could not load dependencies properly! Oh dear.");
    }

    // forbid cookies to be sent over plain HTTP, unless we're on development.
    $cookieSecureFlag = Config::ENVIRONMENT === "development" ? False : True;

    session_set_cookie_params(0, '/', Config::COOKIE_DOMAIN, $cookieSecureFlag, True);
    session_start();

    date_default_timezone_set(Config::SERVER_TIMEZONE);
    $this->serverTimeZone = new DateTimeZone(Config::SERVER_TIMEZONE);
    $this->outputTimeZone = new DateTimeZone(Config::OUTPUT_TIMEZONE);

    // _controllers is a modelUrl:controller mapping for controllers that are bound to a URL.
    // e.g. "tag_types" => TagTypesController()
    foreach (array_slice(get_declared_classes(), $nonLinkedClasses) as $controllerName) {
      $controller = new $controllerName($this);
      if (!isset($this->_controllers[$controllerName::$URL_BASE])) {
        $this->_controllers[$controllerName::$URL_BASE] = $controller;
      }
      if (!isset($this->_modelControllers[$controllerName::$MODEL])) {
        $this->_modelControllers[$controllerName::$MODEL] = [$controller];
      } else {
        $this->_modelControllers[$controllerName::$MODEL][] = $controller;
      }
    }

    // include all achievements.
    // achievements is an ID:object mapping of achievements.
    $nonAchievementClasses = count(get_declared_classes());
    try {
      $this->_loadDependencyDirectory(Config::APP_ROOT."/achievements");
    } catch (AppException $e) {
      $this->logger->alert($e);
      $this->display_error(500, "Could not load achievements properly! Oh dear.");
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
    $this->bind(['Model.afterUpdate', 'Model.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete($parent->cacheKey());
    }));
    $this->bind(['Anime.afterUpdate', 'Anime.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete($parent->cacheKey(['similar']));
    }));
    $this->bind(['Anime.tag', 'Anime.untag', 'Tag.tag', 'Tag.untag'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete(Anime::GenerateCacheKeyFromID(intval($updateParams['anime_id']), ['tagIDs']));
      $parent->app->cache->delete(Tag::GenerateCacheKeyFromID(intval($updateParams['tag_id']), ['animeIDs']));
    }));
    $this->bind(['AnimeEntry.afterUpdate', 'AnimeEntry.afterCreate', 'AnimeEntry.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->cache->delete(AnimeEntry::GenerateCacheKeyFromID(intval($updateParams['id'])));
    }));

    // statsd metrics.
    $this->bind(['Anime.afterCreate'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("anime.count");
    }));
    $this->bind(['Anime.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("anime.count");
    }));
    $this->bind(['Tag.afterCreate'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("tag.count");
    }));
    $this->bind(['Tag.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("tag.count");
    }));

    $this->bind(['AnimeList.afterUpdate', 'AnimeList.afterCreate', 'AnimeEntry.afterCreate'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("animeList.entries");
    }));
    $this->bind(['AnimeEntry.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("animeList.entries");
    }));

    // user stats metrics.
    $this->bind(['User.afterCreate', 'User.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->gauge("user.count", User::Count($parent->app));
    }));
    $this->bind(['User.logIn'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.sessions.started");
    }));
    $this->bind(['User.logOut'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.sessions.ended");
    }));
    $this->bind(['User.requestFriend'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.friendships.requests");
    }));
    $this->bind(['User.confirmFriend'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.friendships.confirmed");
      $parent->app->statsd->decrement("user.friendships.requests");
    }));
    $this->bind(['Comment.afterCreate'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.comments.posted");
    }));
    $this->bind(['Comment.afterDelete'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.comments.posted");
    }));
    $this->bind(['User.addAchievement'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->increment("user.achievements.earned");
      $parent->app->statsd->count("user.points.earned", $updateParams['points']);
    }));
    $this->bind(['User.removeAchievement'], new Observer(function($event, $parent, $updateParams) {
      $parent->app->statsd->decrement("user.achievements.earned");
      $parent->app->statsd->count("user.points.earned", -1 * $updateParams['points']);
    }));

    // bind each achievement to its events.
    foreach ($this->achievements as $achievement) {
      foreach ($achievement->events as $event) {
        $this->bind($event, $achievement);
      }
    }
  }

  public function modelControllers($model) {
    if (!isset($this->_modelControllers[$model])) {
      throw new InvalidModelException($this, $model);
    }
    return $this->_modelControllers[$model];
  }

  public function checkCSRF() {
    // compare the POSTed CSRF token to this app's CSRF token.
    // if either is not set, returns False.
    if (!isset($this->csrfField) || !isset($this->csrfToken) || $this->csrfField === Null || $this->csrfToken === Null) {
      return False;
    }
    if (empty($_POST[$this->csrfField])) {
      $httpHeaders = getallheaders();
      if (isset($_REQUEST[$this->csrfField])) {
        $_POST[$this->csrfField] = $_REQUEST[$this->csrfField];
      } elseif (isset($httpHeaders['X-XSRF-TOKEN'])) {
        $_POST[$this->csrfField] = $httpHeaders['X-XSRF-TOKEN'];
      }
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
      $events = [$events];
    }
    foreach ($events as $event) {
      if (!isset($this->_observers[$event])) {
        $this->_observers[$event] = [$observer];
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
    return [$event, count($this->_observers[$event])-1];
  }
  public function unbind($observer) {
    // callback is array of form [event_name, position]
    // alternatively, also accepts a string for event name to clear all observers.
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

  public function clearOutput() {
    // clears all content in the output buffer(s).
    while (ob_get_level()) {
      ob_end_clean();
    }
    ob_start();
  }
  public function endOutput() {
    // performs tasks after output.
    ob_end_flush();
    $this->statsd->timing("pageload", microtime(True) - $this->startRender);
    $this->statsd->memory('memory.peakusage');
    $this->setPreviousUrl();
    exit;
  }

  public function display_response($code, $response) {
    $this->clearOutput();
    http_response_code(intval($code));
    echo json_encode($response);
    $this->endOutput();
  }
  public function display_success($code, $message) {
    // alias for display_response.
    $this->display_response($code, ['code' => $code, 'message' => $message]);
  }
  public function display_error($code, $message) {
    // alias for display_response.
    $this->display_response($code, ['code' => $code, 'message' => $message]);
  }
  public function display_exception($e) {
    // formats a (potentially subclassed) instance of AppException for display to the end user.
    $this->display_response($e->code, ['code' => $e->code, 'message' => $e->formatMessages()]);
  }
  public function log_exception($e) {
    // log an exception.
    $this->logger->err($e->__toString());
    $this->logger->err("Requested URL: ".$this->currentUrl());
  }
  public function check_partial_include($filename) {
    // displays the standard 404 page if the user is requesting a partial directly.
    if (str_replace("\\", "/", $filename) === $_SERVER['SCRIPT_FILENAME']) {
      $this->display_error(404, "A required partial could not be found.");
    }
  }

  public function delayedMessage($message, $class=Null) {
    // appends message to delayed message queue.
    if (!isset($_SESSION['delayedMessages'])) {
      $_SESSION['delayedMessages'] = [];
    }
    if (!is_array($message)) {
      $message = ['text' => $message];
    }
    if ($class) {
      switch ($class) {
        case 'error':
          $class = 'danger';
          break;
        case 'success':
        case 'info':
        case 'warning':
        default:
          break;
      }
      $message['class'] = $class;
    }
    $_SESSION['delayedMessages'][] = $message;
  }
  public function delayedMessages() {
    // returns delayed message queue.
    if (!isset($_SESSION['delayedMessages'])) {
      $_SESSION['delayedMessages'] = [];
    }
    return $_SESSION['delayedMessages'];    
  }
  public function clearDelayedMessages() {
    // empties delayed message queue.
    $_SESSION['delayedMessages'] = [];
    return True;
  }
  public function message($message) {
    // appends message to message queue.
    if (!is_array($message)) {
      $message = ['text' => $message];
    }
    if ($class) {
      $message['class'] = $class;
    }
    $this->messages[] = $message;
  }
  public function messages() {
    // returns message queue.
    return $this->messages;
  }
  public function clearMessages() {
    // clears message queue.
    $this->messages = [];
    return True;
  }
  public function allMessages() {
    // returns delayed and immediate message queues.
    return array_merge($this->delayedMessages(), $this->messages());
  }
  public function clearAllMessages() {
    // clears delayed and immediate message queues.
    return $this->clearMessages() && $this->clearDelayedMessages();
  }

  public function currentUrl() {
    return $_SERVER['REQUEST_URI'];
  }
  public function previousUrl() {
    if (!isset($_SESSION['prev_url'])) {
      $_SESSION['prev_url'] = '/';
    }
    return $_SESSION['prev_url'];
  }
  public function setPreviousUrl($url=Null) {
    $_SESSION['prev_url'] = $url === Null ? $this->currentUrl() : $url;
  }

  public function redirect($location=Null) {
    if ($location === Null) {
      $location = $this->previousUrl();
    }
    header("Location: ".$location);
    $this->endOutput();
  }
  public function jsRedirect($location) {
    echo "window.location.replace(\"".Config::ROOT_URL."/".$location."\");";
  }

  public function init() {
    // start of application logic.
    // loads dependencies, binds events, sets request variables, then attempts to render the current request.

    $this->startRender = microtime(true);
    ob_start();
    set_error_handler('ErrorHandler', E_ALL & ~E_NOTICE);
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_regex_encoding('UTF-8');
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
      $this->_setCSRFToken();
      // if there isn't a POST, don't run CSRF filter.
      if (!empty($_POST) && !$this->checkCSRF()) {
        $this->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
      }
    }

    // set controller, action, ID, status, class from request parameters.
    if (isset($_REQUEST['status'])) {
      $this->status = $_REQUEST['status'];
    }
    if (isset($_REQUEST['class'])) {
      $this->class = $_REQUEST['class'];
    }
    if (isset($_REQUEST['controller']) && isset($this->_controllers[$_REQUEST['controller']])) {
      $this->controller = $this->_controllers[$_REQUEST['controller']];
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
      // page must be at least 1.
      $this->page = max(1, intval($_REQUEST['page']));
    }
    if (!isset($_REQUEST['format']) || $_REQUEST['format'] === "") {
      $this->format = 'html';
    } else {
      $this->format = escape_output($_REQUEST['format']);
    }

    if (isset($this->controller) && $this->controller !== "") {
      try {
        // render page.
        header('X-Frame-Options: SAMEORIGIN');
        header('Access-Control-Allow-Origin: '.Config::ROOT_URL);
        ob_clean();
        $this->controller->_beforeAction();
        $this->controller->_performAction($this->action);
        echo ob_get_clean();
        $this->endOutput();
      } catch (UndefinedActionException $e) {
        $this->statsd->increment(get_class($e));
        $this->log_exception($e);
        $this->display_error(404, "There is no endpoint on this resource at: ".$e->action);
      } catch (UnauthorizedException $e) {
        // display error page if user is not allowed to perform this action.
        $targetClass = get_class($e->controller->_target);
        $error = new AppException($this, $this->user->username." attempted to ".$this->action." ".$targetClass::MODEL_NAME()." ID#".$this->target->id);
        $this->logger->warning($error->__toString());
        $this->display_error(403, "You're not allowed to perform this action. Try logging in?");
      } catch (AppException $e) {
        $this->statsd->increment(get_class($e));
        $this->log_exception($e);
        $this->clearOutput();
        $this->display_exception($e);
      } catch (NoDatabaseRowsRetrievedException $e) {
        $this->dbConn->reset();
        $this->statsd->increment(get_class($e));
        $this->log_exception($e);
        $this->display_error(404, "Database error: Requested object ID not found.");
      } catch (DatabaseException $e) {
        $this->dbConn->reset();
        $this->statsd->increment(get_class($e));
        $this->log_exception($e);
        $this->display_error(404, "Database error. Oh dear.");
      } catch (Exception $e) {
        $this->statsd->increment(get_class($e));
        $this->log_exception($e);
        $this->display_error(500, "Unspecified server error. Oh dear.");
      }
    }
  }

  public function form(array $params=Null) {
    $params['role'] = isset($params['role']) ? $params['role'] : "form";
    $params['method'] = isset($params['method']) ? $params['method'] : "post";
    $params['accept-charset'] = isset($params['accept-charset']) ? $params['accept-charset'] : "UTF-8";
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
    $params['class'] = isset($params['class']) ? "form-control ".$params['class'] : "form-control";
    $inputAttrs = [];
    foreach ($params as $key=>$value) {
      $inputAttrs[] = escape_output($key)."='".escape_output($value)."'";
    }
    $inputAttrs = implode(" ", $inputAttrs);
    return "<input ".$inputAttrs." />";
  }
  public function textarea(array $params=Null, $textValue=Null) {
    if ($params == Null) {
      $params = [];
    }
    $params['class'] = isset($params['class']) ? "form-control ".$params['class'] : "form-control";
    if ($textValue == Null) {
      $textValue = "";
    }
    $inputAttrs = [];
    foreach ($params as $key=>$value) {
      $inputAttrs[] = escape_output($key)."='".escape_output($value)."'";
    }
    $inputAttrs = implode(" ", $inputAttrs);
    return "<textarea ".$inputAttrs." >".escape_output($textValue)."</textarea>";
  }
  public function csrfInput() {
    return $this->input([
      'type' => 'hidden',
      'name' => $this->csrfField,
      'value' => $this->csrfToken
    ]);
  }

  public function view($view="index", $params=Null) {
    // includes a provided application-level view.
    $file = joinPaths(Config::APP_ROOT, 'views', 'application', "$view.php");
    if (file_exists($file)) {
      ob_start();
      include($file);
      return ob_get_clean();
    }
    throw new AppException($this, "Could not find application view: ".$file);
  }
  public function render($text, $params=Null) {
    // renders the given text surrounded by the standard application header and footer.
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
      foreach ($this->achievements as $achievement) {
        $this->totalPoints += $achievement->points;
      }
    }
    return $this->totalPoints;
  }
  public function addTiming($name) {
    $this->timings[] = ['name' => $name, 'time' => microtime(true)];
  }
  public function timingInfo($separator='\\n') {
    $outputLines = [];
    if (!$this->timings) {
      return "";
    }
    $startTime = $prevTime = $this->timings[0]['time'];
    foreach ($this->timings as $timing) {
      $outputLines[] = ($timing['time'] - $prevTime)." | ".$timing['name'];
      $prevTime = $timing['time'];
    }
    $outputLines[] = "Total time: ".($prevTime - $startTime);
    return $outputLines;
  }
}

?>