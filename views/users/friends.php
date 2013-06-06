<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
/*
  $friendCompats = $this->app->recsEngine->compatibilities($this, array_map(function($u) {
    return $u['user'];
  }, $this->friends()));
*/
  $friendCompats = array_map(function($u) {
    return $this->animeList()->compatibility($u['user']->animeList());
  }, $this->friends());
  $friendCompats = $friendCompats ? $friendCompats : [];
  arsort($friendCompats);
?>
<ul class='item-grid friends'>
<?php
  if (!$friendCompats) {
?>
  <blockquote>You don't have any friends with whom you share a compatibility yet! Try adding more friends, or entries to your anime list!</blockquote>
<?php    
  } else {
    foreach ($friendCompats as $friendID=>$compat) {
      $friend = $this->friends()[intval($friendID)]['user'];
?>
  <li class='friendGridEntry'><?php echo $friend->link("show", $friend->avatarImage(['class' => 'friendGridImage'])."<div class='friendGridUsername'>".escape_output($friend->username)."</div>", Null, True); ?><p><em><?php echo floatval($compat) <= 0 ? "No compat" : round($compat)."% compatible"; ?></em></p></li>
<?php
    }
  }
?>
</ul>