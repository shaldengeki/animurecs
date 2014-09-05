# !/usr/bin/env python
''' 
  animurecs_bot - Runs Animurecs delayed jobs.
  Author - Shal Dengeki <shaldengeki@gmail.com>
  USAGE - python animurecs_bot.py start|stop|restart
  REQUIRES - update_daemon, yapdi

  python animurecs_bot.py start starts animurecs_bot in daemon mode 
  if there is no instance already running.

  python animurecs_bot.py stop kills any running instance.

  python animurecs_bot.py restart kills any running instance and
  starts an instance.
'''

import argparse
import pytz
import datetime
import syslog
import time

import update_daemon
import animurecs_modules
import yapdi

class animurecs(update_daemon.Daemon):
  def preload(self):
    lastRunTime = self.dbs['animurecs'].table('users').fields('last_import').where('mal_username IS NOT NULL').order('last_import ASC').limit(1).firstValue()
    if lastRunTime:
      self.info['last_run_time'] = pytz.timezone('Europe/Paris').localize(lastRunTime)
    else:
      # mal profile update requests queue is empty.
      self.info['last_run_time'] = datetime.datetime.now(tz=pytz.utc)

if __name__ == "__main__":
  parser = argparse.ArgumentParser()
  parser.add_argument("action", choices=["start", "stop", "restart"], 
                      help="start, stop, or restart the daemon")
  parser.add_argument("--config", default='./config.txt',
                      help="path to a config textfile")
  args = parser.parse_args()

  if args.action == "start":
    daemon = yapdi.Daemon(pidfile='/var/run/animurecs_bot.pid')

    # Check whether an instance is already running
    if daemon.status():
      print("An instance is already running.")
      exit()
    retcode = daemon.daemonize()
    # Execute if daemonization was successful else exit
    if retcode == yapdi.OPERATION_SUCCESSFUL:
      bot = animurecs('animurecs', animurecs_modules, config_file=args.config)
      bot.run()
    else:
      syslog.syslog(syslog.LOG_CRIT, 'Daemonization failed')

  elif args.action == "stop":
    daemon = yapdi.Daemon(pidfile='/var/run/animurecs_bot.pid')

    # Check whether no instance is running
    if not daemon.status():
      print("No instance running.")
      exit()
    retcode = daemon.kill()
    if retcode == yapdi.OPERATION_FAILED:
      print('Trying to stop running instance failed')

  elif args.action == "restart":
    daemon = yapdi.Daemon(pidfile='/var/run/animurecs_bot.pid')
    retcode = daemon.restart()
    # Execute if daemonization was successful else exit
    if retcode == yapdi.OPERATION_SUCCESSFUL:
      bot = animurecs('animurecs', animurecs_modules, config_file=args.config)
      bot.run()
    else:
      print('Daemonization failed')