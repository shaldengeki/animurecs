<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstAnime = Anime::Get($this->app);
  $newEntry = new AnimeEntry($this->app, Null, ['user' => $this]);
  $params['anime'] = isset($params['anime']) ? $params['anime'] : Null;
  $animeEntry = ['score' => 0, 'status' => 0, 'episode' => 0];

  if (!$this->app->user->loggedIn()) {
    // display nothing to non-logged-in users.
    return;
  }
?>
                <div class='row-fluid addListEntryForm'>
                  <?php echo $this->app->form(['action' => $newEntry->url("new"), 'class' => 'form-inline']); ?>
                    <?php echo $newEntry->input('user_id', ['type' => 'hidden', 'value' => intval($this->id)]); ?>
<?php
  if ($params['anime'] === Null) {
    $displaySecondColumn = False;
?>
                    <span class='input-group input-group-sm col-md-12'>
                      <input name='anime_entries_anime_title' id='anime_entries_anime_title' type='text' class='autocomplete autocomplete-shrink form-control' data-labelField='title' data-valueField='id' data-url='<?php echo $firstAnime->url("token_search"); ?>' data-tokenLimit='1' data-outputElement='#anime_entries\[anime_id\]' data-status-url='<?php echo $this->url('anime'); ?>' placeholder='Have an anime to update? Type it in!' />
                      <?php echo $newEntry->input('anime_id', ['type' => 'hidden', 'value' => '']); ?>
                    </span>
<?php
  } else {
    $displaySecondColumn = True;
    if (isset($this->app->user->animeList()->uniqueList()[$params['anime']->id])) {
      $animeEntry = $this->app->user->animeList()->uniqueList()[$params['anime']->id];
      $addText = "Update your list: ";
    } else {
      $addText = "Add to your list: ";
    }
?>
                      <?php echo $addText; ?>
                      <?php echo $newEntry->input('anime_id', ['type' => 'hidden', 'value' => $params['anime']->id]); ?>
<?php
  }
?>
                    <span class="form-group"<?php echo $displaySecondColumn ? "" : " style=\"display: none;\" "; ?>>
                      <div class='input-group input-group-sm col-md-5'>
                        <?php echo display_status_dropdown("anime_entries[status]", "form-control", $animeEntry['status'] ? $animeEntry['status'] : 1); ?>
                      </div>
                      <div class='input-group input-group-sm col-md-2'>
                        <?php echo $newEntry->input('score', ['type' => 'number', 'min' => 0, 'max' => 10,  'step' => 'any', 'value' => $animeEntry['score'] ? $animeEntry['score'] : ""]); ?>
                        <span class="input-group-addon">/10</span>
                      </div>
                      <div class='input-group input-group-sm col-md-3'>
                        <span class="input-group-addon">Ep</span>
                        <?php echo $newEntry->input('episode', ['type' => 'number', 'min' => 0, 'step' => 1, 'value' => $animeEntry['episode'] ? $animeEntry['episode'] : ""]); ?>
                        <span class="input-group-addon"><?php echo $params['anime'] ? "/".$params['anime']->episodeCount() : ""; ?></span>
                      </div>
                      <input type='submit' class='btn btn-sm btn-primary updateEntryButton' value='Update' />
                    </span>
                  </form>
                </div>
