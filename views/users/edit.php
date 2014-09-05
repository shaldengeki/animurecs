<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
            <h1><?php echo escape_output($this->username); ?></h1>
            <div class='editUserTabs'>
              <ul class='nav nav-tabs'>
                <li class='active'><a href='#generalSettings' data-toggle='tab'>General</a></li>
                <li><a href='#malImport' data-toggle='tab'>MAL Import</a></li>
                <li><a href='#privacySettings' data-toggle='tab'>Privacy</a></li>
              </ul>
              <div class='tab-content'>
                <div class='tab-pane active' id='generalSettings'>
                  <div class='row'>
                    <div class='col-xs-12 col-md-6'>
                      <?php echo $this->view("form", $params); ?>
                    </div>
                  </div>
                </div>
                <div class='tab-pane' id='malImport'>
                  <div class='row'>
                    <div class='col-xs-12 col-md-6'>
                      <p>
                        Animurecs can sync changes made to your MAL into your AR feed. If you'll enter your MAL username, we'll check a couple times a day to see if you've updated.
                      </p>
                      <p><em>Last import time: <?php echo $this->lastImport === Null ? "Never" : $this->lastImport->format('G:i n/j/y e'); ?></em></p>
                      <?php echo $this->app->form(['action' => $this->url("mal_import"), 'class' => 'form form-inline']); ?>
                        <div class='form-group'>
                          <label class='sr-only' for='user[mal_username]'>MAL Username</label>
                          <?php echo $this->input('mal_username', ['type' => 'text', 'placeholder' => 'MAL username', 'value' => $this->malUsername]); ?>
                        </div>
                        <button type='submit' class='btn btn-primary'>Import</button>
                      </form>
                    </div>
                  </div>
                </div>
                <div class='tab-pane' id='privacySettings'>
<?php
/*
                  <div class='row'>
                    <div class='col-xs-12 col-md-6'>
                      <h2>Delete your account <small>(danger danger)</small></h2>
                      <?php echo $this->app->form(['action' => $this->url("delete"), 'class' => 'form']); ?>
                        <?php echo $this->input('delete', ['type' => 'submit', 'class' => 'btn btn-danger', 'value' => 'Delete my account']); ?>
                      </form>
                    </div>
                  </div>
*/
?>
                </div>
              </div>
            </div>