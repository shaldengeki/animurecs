<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
      <div class='page-header'>
        <h1>All Users</h1>
      </div>
      <table class='table table-striped table-bordered dataTable'>
        <thead>
          <tr>
            <th>Username</th>
            <th>Role</th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
<?php
  $userGroup = new UserGroup($this->app, array_keys($this->dbConn->table(User::$MODEL_TABLE)->fields('id')->order('username ASC')->assoc('id')));
  foreach ($userGroup->load('info') as $thisUser) {
?>          <tr>
            <td><?php echo $thisUser->link("show", $thisUser->username()); ?></td>
            <td><?php echo escape_output($thisUser->usermaskText()); ?></td>
            <td><?php echo $this->app->user->isAdmin() ? $thisUser->link("edit", "Edit") : ""; ?></td>
            <td><?php echo $this->app->user->isAdmin() ? $thisUser->link("delete", "Delete"): ""; ?></td>
          </tr>
<?php
  }
?>
        </tbody>
      </table>