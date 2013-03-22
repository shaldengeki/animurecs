<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $anime = Anime::first($this->app);
  $defaultParams['form'] = array(
                            'method' => 'get',
                            'class' => 'form-inline',
                            'action' => $anime->url('index')
                          );
  $defaultParams['idInput'] = array(
                                'name' => 'id',
                                'id' => 'anime_title',
                                'type' => 'hidden',
                                'value' => intval($anime->title)
                              );
  $defaultParams['searchInput'] = array(
                                    'name' => 'search',
                                    'id' => 'search',
                                    'type' => 'text',
                                    'class' => 'autocomplete input-xlarge',
                                    'data-labelField' => 'title', 
                                    'data-valueField' => 'title', 
                                    'data-url' => $anime->url("token_search"),
                                    'data-tokenLimit' =>  '1',
                                    'data-outputElement' => '#anime_title',
                                    'value' =>  isset($_REQUEST['search']) ? $_REQUEST['search'] : "",
                                    'placeholder' => 'Search for an anime'
                                  );

  $params['submitButton'] = isset($params['submitButton']) ? $params['submitButton'] : True;
  $params['form'] = array_merge($defaultParams['form'], is_array($params['form']) ? $params['form'] : []);
  $params['idInput'] = array_merge($defaultParams['idInput'], is_array($params['idInput']) ? $params['idInput'] : []);
  $params['searchInput'] = array_merge($defaultParams['searchInput'], is_array($params['searchInput']) ? $params['searchInput'] : []);
?>
                <?php echo $this->app->form($params['form']); ?>
                  <?php echo $this->app->input($params['idInput']); ?>
                  <?php echo $this->app->input($params['searchInput']); ?>
                  <?php if ($params['submitButton']) { ?><input type='submit' class='btn btn-primary' value='Search' /><?php } ?>
                </form>
