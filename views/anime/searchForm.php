<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $anime = new Anime($this->app, 0);
?>
                <div class='animeSearchForm'>
                  <?php echo $this->app->form(array('method' => 'get', 'action' => $anime->url('index'), 'class' => 'form-inline')); ?>
                    <input name='id' id='anime_title' type='hidden' value='<?php echo intval($anime->title); ?>' />
                    <input name='search' id='search' type='text' class='autocomplete input-xlarge' data-labelField='title' data-valueField='title' data-url='<?php echo $anime->url("token_search"); ?>' data-tokenLimit='1' data-outputElement='#anime_title' value='<?php echo escape_output($_REQUEST['search']); ?>' placeholder='Search for an anime' />
                    <input type='submit' class='btn btn-primary' value='Search' />
                  </form>
                </div>