<?php
class AnimeList extends EntryList {
  // anime list.
  public static $TABLE = "anime_lists";
  public static $URL = "anime_lists";
  public static $PLURAL = "animeLists";

  public static $PART_NAME = "episode";
  public static $LIST_TYPE = "Anime";
  public static $TYPE_VERB = "watching";
  public static $FEED_TYPE = "Anime";
  public static $TYPE_ID = "anime_id";

  public $statusStrings, $scoreStrings, $partStrings;
  public function __construct(Application $app, $user_id=Null) {
    parent::__construct($app, $user_id);
  }
}
?>