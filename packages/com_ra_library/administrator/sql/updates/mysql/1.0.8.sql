-- Replaces the single route_guide editor field with a repeatable list of
-- route points - each with a short title, an HTML description (for a future
-- map popup), and a location (grid reference and/or lat/long). Route-only,
-- since only Routes ever had route_guide.

CREATE TABLE IF NOT EXISTS `#__ra_library_route_points` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`title` VARCHAR(255) NOT NULL DEFAULT "",
`description` MEDIUMTEXT NULL,
`grid_reference` VARCHAR(20) NULL DEFAULT "",
`latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`ordering` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Carry over anything already typed into the old single route_guide field
-- as a first, unlocated point, rather than silently losing it.
INSERT INTO `#__ra_library_route_points` (`record_id`, `title`, `description`, `ordering`)
SELECT `id`, 'Route Guide', `route_guide`, 1
FROM `#__ra_library_routes`
WHERE `record_type` = 'route'
AND `route_guide` IS NOT NULL
AND `route_guide` != '';
