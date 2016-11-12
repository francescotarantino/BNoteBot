CREATE TABLE IF NOT EXISTS `BNoteBot_memo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `memo` text NOT NULL,
  `timestamp` int(11) NOT NULL,
  `type` text,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `memo` (`memo`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4146 ;

CREATE TABLE IF NOT EXISTS `BNoteBot_reminder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `memoid` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=524 ;

CREATE TABLE IF NOT EXISTS `BNoteBot_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` text,
  `username` text,
  `name` text,
  `status` text,
  `lang` text,
  `notes` text,
  `invertmemodata` int(1) NOT NULL DEFAULT '0',
  `timezone` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2170 ;
