<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstAlias = Alias::Get($this->app);
  $alias = isset($params['alias']) ? $params['alias'] : $this;
  $params['currentObject'] = isset($params['currentObject']) ? $params['currentObject'] : Null;

  echo $alias->app->form(['action' => ($alias->id === 0) ? $firstAlias->url("new") : $alias->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']);
?>
      <?php echo $alias->id !== 0 ? $alias->input('id', ['type' => 'hidden']) : ""; ?>
      <?php echo $alias->input('type', ['type' => 'hidden', 'value' => escape_output(($alias->id === 0) ? get_class($params['currentObject']) : $alias->type)]); ?>
      <?php echo $alias->input('parent_id', ['type' => 'hidden', 'value' => intval(($alias->id === 0) ? $params['currentObject']->id : $alias->parent()->id)]); ?>
      <?php echo $alias->input('name', ['type' => 'text', 'placeholder' => 'Add an alias']); ?>
      <button type='submit' class='btn btn-primary'>".(($alias->id === 0) ? "Add" : "Update")."</button>
    </form>