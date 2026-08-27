-- Per-item Intro/More layout overrides - same idea as the site-wide
-- pastwalk_item_template_intro/_more and route_item_template_intro/_more
-- Options fields (Components > Ra_library > Options), but for one record
-- only. Blank (the default for every existing row) means "use the
-- site-wide setting" - see ItemRenderer::resolveTemplate().

ALTER TABLE `#__ra_library_routes`
	ADD COLUMN `template_intro_override` MEDIUMTEXT NULL AFTER `route_guide`,
	ADD COLUMN `template_more_override` MEDIUMTEXT NULL AFTER `template_intro_override`;
