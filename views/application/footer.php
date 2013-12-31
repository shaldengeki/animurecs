<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
  $params['container'] = isset($params['container']) ? $params['container'] : True;
  $adminUser = class_exists("User") ? new User($this, Null, 'shaldengeki') : Null;
?>
      <footer>
        <hr />
        <div class='row'>
          <ul class='pull-right'>
            <li><a href='/'>Home</a></li>
            <li><a href='http://blog.animurecs.com'>Blog</a></li>
            <li><a href='https://twitter.com/guocharles'>Twitter</a></li>
            <li><a href='https://github.com/shaldengeki/animurecs'>Github</a></li>
          </ul>
          <?php echo $adminUser ? "<p>Created and maintained by ".$adminUser->link('show', $adminUser->username).".</p>" : ""; ?>
        </div>
<?php
  if (Config::DEBUG_ON) {
?>
        <div class='row'>
          <pre><?php echo escape_output(print_r($this->dbConn->queryLog, True)); ?></pre>
        </div>
        <div class='row'>
          <pre>Rendering took <?php echo round((microtime(true) - $this->startRender)*1000, 2); ?>ms</pre>
        </div>
        <div class='row'>
          <pre><?php echo escape_output(print_r($_SESSION, True)); ?></pre>
          <pre><?php echo escape_output(print_r($_REQUEST, True)); ?></pre>
          <pre><?php echo escape_output(print_r($_POST, True)); ?></pre>
          <pre><?php echo escape_output(print_r($this->timingInfo(), True)); ?></pre>
          <!--<pre><?php //echo escape_output(print_r($GLOBALS, True)); ?></pre>-->
        </div>
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
    <script src='//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js' type='text/javascript'></script>
    <script src='//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js' type='text/javascript'></script>

    <!--<script type='text/javascript' src='<?php echo Config::ROOT_URL; ?>/vendor/jquery-ui-timepicker-addon.min.js'></script>-->
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jquery.dropdownPlain.min.js' type='text/javascript'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/datatables/1.9.4/jquery.dataTables.min.js' type='text/javascript'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.3.1/jquery.cookie.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jquery.tokeninput.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jquery.json-2.3.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jquery.jqplot.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.barRenderer.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.categoryAxisRenderer.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.dateAxisRenderer.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.pieRenderer.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.highlighter.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/jqplot.trendline.min.js' type='text/javascript'></script>

    <script src='//www.google.com/jsapi' type='text/javascript'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/d3/3.2.2/d3.v3.min.js' type='text/javascript'></script>
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/d3-helpers.js' type='text/javascript'></script>

    <!--<script src='//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.0.0-rc2/js/bootstrap.min.js' type='text/javascript'></script>-->
    <script src='<?php echo Config::ROOT_URL; ?>/js/vendor/bootstrap.min.js' type='text/javascript'></script>
    <!--<script src='//cdnjs.cloudflare.com/ajax/libs/bootstrap-tour/0.2.0/bootstrap-tour.js' type='text/javascript'></script>-->
    <script src='<?php echo Config::ROOT_URL; ?>/js/animurecs.js?v=<?php echo $assetsVersion; ?>' type='text/javascript'></script>
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