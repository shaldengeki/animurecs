<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<!-- Google Code for Register Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 1002810615;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "AAoeCJHY1gUQ99mW3gM";
var google_conversion_value = 0;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/1002810615/?value=0&amp;label=AAoeCJHY1gUQ99mW3gM&amp;guid=ON&amp;script=0"/>
</div>
<?php echo $this->link('show', 'Click here to continue', Null, False, Null, ['status' => "Congrats! You're now signed in as ".escape_output($this->username()).". Why not start out by adding some anime to your list?", 'class' => 'success']); ?>
</noscript>
<script type="text/javascript">
  $(document).ready(function() {
    <?php echo $this->app->jsRedirect($this->url('show'), ['status' => "Congrats! You're now signed in as ".escape_output($this->username()).". Why not start out by adding some anime to your list?", 'class' => 'success']); ?>
  });
</script>