#!/usr/bin/python
# -*- coding: utf-8 -*-
"""Fetches MAL anime lists within a specified interval of user IDs and inserts them into the database.
"""

import argparse
import pytz
import sys
import time

import DbConn

import myanimelist.session
import myanimelist.user
import animurecs_bot
import animurecs_modules

def zero_pad(number, length):
  """Returns string of the input number, zero-padded to total length length.
  """
  return ("0"*(length - len(unicode(number)))) + unicode(number)

def mysql_date(dt):
  """Returns a mysql date-formatted string for the given datetime object.
  """
  return "-".join([
    zero_pad(dt.year, 4),
    zero_pad(dt.month, 2),
    zero_pad(dt.day, 2)
  ])

def get_max_user_id(queue):
  """Returns the highest user ID in the given queue.
  """
  # get the idx of the user_id field.
  user_id_idx = queue._fields.index('user_id')
  print ""
  print "Exception detected, flushing queue. Highest user ID: " + str(max(queue, key=lambda x: x[user_id_idx])[user_id_idx])

if __name__ == '__main__':
  mal_session = myanimelist.session.Session()
  bot = animurecs_bot.animurecs('fetch_mal_lists', animurecs_modules, config_file='config.txt')
  db = bot.dbs['animurecs']

  parser = argparse.ArgumentParser()
  parser.add_argument("--start", default=1,
                      help="start user ID for range fetch")
  parser.add_argument("--end", required=True,
                      help="end user ID for range fetch")
  parser.add_argument("--per_minute", default=6,
                      help="number of users per minute to fetch, between 1 and 60 inclusive")
  args = parser.parse_args()

  print "Calculating ID range..."
  if args.start:
    start_id = int(args.start)
  end_id = int(args.end)
  print "ID range: " + str(start_id) + " to " + str(end_id)
  if start_id >= end_id:
    print "ID range is empty, halting."
    sys.exit(0)

  if int(args.per_minute) < 1 or int(args.per_minute) > 60:
    print "Users per minute must be between 1 and 60, inclusive"
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
  ]).update('started=VALUES(started), time=VALUES(time), finished=VALUES(finished), status=VALUES(status), score=VALUES(score), episode=VALUES(episode)')

  mal_statuses_to_int = {
      'Watching': 1,
      'Completed': 2,
      'On-Hold': 3,
      'Dropped': 4,
      'Plan to Watch': 6
  }

  sleep_time = 60 / int(args.per_minute)

  try:
    # loop over every user_id in this range, fetching their lists.
    for user_id in xrange(start_id, end_id+1):
      try:
        username = myanimelist.user.User.find_username_from_user_id(mal_session, user_id)
      except myanimelist.user.InvalidUserError as e:
        print "Invalid user ID: " + str(user_id) + ". Skipping."
        time.sleep(sleep_time)
        continue
      user_list = mal_session.anime_list(username)
      if len(user_list) > 0:
        for anime in user_list:
          if user_list[anime]['score'] is None:
            user_list[anime]['score'] = 0

          if user_list[anime]['started'] is not None:
            started = mysql_date(user_list[anime]['started'])
          else:
            started = None

          if user_list[anime]['last_updated'] is not None:
            entry_time = pytz.utc.localize(user_list[anime]['last_updated']).strftime('%Y-%m-%d %H:%M:%S')
          else:
            entry_time = None

          if user_list[anime]['finished'] is not None:
            finished = mysql_date(user_list[anime]['finished'])
          else:
            finished = None

          list_insert_queue.queue({
            'user_id': int(user_id),
            'anime_id': int(anime.id),
            'started': started,
            'time': entry_time,
            'finished': finished,
            'status': int(mal_statuses_to_int[user_list[anime]['status']]),
            'score': int(user_list[anime]['score']),
            'episode': int(user_list[anime]['score'])
          })
        print u"Finished with " + username + u". (" + unicode(len(user_list)) + u" entries, " + unicode(user_id) + u"/" + unicode(end_id) + u")"
      time.sleep(sleep_time)
    list_insert_queue.flush()
  except:
    list_insert_queue.beforeFlush(get_max_user_id).flush()
    raise
  print "Done!"