<?php
  if ($_SERVER['DOCUMENT_URI'] === $_SERVER['REQUEST_URI']) {
    echo "This partial cannot be viewed on its own.";
    exit;
  }
?>
    <form action='<?php echo $this->url("switch_user"); ?>' method='POST' class='form-horizontal'>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='switch_username'>Username</label>
          <div class='controls'>
            <input name='switch_username' type='text' class='input-xlarge' id='switch_username' />
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>Switch</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>Back</a>
        </div>
      </fieldset>
    </form>