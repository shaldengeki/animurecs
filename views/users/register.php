<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(['action' => $_SERVER['SCRIPT_NAME'], 'name' => 'register', 'class' => 'form-horizontal']); ?>
      <fieldset>
        <legend>Signing up is easy! Fill in a few things...</legend>
        <div class='control-group'>
          <label class='control-label'>A username:</label>
          <div class='controls'>
            <input type='text' class='' name='username' id='username' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Your password:</label>
          <div class='controls'>
            <input type='password' class='' name='password' id='password' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Repeat that password:</label>
          <div class='controls'>
            <input type='password' class='' name='password_confirmation' id='password_confirmation' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Your email:</label>
          <div class='controls'>
            <input type='text' class='' name='email' id='email' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>... And you're done!</label>
          <div class='controls'>
            <button type='submit' class='btn btn-primary'>Sign up</button>
          </div>
        </div>
      </fieldset>
    </form>