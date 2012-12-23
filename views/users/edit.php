<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
            <h1><?php echo escape_output($this->username()); ?></h1>
            <div class='editUserTabs'>
              <ul class='nav nav-tabs'>
                <li class='active'><a href='#generalSettings' data-toggle='tab'>General</a></li>
                <li><a href='#malImport' data-toggle='tab'>MAL Import</a></li>
                <li><a href='#privacySettings' data-toggle='tab'>Privacy</a></li>
              </ul>
              <div class='tab-content'>
                <div class='tab-pane active' id='generalSettings'>
                  <?php echo $this->view("form", $params); ?>
                </div>
                <div class='tab-pane' id='malImport'>
                  <p>To import your list, we'll need your MAL username:</p>
                  <?php echo $this->app->form(array('action' => echo $this->url("mal_import"), 'class' => 'form form-inline')); ?>
                    <input type='text' name='user[mal_username]' placeholder='MAL username' />
                    <input type='submit' class='btn btn-primary' value='Import' />
                  </form>
                </div>
                <div class='tab-pane' id='privacySettings'>
                  Coming soon!
                </div>
              </div>
            </div>