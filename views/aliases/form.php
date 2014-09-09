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
      <fieldset>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='alias[name]'>Name</label>
          <div class='col-sm-10'>
            <?php echo $params['alias']->input('name', ['type' => 'text']); ?>
          </div>
        </div>

        <div class='form-group'>
          <div class='col-sm-offset-2 col-sm-10'>
            <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add Alias" : "Save changes")."</button>
            <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
<?php
  if ($params['alias']->id !== 0) {
?>
            <a class='btn btn-danger' href='<?php echo $params['alias']->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
          </div>
        </div>
      </fieldset>
    </form>