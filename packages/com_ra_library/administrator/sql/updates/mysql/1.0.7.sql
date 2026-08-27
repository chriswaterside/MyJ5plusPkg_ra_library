-- GPX tracks and PDF documents attached to a Past Walk or Route. One shared
-- table (same trick as the images table) - record_id alone is enough to
-- identify the parent row since ids don't collide between pastwalk/route
-- rows in the shared #__ra_library_routes table. attachment_type ('gpx' or
-- 'document') is what tells the two upload sections apart.

CREATE TABLE IF NOT EXISTS `#__ra_library_attachments` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`attachment_type` VARCHAR(20) NOT NULL DEFAULT 'document',
`title` VARCHAR(255) NOT NULL DEFAULT "",
`file_path` VARCHAR(500) NOT NULL DEFAULT "",
`file_size` INT(11) UNSIGNED NULL DEFAULT NULL,
`ordering` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
,KEY `idx_attachment_type` (`attachment_type`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;
