# !/usr/bin/env python

''' 
  animurecs_modules - Provides functions for animurecs_bot.
  Author - Shal Dengeki <shaldengeki@gmail.com>
'''
import calendar
import datetime
import pytz

import DbConn
import myanimelist.session
import myanimelist.media_list
import update_daemon

class Modules(update_daemon.Modules):
  '''
  Provides modules for animurecs_bot.
  '''
  def __init__(self, daemon):
    super(Modules, self).__init__(daemon)
    self.update_functions = [
                              self.import_mal_profiles
                            ]
    self.mal_statuses_to_int = {
      'Watching': 1,
      'Completed': 2,
      'On-Hold': 3,
      'Dropped': 4,
      'Plan to Watch': 6
    }
  def import_mal_profiles(self):
    '''
    Processes the MAL profile scraping queue.
    '''
    if (datetime.datetime.now(tz=pytz.utc) - self.info['last_run_time']) < datetime.timedelta(hours=1):
      return
    self.info['last_run_time'] = datetime.datetime.now(tz=pytz.utc)
    self.daemon.log.info("Processing MAL profile queue.")

    mal_session = myanimelist.session.Session()
    import_requests = self.dbs['animurecs'].table('users').fields('id', 'mal_username', 'last_import').where('mal_username IS NOT NULL').where('last_import_failed = 0').order('last_import ASC').list()
    entries_to_add = []
    entry_insert_queue = DbConn.DbInsertQueue(self.dbs['animurecs'], 'anime_lists', [
      'user_id',
      'anime_id',
      'time',
      'status',
      'score',
      'episode'
    ]).ignore(True)

    requested_ids = map(lambda r: int(r['id']), import_requests)
    broken_ids = []

    for request in import_requests:
      # process MAL profile import request.
      self.daemon.log.info("Processing MAL profile for user ID " + str(request['id']) + ".")
      try:
        anime_list = mal_session.anime_list(request['mal_username']).load()
      except myanimelist.media_list.InvalidMediaListError as e:
        self.daemon.log.error("Invalid MAL username provided: " + request['mal_username'] + ". Marking as broken and skipping.")
        broken_ids.append(int(request['id']))
        continue
      curr_time = datetime.datetime.now(tz=pytz.timezone(self.daemon.config['timezone'])).strftime('%Y-%m-%d %H:%M:%S')
      for anime in anime_list.list:
        if anime_list.list[anime]['score'] is None:
          anime_list.list[anime]['score'] = 0
        entry_insert_queue.queue({
          'user_id': request['id'],
          'anime_id': int(anime.id),
          'time': pytz.timezone(self.daemon.config['timezone']).localize(anime_list.list[anime]['last_updated']).strftime('%Y-%m-%d %H:%M:%S'),
          'status': self.mal_statuses_to_int[anime_list.list[anime]['status']],
          'score': anime_list.list[anime]['score'],
          'episode': anime_list.list[anime]['episodes_watched']
        })
    # flush insert queue.
    entry_insert_queue.flush()

    # update last-import time and broken usernames.
    if requested_ids:
      self.dbs['animurecs'].table('users').set(last_import=datetime.datetime.now(tz=pytz.timezone(self.daemon.config['timezone'])).strftime('%Y-%m-%d %H:%M:%S')).where(id=requested_ids).update()
    if broken_ids:
      self.dbs['animurecs'].table('users').set(last_import_failed=1).where(id=broken_ids).update()
    self.daemon.log.info("Inserted entries for " + str(len(requested_ids)) + " users.")