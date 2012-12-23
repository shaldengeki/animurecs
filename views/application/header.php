<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <meta http-equiv='content-type' content='text/html; charset=utf-8' />
    <title><?php echo (isset($params['title']) ? escape_output($params['title']) : "Animurecs").($subtitle != '' ? ' - '.escape_output($subtitle) : ''); ?></title>
    <link rel='shortcut icon' href='/favicon.ico' />

    <link rel='stylesheet' href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' type='text/css' />
    <link rel='stylesheet' href='<?php echo Config::ROOT_URL; ?>/css/jquery.dataTables.css' type='text/css' />
    <link rel='stylesheet' href='<?php echo Config::ROOT_URL; ?>/css/token-input.css' type='text/css' />
    <link rel='stylesheet' href='<?php echo Config::ROOT_URL; ?>/css/animurecs.css' type='text/css' />

    <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js'></script>
    <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js'></script>

    <!--<script type='text/javascript' src='<?php echo Config::ROOT_URL; ?>/jquery-ui-timepicker-addon.js'></script>-->
    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/jquery.dropdownPlain.js'></script>
    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/jquery.dataTables.min.js'></script>
    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/jquery.tokeninput.js'></script>
    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/jquery.json-2.3.min.js'></script>

    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript' src='<?php echo Config::ROOT_URL; ?>/js/d3.v2.min.js'></script>
    <script type='text/javascript' src='<?php echo Config::ROOT_URL; ?>/js/d3-helpers.js'></script>

    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/bootstrap.min.js'></script>
    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/bootstrap-dropdown.js'></script>

    <script type='text/javascript' language='javascript' src='<?php echo Config::ROOT_URL; ?>/js/animurecs.js'></script>
  </head>
  <body>
    <div class='navbar navbar-inverse navbar-fixed-top'>
      <div class='navbar-inner'>
        <div class='container-fluid'>
          <a href='/' class='brand'>Animurecs</a>
          <ul class='nav'>
<?php
  if ($this->user->loggedIn()) {
?>            <li class='divider-vertical'></li>
            <li><a href='/feed.php'><i class='icon-th-list icon-white'></i> Feed</a></li>
            <li class='divider-vertical'></li>
            <li><?php echo $this->user->link("show", "<i class='icon-home icon-white'></i> You", True); ?></li>
            <li class='divider-vertical'></li>
            <li><a href='/users/'><i class='icon-globe icon-white'></i> Connect</a></li>
            <li class='divider-vertical'></li>
            <li><a href='/discover.php'><i class='icon-star icon-white'></i> Discover</a></li>
            <li class='divider-vertical'></li>
<?php
  }
?>
          </ul>
          <ul class='nav pull-right'>
<?php
  if ($this->user->loggedIn()) {
?>
            <li id='navbar-alerts'>
<?php
    if ($this->user->friendRequests) {
?>
              <span class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='#'><span class='badge badge-info'><?php echo count($this->user->friendRequests); ?></span></a>
                <ul class='dropdown-menu'>
                  <?php echo $this->user->friendRequestsList(); ?>
                </ul>
              </span>
<?php
    }
?>
            </li>
            <li id='navbar-user' class='dropdown'>
              <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='icon-user icon-white'></i><?php echo escape_output($this->user->username); ?><b class='caret'></b></a>
              <ul class='dropdown-menu'>
                <li><?php echo $this->user->link("show", "Profile"); ?></li>
                <li><?php echo $this->user->link("edit", "Settings"); ?></li>
<?php
    if ($this->user->isAdmin() && !isset($this->user->switchedUser)) {
?>
                <li><?php echo $this->user->link("switch_user", "Switch User"); ?></li>
<?php
    }
    if (isset($this->user->switchedUser) && is_numeric($this->user->switchedUser)) {
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
                  <?php echo $this->user->view('login'); ?>
                </li>
              </ul>
<?php
  }
?>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class='container-fluid'>
<?php
  if ($this->status != '') {
?>
      <div class='alert alert-<?php echo isset($this->class) ? escape_output($this->class) : ""; ?>'>
    <button class='close' data-dismiss='alert' href='#'>×</button>
  <?php echo escape_output($this->status); ?></div>
<?php
  }
?>