<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <form action='<?php echo ($this->id === 0) ? $this->url("new") : $this->url("edit"); ?>' method='POST' enctype='multipart/form-data' class='form-horizontal'><?php echo ($this->id === 0) ? "" : "<input type='hidden' name='user[id]' value='".intval($this->id)."' />"; ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='user[name]'>Name</label>
          <div class='controls'>
            <input name='user[name]' type='text' class='input-xlarge' id='user[name]'<?php echo ($this->id === 0) ? "" : " value='".escape_output($this->name())."'"; ?> />
          </div>
        </div>
<?php
  if ($this->id === 0) {
?>        <div class='control-group'>
          <label class='control-label' for='user[username]'>Username</label>
          <div class='controls'>
            <input name='user[username]' type='text' class='input-xlarge' id='user[username]'<?php echo ($this->id === 0) ? "" : " value='".escape_output($this->username())."'"; ?> />
          </div>
        </div>
<?php
  } else {
?>            <input name='user[username]' type='hidden' value='<?php echo escape_output($this->username()); ?>' />
<?php
  }
?>        <div class='control-group'>
          <label class='control-label' for='user[password]'>Password</label>
          <div class='controls'>
            <input name='user[password]' type='password' class='input-xlarge' id='user[password]' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[password_confirmation]'>Confirm Password</label>
          <div class='controls'>
            <input name='user[password_confirmation]' type='password' class='input-xlarge' id='user[password_confirmation]' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[email]'>Email</label>
          <div class='controls'>
            <input name='user[email]' type='email' class='input-xlarge' id='user[email]'<?php echo ($this->id === 0) ? "" : " value='".escape_output($this->email())."'"; ?>>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[about]'>About</label>
          <div class='controls'>
            <textarea name='user[about]' id='user[about]' rows='5'><?php echo ($this->id === 0) ? "" : escape_output($this->about()); ?></textarea>
          </div>
        </div>
<?php
  if ($this->id != 0) {
?>        <div class='control-group'>
          <label class='control-label' for='avatar_image'>Avatar</label>
          <div class='controls'>
            <input name='avatar_image' class='input-file' type='file' onChange='displayImagePreview(this.files);' /><span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>
<?php
  }
  if ($this->allow($this->app->user, $this->id === 0 ? 'new' : 'edit') && $this->app->user->isStaff()) {
?>      <div class='control-group'>
          <label class='control-label' for='user[usermask]'>Role(s)</label>
          <div class='controls'>
            <?php echo display_user_roles_select("user[usermask][]", ($this->id === 0) ? 0 : intval($this->usermask())); ?>
          </div>
        </div>
<?php
  } else {
?>      <input type='hidden' name='user[usermask][]' value='<?php echo $this->id === 0 ? 1 : intval($this->usermask()); ?>' />
<?php
        }
?>      <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Sign Up" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>