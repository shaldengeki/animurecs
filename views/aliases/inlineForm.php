<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstAlias = Alias::Get($this->app);
  $params['alias'] = isset($params['alias']) ? $params['alias'] : $this;
  $params['currentObject'] = isset($params['currentObject']) ? $params['currentObject'] : Null;

  echo $params['alias']->app->form(['action' => ($params['alias']->id === 0) ? $firstAlias->url("new") : $params['alias']->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']);
?>
      <?php echo $params['alias']->id !== 0 ? $params['alias']->input('id', ['type' => 'hidden']) : ""; ?>
      <?php echo $params['alias']->input('type', ['type' => 'hidden', 'value' => escape_output(($params['alias']->id === 0) ? get_class($params['currentObject']) : $params['alias']->type)]); ?>
      <?php echo $params['alias']->input('parent_id', ['type' => 'hidden', 'value' => intval(($params['alias']->id === 0) ? $params['currentObject']->id : $params['alias']->parent()->id)]); ?>
      <?php echo $params['alias']->input('name', ['type' => 'text', 'placeholder' => 'Add an alias']); ?>
      <button type='submit' class='btn btn-primary'>".(($params['alias']->id === 0) ? "Add" : "Update")."</button>
    </form>