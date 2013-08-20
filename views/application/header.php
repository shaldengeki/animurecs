<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
  $assetsVersion = 0.77;
  $firstAnime = class_exists("Anime") ? Anime::first($this) : Null;
  $params['container'] = isset($params['container']) ? $params['container'] : True;
  $params['title'] = isset($params['title']) ? $params['title'] : "Animurecs";
  $params['subtitle'] = isset($params['subtitle']) && $params['subtitle'] ? $params['subtitle'] : "Social Anime Recommendations";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name='description' content="Animurecs is an anime-centric social network that gives you personalized recommendations by learning your tastes. Discover new anime you'll love today!" />
    <meta name='keywords' content='anime, recommendations, anime list, recommend, top anime' />

    <title><?php echo escape_output($params['title'])." - ".escape_output($params['subtitle']); ?></title>
    <link href='/favicon.ico' rel='shortcut icon' />

    <link href='//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' rel='stylesheet' />
    <link href='<?php echo Config::ROOT_URL; ?>/css/jquery.dataTables.css' rel='stylesheet' />
    <link href='<?php echo Config::ROOT_URL; ?>/css/token-input.css' rel='stylesheet' />
    <link href='<?php echo Config::ROOT_URL; ?>/css/animurecs.css?v=<?php echo $assetsVersion; ?>' rel='stylesheet' />
    <script src='//cdnjs.cloudflare.com/ajax/libs/modernizr/2.6.2/modernizr.min.js' type='text/javascript'></script>
  </head>
  <body>
    <div class='navbar navbar-inverse navbar-fixed-top' role='navigation'>
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a href='/' class="navbar-brand" href="#">Animurecs</a>
        </div>
        <div class="collapse navbar-collapse">
<?php
  if ($this->user && $this->user->loggedIn()) {
?>
            <ul class="nav navbar-nav">
              <li class='divider-vertical'></li>
              <li><?php echo $this->user->link("globalFeed", "<i class='glyphicon glyphicon-th-list glyphicon-white'></i> Feed", Null, True); ?></li>
              <li class='divider-vertical'></li>
              <li><?php echo $this->user->link("show", "<i class='glyphicon glyphicon-home glyphicon-white'></i> You", Null, True); ?></li>
              <li class='divider-vertical'></li>
              <li><a href='/users/'><i class='glyphicon glyphicon-globe glyphicon-white'></i> Connect</a></li>
              <li class='divider-vertical'></li>
              <li><?php echo $this->user->link("discover", "<i class='glyphicon glyphicon-star glyphicon-white'></i> Discover", Null, True); ?></li>
              <li class='divider-vertical'></li>
            </ul>
<?php
  }
?>
            <ul class='nav navbar-nav navbar-right'>
<?php
  if ($this->user && $this->user->loggedIn()) {
?>
              <li>
                <?php echo $firstAnime->view('searchForm', [
                'form' => [
                    'class' => 'navbar-search'
                  ],
                'searchInput' => [
                    'id' => 'navbar-anime-search',
                    'class' => 'autocomplete search-query'
                  ],
                'submitButton' => False
                ]); ?>
              </li>
              <li id='navbar-alerts'>
<?php
    if ($this->user && $this->user->outstandingFriendRequests) {
?>
                <span class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='#'><i class='glyphicon glyphicon-envelope glyphicon-white'></i> <span class='msg-count'><span class='msg-count-inner'><?php echo count($this->user->outstandingFriendRequests); ?></span></span></a>
<?php
    } else {
?>
                <span class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='#'><i class='glyphicon glyphicon-envelope glyphicon-inactive'></i></a>
<?php
    }
?>
                  <?php echo $this->user->view('friendRequestList', $params); ?>
                </span>
              </li>
              <li id='navbar-user' class='dropdown'>
                <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='glyphicon glyphicon-user glyphicon-white'></i><?php echo escape_output($this->user->username); ?><b class='caret'></b></a>
                <ul class='dropdown-menu'>
                  <li><?php echo $this->user->link("show", "Profile"); ?></li>
                  <li><?php echo $this->user->link("edit", "Settings"); ?></li>
<?php
    if ($this->user && $this->user->isAdmin() && !isset($this->user->switchedUser)) {
?>
                  <li><?php echo $this->user->link("switch_user", "Switch User"); ?></li>
<?php
    }
    if ($this->user && isset($this->user->switchedUser) && is_numeric($this->user->switchedUser)) {
?>
                  <li><?php echo $this->user->link("switch_back", "Switch Back"); ?></li>
<?php
    }
?>
                  <li><a href='/logout.php'>Sign out</a></li>
                </ul>
<?php
  } else {
?>
                  <li>
                    <?php echo $this->user ? $this->user->view('loginInline', $params) : ""; ?>
                  </li>
<?php
  }
?>
            </ul>
          </div>
        </div>
      </div>
    </div>
<?php
  if ($params['container']) {
?>
    <div class='container'>
<?php
  }
  foreach ($this->allMessages() as $message) {
?>
      <div class='alert<?php echo isset($message['class']) ? " alert-".escape_output($message['class']) : ""; ?>'>
    <button class='close' data-dismiss='alert' href='#'>×</button>
  <?php echo $message['text']; ?></div>
<?php
  }
  $this->clearAllMessages();
?>