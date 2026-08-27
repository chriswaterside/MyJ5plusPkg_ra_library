-- Image gallery for Past Walks and Routes. One child table shared by both
-- record types, same pattern as the parent ra_library_routes table -
-- distinguished by record_id (FK to ra_library_routes.id), not a separate
-- extension/record_type column, since an image always belongs to exactly
-- one parent record regardless of that record's own type.

CREATE TABLE IF NOT EXISTS `#__ra_library_images` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`caption` VARCHAR(255) NULL DEFAULT "",
`description` TEXT NULL,
`thumbnail_path` VARCHAR(500) NULL DEFAULT "",
`large_path` VARCHAR(500) NULL DEFAULT "",
`grid_reference` VARCHAR(20) NULL DEFAULT "",
`latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`featured` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
,KEY `idx_featured` (`featured`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;
