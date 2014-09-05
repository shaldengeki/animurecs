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
      <fieldset>
        <div class='form-group'>
          <label class='control-label' for='alias[name]'>Name</label>
          <div class='controls'>
            <?php echo $alias->input('name', ['type' => 'text']); ?>
          </div>
        </div>

        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add Alias" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
<?php
  if ($alias->id !== 0) {
?>
          <a class='btn btn-danger' href='<?php echo $alias->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
        </div>
      </fieldset>
    </form>