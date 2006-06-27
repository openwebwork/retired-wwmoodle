# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display

DROP TABLE IF EXISTS `prefix_wwmoodleset`;

CREATE TABLE `prefix_wwassignment` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`course` int(10) unsigned NOT NULL,
	`set_id` text NOT NULL,
	`description` text NOT NULL,
	`gradingmethod` int(3) unsigned NOT NULL,
	`timemodified` int(10) unsigned NOT NULL,

	PRIMARY KEY  (`id`),
	KEY (`course`)

);



# Insert sane defaults for config options:

INSERT INTO `prefix_config` (`name`, `value`) VALUES ('wwmoodleset_webwork_url', '/webwork2');

INSERT INTO `prefix_config` (`name`, `value`) VALUES ('wwmoodleset_iframe_width', '90%');

INSERT INTO `prefix_config` (`name`, `value`) VALUES ('wwmoodleset_iframe_height', '500px');


# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display
DROP TABLE IF EXISTS `prefix_wwmoodle`;

CREATE TABLE `prefix_wwassignment_bridge` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`allowedRecipients` text NOT NULL,
	`course` int(10) unsigned NOT NULL,
	`coursename` varchar(15) NOT NULL,
	`timemodified` int(10) unsigned NOT NULL,

	PRIMARY KEY  (`id`),
	KEY (`course`)

);


# Insert sane default config options:

INSERT INTO `prefix_config` (`name`, `value`) VALUES ('wwmoodle_webwork_courses', '/opt/webwork2/courses/');
