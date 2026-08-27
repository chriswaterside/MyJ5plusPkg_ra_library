-- Mail spool table for com_ra_library
-- Generated for Joomla 5/6

CREATE TABLE IF NOT EXISTS `#__ra_mail_spool` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `created`     datetime NOT NULL,
  `status`      tinyint(1) NOT NULL DEFAULT 0,
  `attempts`    tinyint(1) NOT NULL DEFAULT 0,
  `sent`        datetime NULL,
  `component`   varchar(100) NOT NULL DEFAULT '',
  `to_email`    text NOT NULL,
  `cc`          text NULL,
  `bcc`         text NULL,
  `reply_to`    text NULL,
  `subject`     varchar(255) NOT NULL,
  `body`        longtext NULL,
  `htmlbody`    longtext NULL,
  `attachments` text NULL,
  `error`       text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_component` (`component`),
  KEY `idx_sent` (`sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
