<?php

trait Aliasable {
  // allows an object to have aliases.
  
  protected $aliases;

  public function getAliases() {
    // returns a list of comment objects sent by this user.
    $aliasQuery = $this->dbConn->table('aliases')->fields('id')->where(['type' => static::MODEL_NAME(), 'parent_id' => $this->id])->order('name ASC')->query();
    $aliases = [];
    while ($alias = $aliasQuery->fetch()) {
      $aliases[intval($alias['id'])] = new Alias($this->app, intval($alias['id']));
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