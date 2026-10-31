-- SHA-1 of each gallery photo's ORIGINAL uploaded bytes (the original itself
-- is never kept - only resized copies - so this is the only way left to
-- recognise the same file being offered again). Used by "Add several photos
-- at once" (ImageGalleryHelper::addBulkUploads) to skip photos already in
-- the same record's gallery. Photos stored before this column existed have
-- '' and can't be matched.

ALTER TABLE `#__ra_library_images`
	ADD COLUMN `file_hash` CHAR(40) NOT NULL DEFAULT '' AFTER `large_path`,
	ADD KEY `idx_record_hash` (`record_id`, `file_hash`);
