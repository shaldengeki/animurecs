<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
  <?php echo $this->app->form(['action' => '/login.php', 'class' => 'form-inline']); ?>
    <?php echo $this->app->input(['name' => 'username', 'type' => 'text', 'class' => 'input-small', 'placeholder' => 'Username']); ?>
    <?php echo $this->app->input(['name' => 'password', 'type' => 'password', 'class' => 'input-small', 'placeholder' => 'Password']); ?>
    <!--<label class='checkbox'>
      <input type='checkbox'> Remember me
    </label>-->
    <button type='submit' class='btn btn-primary btn-small'>Sign in</button>
    <a href="/register.php" class="btn btn-success btn-small">Sign Up</a>
  </form>