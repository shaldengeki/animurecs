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
                  <?php echo $this->app->form(['action' => $this->url("mal_import"), 'class' => 'form form-inline']); ?>
                    <?php echo $this->input('mal_username', ['type' => 'text', 'placeholder' => 'MAL username']); ?>
                    <?php echo $this->input('import', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => 'Import']); ?>
                  </form>
                </div>
                <div class='tab-pane' id='privacySettings'>
<?php
/*
                  <h2>Delete your account <small>(danger danger)</small></h2>
                  <?php echo $this->app->form(['action' => $this->url("delete"), 'class' => 'form']); ?>
                    <?php echo $this->input('delete', ['type' => 'submit', 'class' => 'btn btn-danger', 'value' => 'Delete my account']); ?>
                  </form>
*/
?>
                </div>
              </div>
            </div>