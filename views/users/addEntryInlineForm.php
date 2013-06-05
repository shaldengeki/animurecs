<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $anime = Anime::first($this->app);
?>
                <div class='row-fluid addListEntryForm'>
                  <?php echo $this->app->form(['action' => $this->animeList()->url("new"), 'class' => 'form-inline']); ?>
                    <div class='addListEntryFormCol1 span12'>
                      <?php echo $this->animeList()->input('user_id', ['type' => 'hidden', 'value' => intval($this->id)]); ?>
                      <input name='anime_lists[user_id]' id='anime_lists_user_id' type='hidden' value='<?php echo intval($this->id); ?>' />
                      <input name='anime_lists_anime_title' id='anime_lists_anime_title' type='text' class='autocomplete autocomplete-shrink span12' data-labelField='title' data-valueField='id' data-url='<?php echo $anime->url("token_search"); ?>' data-tokenLimit='1' data-outputElement='#anime_lists\[anime_id\]' data-status-url='<?php echo $this->url('anime'); ?>' placeholder='Have an anime to update? Type it in!' />
                      <?php echo $this->animeList()->input('anime_id', ['type' => 'hidden', 'value' => '']); ?>
                    </div>
                    <div class='addListEntryFormCol2 row-fluid'>
                      <?php echo display_status_dropdown("anime_lists[status]", "span5", 1); ?>
                      <div class='input-append'>
                        <?php echo $this->animeList()->input('score', ['type' => 'number', 'class' => 'input-mini', 'min' => 0, 'max' => 10, 'value' => '']); ?>
                        <span class='add-on'>/10</span>
                      </div>
                      <div class='input-prepend'>
                        <span class='add-on'>Ep</span>
                        <?php echo $this->animeList()->input('episode', ['type' => 'number', 'class' => 'input-mini', 'min' => 0, 'step' => 1, 'value' => '']); ?>
                      </div>
                      <input type='submit' class='btn btn-primary updateEntryButton' value='Update' />
                    </div>
                  </form>
                </div>