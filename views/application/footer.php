<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
?>
      <hr />
      <p>Created and maintained by <a href='<?php echo Config::ROOT_URL; ?>/users/1/show/'>shaldengeki</a>.</p>
<?php
  if (Config::DEBUG_ON) {
?>
      <pre><?php echo escape_output(print_r($GLOBALS['database']->queryLog, True)); ?></pre>
      <pre><?php echo escape_output(print_r($GLOBALS, True)); ?></pre>
<?php
  }
?>
    </div>
  </body>
</html>";