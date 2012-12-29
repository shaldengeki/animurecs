<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
  <?php echo $this->app->form(array('action' => '/login.php?redirect_to='.(isset($_REQUEST['redirect_to']) ? urlencode($_REQUEST['redirect_to']) : urlencode($_SERVER['REQUEST_URI'])), 'class' => 'form-inline')); ?>  
    <input name='username' type='text' class='input-small' placeholder='Username'>
    <input name='password' type='password' class='input-small' placeholder='Password'>
    <!--<label class='checkbox'>
      <input type='checkbox'> Remember me
    </label>-->
    <button type='submit' class='btn btn-primary btn-small'>Sign in</button>
  </form>