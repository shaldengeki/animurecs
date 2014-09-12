#!/usr/bin/python
# -*- coding: utf-8 -*-

import animurecs_bot, animurecs_modules
import datetime,pytz
import decimal

if __name__ == '__main__':
  bot = animurecs_bot.animurecs('test', animurecs_modules, config_file='./config.txt')

  user_lists = {}
  for row in bot.dbs['animurecs'].table('anime_lists').fields('user_id', 'anime_id', 'time', 'score').order('user_id ASC, anime_id ASC, time DESC').query():
    if row['user_id'] not in user_lists:
      user_lists[row['user_id']] = {}
    if row['anime_id'] not in user_lists[row['user_id']]:
      user_lists[row['user_id']][row['anime_id']] = row
    elif row['time'] > user_lists[row['user_id']][row['anime_id']]['time']:
      user_lists[row['user_id']][row['anime_id']] = row

  for user in user_lists:
    for anime_id in user_lists[user].keys():
      if user_lists[user][anime_id]['score'] == decimal.Decimal(0.00):
        del user_lists[user][anime_id]

  all_ratings = [user_lists[u][a]['score'] for u in user_lists for a in user_lists[u]]
  all_rating_count = len(all_ratings)
  all_rating_mean = float(sum(all_ratings)) * 1.0 / all_rating_count

  all_rating_square_sum = 0
  for u in user_lists:
    for a in user_lists[u]:
      all_rating_square_sum += pow(float(user_lists[u][a]['score']) - all_rating_mean, 2)

  # this is the variance of all the individual ratings.
  all_rating_variance = all_rating_square_sum / all_rating_count

  anime_lists = {}
  for user in user_lists:
    for anime_id in user_lists[user].keys():
      if anime_id not in anime_lists:
        anime_lists[anime_id] = []
      anime_lists[anime_id].append(user_lists[user][anime_id]['score'])

  anime_means = {}
  for anime_id in anime_lists:
    anime_means[anime_id] = float(sum(anime_lists[anime_id])) / len(anime_lists[anime_id])

  all_anime_means = [anime_means[a] for a in anime_means]
  all_anime_count = len(all_anime_means)
  all_anime_mean = sum(all_anime_means) / all_anime_count

  all_anime_square_sum = 0
  for a in anime_means:
    all_anime_square_sum += pow(anime_means[a] - all_anime_mean, 2)

  # this is the variance of the anime's mean ratings.
  all_anime_mean_variance = all_anime_square_sum / all_anime_count

  # this is the weight of the global prior when calculating expected true mean of an anime
  K = all_rating_variance / all_anime_mean_variance

  # calculates the "true mean" of an anime, adding a prior expectation weighted by prior_weight
  def true_mean(anime_lists, prior_value, prior_weight, anime_id):
    return (prior_value * prior_weight + float(sum(anime_lists[anime_id]))) / (prior_weight + len(anime_lists[anime_id]))

  for anime_id in sorted(anime_lists.keys()):
    print anime_id,":",true_mean(anime_lists, all_anime_mean, K, anime_id)