#!/usr/env/python

import animurecs_bot
import animurecs_modules

import glob
import os
import malSVD
import datetime
import pytz
import shutil
import subprocess
import urllib2

def get_max_mal_user_id(path):
  mal_data_filename = sorted(glob.glob(os.path.join(path, u"data", "rawData-*.txt")))[-1]
  mal_data_path = os.path.join(path, u"data", mal_data_filename)
  last_line = subprocess.Popen(u"tail -n 1 " + mal_data_path, shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT).stdout.readline()
  return int(last_line.split(",")[0])


def dump_animurecs_ratings(db, output_path):
  # Load credentials from a textfile.
  # only save one ratings copy per day.
  print "Exporting animurecs ratings..."
  if os.path.exists(output_path):
    os.unlink(output_path)
  export_ratings = db.query(u"""SELECT `user_id`, `anime_id`, `score` 
    INTO OUTFILE '""" + output_path + """'
    FIELDS TERMINATED BY ','
    LINES TERMINATED BY '\n'
    FROM (
      SELECT MAX(`id`) AS `id` FROM `anime_lists`
      GROUP BY `anime_id`
    ) `p` INNER JOIN `anime_lists` ON `anime_lists`.`id` = `p`.`id`
    WHERE `status` != 0 && `score` != 0
    ORDER BY `user_id` ASC, `status` ASC, `score` DESC""")

if __name__ == '__main__':
  bot = animurecs_bot.animurecs('dump_ratings', animurecs_modules, config_file='config.txt')

  # change working directory to scripts directory.
  current_date = unicode(datetime.datetime.now(tz=pytz.timezone(bot.config['timezone'])).strftime('%m-%d-%y'))
  os.chdir(bot.config['scripts_path'])

  # merge animurecs ratings with the MAL dataset, keeping track of animurecs user_ids so we can extract user features later.
  # find max user_id in the MAL dataset.
  # this assumes dataset is sorted by user_id ascending!
  print "Finding maximum MAL user_id..."
  mal_max_user_id = get_max_mal_user_id(bot.config['scripts_path'])

  # dump animurecs data.
  animurecs_data_filename = u"userData-" + current_date + u".txt"
  animurecs_data_path = os.path.join(bot.config['scripts_path'], u"data", animurecs_data_filename)
  print "Exporting animurecs ratings..."
  dump_animurecs_ratings(animurecs_data_path)

  # read the dumped data file.
  animurecs_ratings = {}
  with open(animurecs_data_path, 'r') as animurecs_ratings_file:
    for line in animurecs_ratings_file:
      split_line = line.strip().split(",")
      user_id = int(split_line[0])
      if user_id not in animurecs_ratings:
        animurecs_ratings[user_id] = [split_line[1:]]
      else:
        animurecs_ratings[user_id].append(split_line[1:])

  # assign each animurecs user a user_id higher than the max MAL user_id.
  print "Converting animurecs user_ids..."
  animurecs_to_mal = {}
  mal_to_animurecs = {}
  new_user_id = mal_max_user_id + 1
  for user_id in animurecs_ratings.keys():
    animurecs_to_mal[user_id] = new_user_id
    mal_to_animurecs[new_user_id] = user_id
    new_user_id += 1

  # append animurecs ratings to the MAL dataset.
  print "Appending animurecs ratings to MAL dataset..."
  merged_filename = u"mergedData-" + current_date + u".txt"
  merged_path = os.path.join(bot.config['scripts_path'], u"data", merged_filename)
  if os.path.exists(merged_path):
    os.unlink(merged_path)
  try:
    shutil.copy(mal_data_path, merged_path)
  except IOError, e:
    print "Could not copy MAL data (" + mal_data_path + ") to merged data path (" + merged_path + ")!"
    exit()
  with open(merged_path, "a") as mergedFile:
    mergedFile.write("\n")
    for user_id in animurecs_ratings.keys():
      for rating in animurecs_ratings[user_id]:
        mergedFile.write(",".join([str(animurecs_to_mal[user_id])] + rating) + "\n")

  # run SVD.
  print "Running SVD..."
  calcSVD = subprocess.Popen(["./malSVD", "--train", merged_path, "--output", u"merged_svd.txt"])
  calcSVD.wait()

  # extract anime features and animurecs user features from SVD output.
  # dump into recsServer input file.
  print "Extracting relevant features and writing to output file..."
  with open(os.path.join(bot.config['scripts_path'], "merged_svd.txt"), "r") as merged_svd:
    with open(os.path.join(bot.config['scripts_path'], u"data", "user_svd.txt"), "w") as user_svd:
      # copy global average.
      user_svd.write(merged_svd.readline())

      # loop over anime features, copying them over.
      for line in merged_svd:
        if line.startswith("---"):
          print "Found anime/user break line."
          break
        user_svd.write(line)

      user_svd.write("---\n")

      # copy user features only if user_id matches an animurecs user.
      for line in merged_svd:
        split_line = line.strip().split(",")
        if len(line) < 3:
          continue
        mal_user_id = int(split_line[0])
        if mal_user_id in mal_to_animurecs:
          animurecs_user_id = mal_to_animurecs[mal_user_id]
          user_svd.write(",".join([str(animurecs_user_id)]+split_line[1:]) + "\n")

  # trigger SVD reload.
  print "Reloading recommender SVD..."
  reload_svd = urllib2.urlopen("http://" + bot.config['RECS']['host'] + ":" + bot.config['RECS']['port'] + "/svd/reload")
  reload_svd.read()

  # clean up.
  # print "Cleaning up..."
  # os.unlink(animurecs_data_path)
  # os.unlink(merged_path)
  # os.unlink(os.path.join(bot.config['scripts_path'], u"data", "merged_svd.txt"))

  print "Done!"