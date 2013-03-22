<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $anime = Anime::first($this->app);
?>
                <div class='addListEntryForm'>
                  <?php echo $this->app->form(array('action' => $this->animeList()->url("new"), 'class' => 'form-inline')); ?>
                    <input name='anime_list[user_id]' id='anime_list_user_id' type='hidden' value='<?php echo intval($this->id); ?>' />
                    <input name='anime_list_anime_title' id='anime_list_anime_title' type='text' class='autocomplete input-xlarge' data-labelField='title' data-valueField='id' data-url='<?php echo $anime->url("token_search"); ?>' data-tokenLimit='1' data-outputElement='#anime_list_anime_id' placeholder='Have an anime to update? Type it in!' />
                    <input name='anime_list[anime_id]' id='anime_list_anime_id' type='hidden' value='' />
                    <?php echo display_status_dropdown("anime_list[status]", "span2", 1); ?>
                    <div class='input-append'>
                      <input class='input-mini' name='anime_list[score]' id='anime_list_score' type='number' min='0' max='10' step='1' value='' />
                      <span class='add-on'>/10</span>
                    </div>
                    <div class='input-prepend'>
                      <span class='add-on'>Ep</span>
                      <input class='input-mini' name='anime_list[episode]' id='anime_list_episode' type='number' min='0' step='1' />
                    </div>
                    <input type='submit' class='btn btn-primary updateEntryButton' value='Update' />
                  </form>
                </div>