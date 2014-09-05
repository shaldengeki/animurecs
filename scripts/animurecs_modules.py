# !/usr/bin/env python

''' 
  animurecs_modules - Provides functions for animurecs_bot.
  Author - Shal Dengeki <shaldengeki@gmail.com>
'''
import datetime
import itertools
import requests
import time
import pytz
import xml
from xml.etree import ElementTree
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
  def login_to_mal(self):
    '''
    Logs into MAL and returns a requests session object.
    '''
    s = requests.session()
    s.headers['Referer'] = 'http://myanimelist.net'
    s.headers['Host'] = 'myanimelist.net'
    s.headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
    s.headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36'
    s.post('http://myanimelist.net/login.php', {'username': str(self.daemon.config['MAL']['username']), 'password': str(self.daemon.config['MAL']['password']), 'sublogin': ' Login '})
    return s
  def import_mal_profiles(self):
    '''
    Processes the MAL profile scraping queue.
    '''
    if (datetime.datetime.now(tz=pytz.utc) - self.info['last_run_time']) < datetime.timedelta(hours=1):
      return
    self.info['last_run_time'] = datetime.datetime.now(tz=pytz.utc)
    self.daemon.log.info("Processing MAL profile queue.")

    mal_session = self.login_to_mal()
    import_requests = self.dbs['animurecs'].table('users').fields('id', 'mal_username', 'last_import').where('mal_username IS NOT NULL').order('last_import ASC').list()

    entries_to_add = []

    for request in import_requests:
      # process MAL profile import request.
      user_entries = 0
      self.daemon.log.info("Processing MAL profile for user ID " + str(request['id']) + ".")
      for _ in range(10):
        while True:
          profile_html = mal_session.get('http://myanimelist.net/malappinfo.php?u=' + str(request['mal_username']) + '&status=all&type=anime').text.encode('utf-8')
          try:
            xml_object = ElementTree.fromstring(profile_html)
            break
          except (xml.parsers.expat.ExpatError, ElementTree.ParseError, xml.etree.ElementTree.ParseError):
            self.daemon.log.debug("Improper MAL list XML for user ID " + str(request['id']) + ". Trying again.")
            time.sleep(1)
            continue
        if xml_object is not None:
          break
      # catch edge cases where the XML is malformed.
      if xml_object is None:
        self.daemon.log.debug("Improper MAL list XML for user ID " + str(request['id']) + ". Skipping.")
        continue
      anime_objects = xml_object.findall('anime')
      if len(xml_object.findall('error')) > 0:
        self.daemon.log.error("MAL error fetching list for user ID " + str(request['id']) + ". Skipping. | HTML: " + str(profile_html))
        continue
      elif len(anime_objects) == 0:
        self.daemon.log.debug("Empty MAL list found for user ID " + str(request['id']) + ". Skipping. | HTML: " + str(profile_html))
        continue
      # we have an intact anime list.
      # get everything that's updated since the last_import time.
      for anime in itertools.ifilter(lambda x: datetime.datetime.fromtimestamp(int(x.find('my_last_updated').text), tz=pytz.utc) > pytz.timezone('Europe/Paris').localize(request['last_import']), anime_objects):
        user_entries += 1
        entries_to_add.append([request['id'], int(anime.find('series_animedb_id').text), datetime.datetime.fromtimestamp(int(anime.find('my_last_updated').text), tz=pytz.utc).strftime('%Y-%m-%d %H:%M:%S'), int(anime.find('my_status').text), int(anime.find('my_score').text), int(anime.find('my_watched_episodes').text)])

      # add entries to the database.
      if entries_to_add:
        self.dbs['animurecs'].table('anime_lists').fields('user_id', 'anime_id', 'time', 'status', 'score', 'episode').values(entries_to_add).onDuplicateKeyUpdate('id=id').insert()

      # update last-import time.
      self.dbs['animurecs'].table('users').set(last_import=datetime.datetime.now(tz=pytz.timezone('Europe/Paris')).strftime('%Y-%m-%d %H:%M:%S')).where(id=request['id']).update()
      self.daemon.log.info("Inserted " + str(user_entries) + " entries for userID " + str(request['id']) + ".")