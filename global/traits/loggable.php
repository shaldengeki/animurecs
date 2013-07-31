<?php

trait Loggable {
  // allows an object to be logged.
  
  protected $logger=Null;

  public function log($logger) {
    // sets a logger for this object.
    $this->logger = $logger;
    return $this;
  }
  public function unlog() {
    $this->logger = Null;
    return $this;
  }
  public function canLog() {
  	return $this->logger !== Null;
  }
}

?>