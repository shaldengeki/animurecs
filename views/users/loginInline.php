<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
  <?php echo $this->app->form(['action' => '/login.php', 'class' => 'form-inline']); ?>
    <div class='form-group'>
      <label class='sr-only' for='username'>Username</label>
      <?php echo $this->app->input(['name' => 'username', 'type' => 'text', 'class' => 'input-sm', 'placeholder' => 'Username']); ?>
    </div>
    <div class='form-group'>
      <label class='sr-only' for='password'>Password</label>
      <?php echo $this->app->input(['name' => 'password', 'type' => 'password', 'class' => 'input-sm', 'placeholder' => 'Password']); ?>
    </div>
    <!--<label class='checkbox'>
      <input type='checkbox'> Remember me
    </label>-->
    <button type='submit' class='btn btn-primary btn-sm'>Sign in</button>
    <a href="/register.php" class="btn btn-success btn-sm">Sign Up</a>
  </form>