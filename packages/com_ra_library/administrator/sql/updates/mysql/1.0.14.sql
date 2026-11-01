-- The 'Calendar ICS download' display option is now 'Add to Calendar button'
-- and its stored value changed from future_icsdownload to future_addtocalendar.

UPDATE `#__ra_library_displays` SET `displayoption` = 'future_addtocalendar' WHERE `displayoption` = 'future_icsdownload';
