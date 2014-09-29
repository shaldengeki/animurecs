#!/usr/bin/python
# -*- coding: utf-8 -*-

import animurecs_bot
import animurecs_modules
import datetime
import pytz
import os

if __name__ == '__main__':
  bot = animurecs_bot.animurecs('dump_mal_ratings', animurecs_modules, config_file='config.txt')
  output_filename = os.path.join(bot.config['scripts_path'], 'data', 'rawData' + unicode(datetime.datetime.now(tz=pytz.timezone('America/Chicago')).strftime('%m-%d-%y')) + u'.csv')

  print "Exporting MAL ratings into " + output_filename + "..."
  exportRatings = bot.database['animurecs'].query(u"""SELECT user_id, anime_id, score
    INTO OUTFILE '""" + output_filename + """
    FIELDS TERMINATED BY ','
    LINES TERMINATED BY '\n'
    FROM mal_anime_lists
    WHERE score != 0
    ORDER BY user_id ASC""")

  print "Finished."