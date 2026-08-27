-- Routes feature: the past-walks table becomes a shared table for both
-- Past Walks and the new Routes section, distinguished by a record_type
-- column. Physically renamed from ra_library_past_walks to ra_library_routes
-- per Chris's request, since "past walks" is no longer an accurate name for
-- a table that also holds routes with no date. All existing rows are
-- past walks, so the new column's DEFAULT backfills them as 'pastwalk'
-- automatically - no separate UPDATE needed for that.

RENAME TABLE `#__ra_library_past_walks` TO `#__ra_library_routes`;

ALTER TABLE `#__ra_library_routes`
	ADD COLUMN `record_type` VARCHAR(20) NOT NULL DEFAULT 'pastwalk' AFTER `id`,
	ADD COLUMN `route_guide` MEDIUMTEXT NULL AFTER `description`;

CREATE INDEX `idx_record_type` ON `#__ra_library_routes` (`record_type`);

-- Point the existing Past Walk content_types row at the renamed table.
UPDATE `#__content_types`
SET `table` = '{"special":{"dbtable":"#__ra_library_routes","key":"id","type":"PastwalkTable","prefix":"Ramblers\\Component\\Ra_library\\Administrator\\Table\\"}}'
WHERE `type_alias` = 'com_ra_library.pastwalk';

-- New content_types row for the Route form (same table, same Table class -
-- record_type is what tells them apart).
INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `content_history_options`)
SELECT * FROM ( SELECT 'Route','com_ra_library.route','{"special":{"dbtable":"#__ra_library_routes","key":"id","type":"PastwalkTable","prefix":"Ramblers\\Component\\Ra_library\\Administrator\\Table\\"}}', CASE
                                    WHEN 'rules' is null THEN ''
                                    ELSE ''
                                    END as rules, CASE
                                    WHEN 'field_mappings' is null THEN ''
                                    ELSE ''
                                    END as field_mappings, '{"formFile":"administrator\/components\/com_ra_library\/forms\/route.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time"], "convertToInt":[], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}]}') AS tmp
WHERE NOT EXISTS (
	SELECT type_alias FROM `#__content_types` WHERE (`type_alias` = 'com_ra_library.route')
) LIMIT 1;
