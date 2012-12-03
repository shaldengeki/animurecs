<?php

trait Aliasable {
  // allows an object to have aliases.
  
  protected $aliases;

  public function getAliases() {
    // returns a list of comment objects sent by this user.
    $aliasQuery = $this->dbConn->stdQuery("SELECT `id` FROM `aliases` WHERE `type` = '".$this->modelName()."' && `parent_id` = ".intval($this->id)." ORDER BY `name` ASC");
    $aliases = [];
    while ($alias = $aliasQuery->fetch_assoc()) {
      $aliases[intval($alias['id'])] = new Alias($this->dbConn, intval($alias['id']));
    }
    return $aliases;
  }
  public function aliases() {
    if ($this->aliases === Null) {
      $this->aliases = $this->getAliases();
    }
    return $this->aliases;
  }
}

?>