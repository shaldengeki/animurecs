<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(['action' => $this->url("switch_user"), 'class' => 'form-horizontal']); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='switch_username'>Username</label>
          <div class='controls'>
            <input name='switch_username' type='text' class='input-xlarge' id='switch_username' />
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>Switch</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>Back</a>
        </div>
      </fieldset>
    </form>