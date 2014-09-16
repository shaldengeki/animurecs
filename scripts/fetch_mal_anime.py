#!/usr/bin/python
# -*- coding: utf-8 -*-

import datetime
import pytz
import os
import sys
import urllib2
import argparse
import animurecs_bot
import animurecs_modules

import myanimelist.session
import myanimelist.anime

def download_anime_image(image_url, dest_url, anime_id, db):
  if not os.path.exists(os.path.dirname(dest_url)):
    os.makedirs(os.path.dirname(dest_url))
  try:
    image = urllib2.urlopen(image_url)
  except urllib2.HTTPError:
    return False
  else:
    CHUNK = 16 * 1024
    with open(dest_url, 'wb') as fp:
      while True:
        chunk = image.read(CHUNK)
        if not chunk: break
        fp.write(chunk)
    foo = db.table('anime').set(image_path=os.path.join("img", "anime", str(anime_id), os.path.basename(dest_url))).where(id=int(anime_id)).limit(1).update()
    return True

if __name__ == '__main__':
  bot = animurecs_bot.animurecs('fetch_mal_anime', animurecs_modules, config_file='config.txt')
  db = bot.dbs['animurecs']
  mal_session = myanimelist.session.Session()

  parser = argparse.ArgumentParser()
  parser.add_argument("--start", default=1,
                      help="start anime ID for range fetch")
  parser.add_argument("--end", default=None,
                      help="end anime ID for range fetch")
  parser.add_argument("--only_new", action='store_true',
                      help="only update with new anime")
  args = parser.parse_args()

  print "Calculating ID range..."
  if args.only_new:
    start_id = int(db.table('anime').fields('MAX(id)').firstValue()) + 1
    end_id = myanimelist.anime.Anime.newest(mal_session).id
  else:
    if args.start:
      start_id = int(args.start)
    if args.end:
      end_id = int(args.end)
    else:
      end_id = myanimelist.anime.Anime.newest(mal_session)
  print "ID range: " + str(start_id) + " to " + str(end_id)
  if start_id >= end_id:
    print "ID range is empty, halting."
    sys.exit(0)

  TAG_TYPES = { 'general': 1, 'studio': 2, 'type': 4, 'va': 3 }

  # load all extant tags into memory.
  print "Loading tags..."
  # name:id
  TAGS = {}
  db_tags = db.table('tags').fields('id', 'name').query()
  db_tag = db_tags.fetchone()
  while db_tag is not None:
    TAGS[unicode(db_tag['name'])] = int(db_tag['id'])
    db_tag = db_tags.fetchone()

  # load all extant anime into memory.
  print "Loading anime..."
  # id:title
  ANIME = {}
  db_animes = db.table('anime').fields('id', 'title').order('id ASC').query()
  db_anime = db_animes.fetchone()
  last_db_anime_id = 0
  while db_anime is not None:
    ANIME[int(db_anime['id'])] = unicode(db_anime['title'])
    last_db_anime_id = int(db_anime['id'])
    db_anime = db_animes.fetchone()

  print "Loading aliases..."
  ALIASES = {}
  db_aliases = db.table('aliases').fields('parent_id', 'name').where(type='Anime').query()
  db_alias = db_aliases.fetchone()
  while db_alias is not None:
    if unicode(db_alias['name']) not in ALIASES:
      ALIASES[unicode(db_alias['name'])] = [int(db_alias['parent_id'])]
    else:
      ALIASES[unicode(db_alias['name'])].append(int(db_alias['parent_id']))
    db_alias = db_aliases.fetchone()

  print "Loading taggings..."
  TAGGINGS = {}
  db_taggings = db.table('anime_tags').fields('anime_id', 'tag_id').query()
  db_tagging = db_taggings.fetchone()
  while db_tagging is not None:
    if db_tagging['anime_id'] not in TAGGINGS:
      TAGGINGS[int(db_tagging['anime_id'])] = {int(db_tagging['tag_id']):1}
    else:
      TAGGINGS[int(db_tagging['anime_id'])][int(db_tagging['tag_id'])] = 1
    db_tagging = db_taggings.fetchone()

  alias_queue = db.insertQueue('aliases', ['name', 'type', 'parent_id', 'created_at', 'updated_at']).ignore(True)
  tagging_queue = db.insertQueue('anime_tags', ['tag_id', 'anime_id', 'created_user_id', 'created_at']).ignore(True)

  # loop over every anime_id in this range, fetching their lists.
  print "Looping over anime_ids " + str(start_id) + "-" + str(end_id) + "..."
  try:
    for anime_id in xrange(start_id, end_id+1):
      tags = {}
      curr_time_stamp = datetime.datetime.now(tz=pytz.timezone('Europe/Paris')).strftime('%Y-%m-%d %H:%M:%S')

      try:
        anime = mal_session.anime(anime_id).load()
      except myanimelist.anime.InvalidAnimeError as e:
        print "AnimeID " + str(anime_id) + " invalid. Moving on."
        continue

      # get image.
      img_path = u""
      if anime.picture is not None and anime.picture is not 'http://cdn.myanimelist.net/images/na_series.gif':
        file_name, file_extension = os.path.splitext(anime.picture)
        if file_name.endswith("l"):
          large_image_url = anime.picture
        else:
          large_image_url = file_name + "l" + file_extension
        dest_path = os.path.join(bot.config['path'], "/public/img/anime",str(anime_id), os.path.basename(large_image_url))
        if not download_anime_image(large_image_url, dest_path, anime_id, db):
          dest_path = os.path.join(bot.config['path'], "/public/img/anime",str(anime_id), os.path.basename(anime.picture))
          if download_anime_image(anime.picture, dest_path, anime_id, db):
            img_path = unicode(os.path.join("img", "anime", str(anime_id), os.path.basename(dest_path)))
        else:
          img_path = unicode(os.path.join("img", "anime", str(anime_id), os.path.basename(dest_path)))

      for synonym in (x for lang in anime.alternative_titles for x in anime.alternative_titles[lang]):
        if synonym not in ALIASES:
          alias_queue.queue({'name': synonym.encode('utf-8'), 'type': 'Anime', 'parent_id': int(anime_id), 'created_at': curr_time_stamp, 'updated_at': curr_time_stamp})
          ALIASES[synonym] = [anime_id]
        elif anime_id not in ALIASES[synonym]:
          alias_queue.queue({'name': synonym.encode('utf-8'), 'type': 'Anime', 'parent_id': int(anime_id), 'created_at': curr_time_stamp, 'updated_at': curr_time_stamp})
          ALIASES[synonym].append(anime_id)

      # process information.
      if anime.type not in TAGS:
        insert_tag = db.table('tags').set(name=anime.type.encode('utf-8'), description='', tag_type_id=int(TAG_TYPES['type']), created_user_id=1, created_at=curr_time_stamp, updated_at=curr_time_stamp).insert(ignore=True)
        TAGS[anime.type] = int(insert_tag.lastrowid)
      if TAGS[anime.type] not in tags:
        if anime_id not in TAGGINGS:
          TAGGINGS[anime_id] = {int(TAGS[anime.type]):1}
          tagging_queue.queue({'tag_id': int(TAGS[anime.type]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
        elif TAGS[anime.type] not in TAGGINGS[anime_id]:
          tagging_queue.queue({'tag_id': int(TAGS[anime.type]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
        tags[TAGS[anime.type]] = 1

      try:
        start_date = unicode(anime.aired[0].strftime('%Y-%m-%d %H-%M-%S'))
      except (ValueError, AttributeError):
        start_date = None
      if len(anime.aired) < 2 or anime.aired[1] is None:
        end_date = None
      else:
        try:
          end_date = unicode(anime.aired[1].strftime('%Y-%m-%d %H-%M-%S'))
        except ValueError:
          end_date = None

      for producer in anime.producers:
        producer = producer.name.lower()
        if producer not in TAGS:
          insert_tag = db.table('tags').set(name=producer.encode('utf-8'), description='', tag_type_id=int(TAG_TYPES['studio']), created_user_id=1, created_at=curr_time_stamp, updated_at=curr_time_stamp).insert(ignore=True)
          TAGS[producer] = int(insert_tag.lastrowid)
        if TAGS[producer] not in tags:
          if TAGS[producer] not in TAGGINGS[anime_id]:
            tagging_queue.queue({'tag_id': int(TAGS[producer]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
          tags[TAGS[producer]] = 1

      for genre in anime.genres:
        genre = genre.name.lower()
        if genre not in TAGS:
          insert_tag = db.table('tags').set(name=genre.encode('utf-8'), description='', tag_type_id=int(TAG_TYPES['general']), created_user_id=1, created_at=curr_time_stamp, updated_at=curr_time_stamp).insert(ignore=True)
          TAGS[genre] = int(insert_tag.lastrowid)
        if TAGS[genre] not in tags:
          if TAGS[genre] not in TAGGINGS[anime_id]:
            tagging_queue.queue({'tag_id': int(TAGS[genre]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
          tags[TAGS[genre]] = 1

      episode_length = unicode(anime.duration.total_seconds())

      for tag in anime.tags:
        tag = tag.name.lower()
        if tag not in TAGS:
          insert_tag = db.table('tags').set(name=tag.encode('utf-8'), description='', tag_type_id=int(TAG_TYPES['general']), created_user_id=1, created_at=curr_time_stamp, updated_at=curr_time_stamp).insert(ignore=True)
          TAGS[tag] = int(insert_tag.lastrowid)
        if TAGS[tag] not in tags:
          if TAGS[tag] not in TAGGINGS[anime_id]:
            tagging_queue.queue({'tag_id': int(TAGS[tag]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
          tags[TAGS[tag]] = 1

      for va in anime.voice_actors:
        va_name = va.name.lower()
        if va_name not in TAGS:
          insert_tag = db.table('tags').set(name=va_name.encode('utf-8'), description='', tag_type_id=int(TAG_TYPES['va']), created_user_id=1, created_at=curr_time_stamp, updated_at=curr_time_stamp).insert(ignore=True)
          TAGS[va_name] = int(insert_tag.lastrowid)
        if TAGS[va_name] not in tags:
          if TAGS[va_name] not in TAGGINGS[anime_id]:
            tagging_queue.queue({'tag_id': int(TAGS[va_name]), 'anime_id': int(anime_id), 'created_user_id': 1, 'created_at': curr_time_stamp})
          tags[TAGS[va_name]] = 1
      
      if int(anime_id) not in ANIME:
        # insert this anime.
        insert_anime = db.table('anime').set(id=int(anime_id), title=anime.title, description=anime.synopsis, episode_count=anime.episodes, episode_length=episode_length, started_on=start_date, ended_on=end_date, created_at=curr_time_stamp, updated_at=curr_time_stamp, image_path=img_path).insert()
        ANIME[int(insert_anime.lastrowid)] = anime.title
      else:
        # update this anime.
        updateAnime = db.table('anime').set(id=int(anime_id), title=anime.title, description=anime.synopsis, episode_count=anime.episodes, episode_length=episode_length, started_on=start_date, ended_on=end_date, created_at=curr_time_stamp, updated_at=curr_time_stamp, image_path=img_path).where(id=int(anime_id)).limit(1).update()
        ANIME[int(anime_id)] = anime.title

      try:
        print u"Finished with " + anime.title.decode('utf-8') + u" (" + unicode(anime_id) + u")"
      except UnicodeEncodeError:
        print u"Finished with anime_id " + unicode(anime_id) + u" (" + unicode(anime_id) + u")"
  except Exception as e:
    print "Exception detected, flushing queues."
    alias_queue.flush()
    tagging_queue.flush()
    raise
  alias_queue.flush()
  tagging_queue.flush()
  print u"Done!"