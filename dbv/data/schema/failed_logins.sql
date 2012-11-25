CREATE TABLE `failed_logins` (
  `ip` varchar(15) COLLATE utf8_bin NOT NULL,
  `time` datetime NOT NULL,
  `username` text COLLATE utf8_bin NOT NULL,
  `password` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`ip`,`time`),
  KEY `username` (`username`(30)),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin