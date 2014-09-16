#!/usr/bin/python
# -*- coding: utf-8 -*-
"""Fetches MAL anime lists within a specified interval of user IDs and inserts them into the database.
"""

import argparse
import sys

import DbConn

import myanimelist.session
import myanimelist.user
import animurecs_bot
import animurecs_modules

if __name__ == '__main__':
  mal_session = myanimelist.session.Session()
  bot = animurecs_bot.animurecs('fetch_mal_lists', animurecs_modules, config_file='config.txt')
  db = bot.dbs['animurecs']

  parser = argparse.ArgumentParser()
  parser.add_argument("--start", default=1,
                      help="start user ID for range fetch")
  parser.add_argument("--end", required=True,
                      help="end user ID for range fetch")
  args = parser.parse_args()

  print "Calculating ID range..."
  if args.start:
    start_id = int(args.start)
  end_id = int(args.end)
  print "ID range: " + str(start_id) + " to " + str(end_id)
  if start_id >= end_id:
    print "ID range is empty, halting."
    sys.exit(0)

  list_insert_queue = DbConn.DbInsertQueue(db, table='mal_anime_lists', fields=[
    'user_id',
    'anime_id',
    'started',
    'time',
    'finished',
    'status',
    'score',
    'episode'
  ]).ignore(True)

  mal_statuses_to_int = {
      'Watching': 1,
      'Completed': 2,
      'On-Hold': 3,
      'Dropped': 4,
      'Plan to Watch': 6
  }
  # loop over every user_id in this range, fetching their lists.
  for user_id in range(start_id, end_id+1):
    try:
      username = myanimelist.user.User.find_username_from_user_id(mal_session, user_id)
    except myanimelist.user.InvalidUserError as e:
      print "Invalid user ID: " + str(user_id) + ". Skipping."
      continue
    user_list = mal_session.anime_list(username)
    if len(user_list) > 0:
      for anime in user_list:
        if user_list[anime]['score'] is None:
          user_list[anime]['score'] = 0

        if user_list[anime]['started'] is not None:
          started = pytz.utc.localize(user_list[anime]['started']).strftime('%Y-%m-%d %H:%M:%S')
        else:
          started = None

        if user_list[anime]['time'] is not None:
          time = pytz.utc.localize(user_list[anime]['time']).strftime('%Y-%m-%d %H:%M:%S')
        else:
          time = None

        if user_list[anime]['finished'] is not None:
          finished = pytz.utc.localize(user_list[anime]['finished']).strftime('%Y-%m-%d %H:%M:%S')
        else:
          finished = None

        list_insert_queue.queue({
          'user_id': int(user_id),
          'anime_id': int(anime.id),
          'started': started,
          'time': time,
          'finished': finished,
          'status': int(mal_statuses_to_int[user_list[anime]['status']]),
          'score': int(user_list[anime]['score']),
          'episode': int(user_list[anime]['score'])
        })
      print u"Finished with " + username + u". (" + unicode(len(user_list)) + u" entries, " + unicode(user_id) + u"/" + unicode(end_id) + u")"
  list_insert_queue.flush()
  print "Done!"