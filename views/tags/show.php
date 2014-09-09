<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['object'] = isset($params['object']) ? $params['object'] : Null;
  $params['group'] = isset($params['group']) ? $params['group'] : [];
  $params['predictions'] = isset($params['predictions']) ? $params['predictions'] : [];
  $params['perPage'] = isset($params['perPage']) ? $params['perPage'] : 25;

  $pages = ceil(count($params['group'])/$params['perPage']);
?>
<h1><?php echo $this->link('show', ($this->type->id != 1 ? $this->type->name.":" : "").$this->name).($this->allow($this->app->user, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>
<?php echo $this->description ? "<p class='lead'>".escape_output($this->description)."</p>" : "" ?>
<div class='row'>
  <div class='col-md-2'>
    <h2>Tags:</h2>
    <?php echo $params['group']->view('tagList', $params); ?>
  </div>
  <div class='col-md-10'>
    <?php echo paginate($this->url("show", Null, ["page" => ""]), intval($this->app->page), $pages); ?>
    <?php echo $params['object']->view('grid', $params); ?>
    <?php echo paginate($this->url("show", Null, ["page" => ""]), intval($this->app->page), $pages); ?>
  </div>
</div>