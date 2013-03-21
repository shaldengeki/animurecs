<?php
require_once("global/includes.php");

if (!$app->user->loggedIn()) {
	$app->redirect('/index.php', array('status' => 'You must be logged in to view recommendations.', 'redirect_to' => '/discover/'));
}

$blankAnime = new Anime($app, 0);

$pageHTML = <<<EOD
<div class='tabbable tabs-left'>
  <ul class='nav nav-tabs'>
    <li class='active ajaxTab' data-url="{$app->user->url('recommendations')}"><a href='#yourRecs' data-toggle='tab'>Your Recs</a></li>
    <li class='ajaxTab' data-url="{$app->user->url('friendRecs')}"><a href='#friendRecs' data-toggle='tab'>Friends</a></li>
    <li><a href='{$blankAnime->url('index')}'>Browse</a></li>
  </ul>
  <div class='tab-content'>
    <div class='tab-pane active' id='yourRecs'>
      {$app->user->view('recommendations')}
    </div>
    <div class='tab-pane' id='friendRecs'>
      Loading...
    </div>
  </div>
</div>
EOD;
$app->render($pageHTML, array('subtitle' => 'Your Recs'));
?>