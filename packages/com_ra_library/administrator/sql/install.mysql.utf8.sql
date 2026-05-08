CREATE TABLE IF NOT EXISTS `#__ra_email_log` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`datetime` DATETIME NULL  DEFAULT NULL ,
`to` VARCHAR(255)  NOT NULL ,
`title` VARCHAR(255)  NULL  DEFAULT "",
`replyto` VARCHAR(255)  NULL  DEFAULT "",
`sent` BOOLEAN NOT NULL  DEFAULT "0",
`message` VARCHAR(255)  NULL  DEFAULT "",
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `#__ra_email_log_datetime` ON `#__ra_email_log`(`datetime`);

CREATE INDEX `#__ra_email_log_sent` ON `#__ra_email_log`(`sent`);

CREATE TABLE IF NOT EXISTS `#__ra_library_displays` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`state` TINYINT(1)  NULL  DEFAULT 1,
`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`checked_out_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_by` INT(11)  NULL  DEFAULT 0,
`title` VARCHAR(255)  NOT NULL ,
`displayoption` VARCHAR(255)  DEFAULT "none" ,
`options` MEDIUMTEXT NULL  DEFAULT "",
PRIMARY KEY (`id`)
,KEY `idx_state` (`state`)
,KEY `idx_checked_out` (`checked_out`)
,KEY `idx_created_by` (`created_by`)
,KEY `idx_modified_by` (`modified_by`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;


INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `content_history_options`)
SELECT * FROM ( SELECT 'Library display','com_ra_library.librarydisplay','{"special":{"dbtable":"#__ra_library_displays","key":"id","type":"LibrarydisplayTable","prefix":"Ra_library\\Component\\Ra_library\\Administrator\\Table\\"}}', CASE 
                                    WHEN 'rules' is null THEN ''
                                    ELSE ''
                                    END as rules, CASE 
                                    WHEN 'field_mappings' is null THEN ''
                                    ELSE ''
                                    END as field_mappings, '{"formFile":"administrator\/components\/com_ra_library\/forms\/librarydisplay.xml", "hideFields":["checked_out","checked_out_time","params","language"], "ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time"], "convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"group_id","targetTable":"#__usergroups","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}]}') AS tmp
WHERE NOT EXISTS (
	SELECT type_alias FROM `#__content_types` WHERE (`type_alias` = 'com_ra_library.librarydisplay')
) LIMIT 1;

INSERT INTO `#__mail_templates` 
(`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) 
VALUES 
('com_ra_library.upload_notification', 'com_ra_library', '', 'COM_RA_LIBRARY_MAIL_UPLOAD_NOTIFICATION_SUBJECT', 'COM_RA_LIBRARY_MAIL_UPLOAD_NOTIFICATION_BODY', 'COM_RA_LIBRARY_MAIL_UPLOAD_NOTIFICATION_BODY_HTML', '', '{"tags":["sitename","filename","filepath","username"]}')
ON DUPLICATE KEY UPDATE 
`extension` = VALUES(`extension`);