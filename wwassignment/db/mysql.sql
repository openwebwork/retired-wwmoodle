# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display

DROP TABLE IF EXISTS `prefix_wwassignment`;

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

# using IGNORE to throw away duplicate key errors when a config item already
# exists (i.e. from removal and subsequent reinstallation). this preserves
# existing values. another way to go would be to use REPLACE to overwrite
# existing values. this would ensure that at installation time, the defaults
# would be up-to-date.

INSERT IGNORE INTO `prefix_config` (`name`, `value`) VALUES ('wwassignment_webworkurl', '/webwork2');

INSERT IGNORE INTO `prefix_config` (`name`, `value`) VALUES ('wwassignment_iframewidth', '90%');

INSERT IGNORE INTO `prefix_config` (`name`, `value`) VALUES ('wwassignment_iframeheight', '500px');


# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display
DROP TABLE IF EXISTS `prefix_wwassignment_bridge`;

CREATE TABLE `prefix_wwassignment_bridge` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`course` int(10) unsigned NOT NULL,
	`coursename` varchar(15) NOT NULL,
	`timemodified` int(10) unsigned NOT NULL,

	PRIMARY KEY  (`id`),
	KEY (`course`)

);


# Insert sane default config options:

# (same IGNORE option used here, see above)

INSERT IGNORE INTO `prefix_config` (`name`, `value`) VALUES ('wwassignment_bridge_webworkcourses', '/opt/webwork2/courses/');
