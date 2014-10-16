<?php

class UserGroup extends ModelGroup {
  // class to provide mass-querying functions for groups of userIDs or user objects.
  protected $_groupTable = "users";
  protected $_groupTableSingular = "user";
  protected $_groupObject = "User";
  protected $_nameField = "username";

  public function users() {
    return $this->objects();
  }
}
?>