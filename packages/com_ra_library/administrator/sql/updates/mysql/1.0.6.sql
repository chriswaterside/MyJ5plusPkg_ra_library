-- Flag walks brought in via "Import from Walks Manager" so they're easy to
-- find and review/enrich afterwards - published straight away (so they show
-- on the site immediately) but visibly marked until someone has actually
-- looked at them. Cleared automatically the next time the record is
-- manually edited and saved (that IS the review), rather than needing a
-- separate "mark reviewed" step for every walk.

ALTER TABLE `#__ra_library_routes`
	ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `walks_manager_id`;

CREATE INDEX `idx_needs_review` ON `#__ra_library_routes` (`needs_review`);
