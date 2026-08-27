-- Lets one GPX file per record be marked as the "main" route (same idea as
-- the images table's featured flag) - used by {gpx_map} to know which file
-- to embed inline on the item page without any configuration needed for
-- the common case of a record with only a single GPX file. document rows
-- share this table but never set the flag.

ALTER TABLE `#__ra_library_attachments`
	ADD COLUMN `featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ordering`;

CREATE INDEX `idx_featured` ON `#__ra_library_attachments` (`featured`);
