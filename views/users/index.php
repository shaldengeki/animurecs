<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $app->check_partial_include(__FILE__);
?>
      <h1>All Users</h1>
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
  $users = array_keys($this->dbConn->queryAssoc("SELECT `users`.`id` FROM `users` ORDER BY `users`.`username` ASC", 'id'));
  $userGroup = new UserGroup($this->dbConn, $users);
  $userGroup->info();
  foreach ($userGroup->users() as $thisUser) {
?>          <tr>
            <td><?php echo $thisUser->link("show", $thisUser->username()); ?></td>
            <td><?php echo escape_output(convert_usermask_to_text($thisUser->usermask())); ?></td>
            <td><?php echo $app->user->isAdmin() ? $thisUser->link("edit", "Edit") : ""; ?></td>
            <td><?php echo $app->user->isAdmin() ? $thisUser->link("delete", "Delete"): ""; ?></td>
          </tr>
<?php
  }
?>
        </tbody>
      </table>