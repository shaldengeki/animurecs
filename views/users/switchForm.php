<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(['action' => $this->url("switch_user"), 'class' => 'form-horizontal']); ?>
      <fieldset>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='switch_username'>Username</label>
          <div class='col-sm-10'>
            <input name='switch_username' type='text' class='input-xlarge' id='switch_username' />
          </div>
        </div>
        <div class='form-group'>
          <div class='col-sm-offset-2 col-sm-10'>
            <button type='submit' class='btn btn-primary'>Switch</button>
            <a href='#' onClick='window.location.replace(document.referrer);' class='btn btn-default'>Back</a>
          </div>
        </div>
      </fieldset>
    </form>