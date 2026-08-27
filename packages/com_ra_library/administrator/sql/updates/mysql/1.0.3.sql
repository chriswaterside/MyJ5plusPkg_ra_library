-- Past Walks feature (Phase 1: schema + admin CRUD)
-- Generated for Joomla 5/6

CREATE TABLE IF NOT EXISTS `#__ra_library_past_walks` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`walks_manager_id` VARCHAR(50) NULL DEFAULT NULL,
`walk_date` DATE NULL DEFAULT NULL,
`title` VARCHAR(255) NOT NULL DEFAULT "",
`walk_leader` VARCHAR(255) NULL DEFAULT "",
`description` MEDIUMTEXT NULL,
`distance_km` DECIMAL(6,2) NULL DEFAULT NULL,
`national_grade` VARCHAR(50) NULL DEFAULT "",
`gpx_path` VARCHAR(500) NULL DEFAULT "",
`catid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`start_latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`start_longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`start_grid_reference` VARCHAR(20) NULL DEFAULT "",
`state` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED,
`checked_out_time` DATETIME NULL DEFAULT NULL,
`created` DATETIME NULL DEFAULT NULL,
`created_by` INT(11) NULL DEFAULT 0,
`modified` DATETIME NULL DEFAULT NULL,
`modified_by` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,UNIQUE KEY `idx_walks_manager_id` (`walks_manager_id`)
,KEY `idx_state` (`state`)
,KEY `idx_walk_date` (`walk_date`)
,KEY `idx_catid` (`catid`)
,KEY `idx_checked_out` (`checked_out`)
,KEY `idx_created_by` (`created_by`)
,KEY `idx_modified_by` (`modified_by`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `content_history_options`)
SELECT * FROM ( SELECT 'Past walk','com_ra_library.pastwalk','{"special":{"dbtable":"#__ra_library_past_walks","key":"id","type":"PastwalkTable","prefix":"Ramblers\\Component\\Ra_library\\Administrator\\Table\\"}}', CASE
                                    WHEN 'rules' is null THEN ''
                                    ELSE ''
                                    END as rules, CASE
                                    WHEN 'field_mappings' is null THEN ''
                                    ELSE ''
                                    END as field_mappings, '{"formFile":"administrator\/components\/com_ra_library\/forms\/pastwalk.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time"], "convertToInt":[], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}]}') AS tmp
WHERE NOT EXISTS (
	SELECT type_alias FROM `#__content_types` WHERE (`type_alias` = 'com_ra_library.pastwalk')
) LIMIT 1;
