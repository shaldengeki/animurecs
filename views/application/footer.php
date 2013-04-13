<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
  $params['container'] = isset($params['container']) ? $params['container'] : True;
  $adminUser = new User($this, Null, 'shaldengeki');
?>
      <footer>
        <hr />
        <ul>
          <li><a href='/'>Home</a></li>
          <li><a href='//blog.animurecs.com'>Blog</a></li>
          <li><a href='https://twitter.com/guocharles'>Twitter</a></li>
        </ul>
        <p>Created and maintained by <?php echo $adminUser->link('show', $adminUser->username); ?>.</p>
<?php
  if (Config::DEBUG_ON) {
?>
        <pre><?php echo escape_output(print_r($this->dbConn->queryLog, True)); ?></pre>
        <pre>Rendering took <?php echo round((microtime(true) - $this->startRender)*1000, 2); ?>ms</pre>
        <!--<pre><?php //echo escape_output(print_r($GLOBALS, True)); ?></pre>-->
<?php
  }
?>
    </footer>
<?php
  if ($params['container']) {
?>
      </div>
<?php
  }
?>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-37523517-1']);
      var pluginUrl = '//www.google-analytics.com/plugins/ga/inpage_linkid.js';
      _gaq.push(['_require', 'inpage_linkid', pluginUrl]);
      _gaq.push(['_trackPageview']);
      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
  </body>
</html>