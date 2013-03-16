<?php
class Cache {
  // Provides interface for memcached.
  private $memcached;
  public function __construct() {
    $this->memcached = new Memcached();
    $this->memcached->addServer(Config::MEMCACHED_HOST, intval(Config::MEMCACHED_PORT));
  }
  public function resultCode() {
    return $this->memcached->getResultCode();
  }
  public function set($key, $value) {
    // sets a key-value pair in the memcached server using CAS.
    if ($this->memcached === Null) {
      return False;
    }
    do {
      $cachedValue = $this->memcached->get($key, Null, $cas);
      if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
        // key is not yet set in cache.
        $this->memcached->add($key, $value);
      } else {
        // update the extant cache value.
        $this->memcached->cas($cas, $key, $value, Config::MEMCACHED_DEFAULT_LIFESPAN);
      }
    } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);
  }
  public function get($key, &$cas_token=Null) {
    // retrieves a key (or many keys) from the cache.
    if (is_array($key)) {
      $cacheValues = $this->memcached->getMulti($key, $cas_token);
      if (!$cacheValues or $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
        return False;
      } else {
        return $cacheValues;
      }
    } else {
      $cacheValue = $this->memcached->get($key, Null, $cas_token);
      if (!$cacheValue && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
        return False;
      } else {
        return $cacheValue;
      }
    }
  }
  public function delete($key) {
    // removes a key from the cache.
    if ($this->memcached->delete($key) === False || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
      return False;
    } else {
      return True;
    }
  }
}

?>