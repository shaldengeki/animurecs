<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $anime = Anime::Get($this->app);
  $defaultParams['form'] = [
                            'method' => 'get',
                            'class' => 'searchForm form-inline',
                            'action' => $anime->url('index')
                          ];
  $defaultParams['searchInput'] = [
                                    'name' => 'search',
                                    'id' => 'search',
                                    'type' => 'text',
                                    'class' => 'autocomplete form-control',
                                    'data-labelField' => 'title', 
                                    'data-valueField' => 'title', 
                                    'data-url' => $anime->url("token_search"),
                                    'data-tokenLimit' =>  '1',
                                    'data-outputElement' => '#anime_title',
                                    'value' =>  isset($_REQUEST['search']) ? $_REQUEST['search'] : "",
                                    'placeholder' => 'Search for an anime'
                                  ];

  $params['submitButton'] = isset($params['submitButton']) ? $params['submitButton'] : True;
  $params['form'] = array_merge($defaultParams['form'], is_array($params['form']) ? $params['form'] : []);
  $params['searchInput'] = array_merge($defaultParams['searchInput'], is_array($params['searchInput']) ? $params['searchInput'] : []);
?>
                <?php echo $this->app->form($params['form']); ?>
                  <div class='form-group'>
                    <label class='sr-only' for='search'>Search</label>
                    <?php echo $this->app->input($params['searchInput']); ?>
                  </div>
                  <?php if ($params['submitButton']) { ?><button type='submit' class='btn btn-primary'>Search</button><?php } ?>
                </form>
