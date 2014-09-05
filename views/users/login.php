<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
  <p>
    Don't have an account? <a href='/register.php'>Sign up for one today!</a>
  </p>
  <?php echo $this->app->form(['action' => '/login.php', 'class' => 'form-horizontal']); ?>
    <div class='form-group'>
      <label class='control-label' for='username'>Username</label>
      <div class='controls'>
      <?php echo $this->app->input(['name' => 'username', 'type' => 'text', 'placeholder' => 'Username']); ?>
      </div>
    </div>
    <div class='form-group'>
      <label class='control-label' for='username'>Password</label>
      <div class='controls'>
      <?php echo $this->app->input(['name' => 'password', 'type' => 'password', 'placeholder' => 'Password']); ?>
      </div>
    </div>
    <!--<label class='checkbox'>
      <input type='checkbox'> Remember me
    </label>-->
    <button type='submit' class='btn btn-primary'>Sign in</button>
  </form>