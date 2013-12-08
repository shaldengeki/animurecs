<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(['action' => $_SERVER['SCRIPT_NAME'], 'name' => 'register', 'class' => 'form-horizontal']); ?>
      <legend>Signing up is easy! Fill in a few things...</legend>
      <div class='form-group'>
        <label class='control-label col-lg-3'>A username:</label>
        <div class='col-lg-9'>
          <input type='text' class='form-control' name='username' id='username' />
        </div>
      </div>
      <div class='form-group'>
        <label class='control-label col-lg-3'>Your password:</label>
        <div class='col-lg-9'>
          <input type='password' class='form-control' name='password' id='password' />
        </div>
      </div>
      <div class='form-group'>
        <label class='control-label col-lg-3'>Repeat that password:</label>
        <div class='col-lg-9'>
          <input type='password' class='form-control' name='password_confirmation' id='password_confirmation' />
        </div>
      </div>
      <div class='form-group'>
        <label class='control-label col-lg-3'>Your email:</label>
        <div class='col-lg-9'>
          <input type='text' class='form-control' name='email' id='email' />
        </div>
      </div>
      <div class='form-group'>
        <label class='control-label col-lg-3'>... And you're done!</label>
        <div class='col-lg-9'>
          <button type='submit' class='btn btn-primary'>Sign up</button>
        </div>
      </div>
    </form>