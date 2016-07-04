-- phpMyAdmin SQL Dump
-- version 2.11.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 19, 2008 at 11:57 PM
-- Server version: 4.1.22
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `blacktow_dbbetarap`
--

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_adjustments`
--

DROP TABLE IF EXISTS `eqdkp_adjustments`;
CREATE TABLE `eqdkp_adjustments` (
  `adjustment_id` mediumint(8) unsigned NOT NULL auto_increment,
  `adjustment_value` double(11,2) NOT NULL default '0.00',
  `adjustment_event` varchar(255) default NULL,
  `adjustment_date` int(11) NOT NULL default '0',
  `member_name` varchar(30) default NULL,
  `adjustment_reason` varchar(255) default NULL,
  `adjustment_added_by` varchar(30) NOT NULL default '',
  `adjustment_updated_by` varchar(30) default NULL,
  `adjustment_group_key` varchar(32) default NULL,
  PRIMARY KEY  (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_armor_types`
--

DROP TABLE IF EXISTS `eqdkp_armor_types`;
CREATE TABLE `eqdkp_armor_types` (
  `armor_type_id` smallint(3) unsigned NOT NULL default '0',
  `armor_type_name` varchar(50) NOT NULL default '',
  `armor_type_key` varchar(30) NOT NULL default '',
  PRIMARY KEY  (`armor_type_id`),
  UNIQUE KEY `armor_type_id` (`armor_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_auth_options`
--

DROP TABLE IF EXISTS `eqdkp_auth_options`;
CREATE TABLE `eqdkp_auth_options` (
  `auth_id` smallint(3) unsigned NOT NULL default '0',
  `auth_value` varchar(25) NOT NULL default '',
  `auth_default` enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (`auth_id`),
  KEY `auth_value` (`auth_value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_auth_users`
--

DROP TABLE IF EXISTS `eqdkp_auth_users`;
CREATE TABLE `eqdkp_auth_users` (
  `user_id` smallint(5) unsigned NOT NULL default '0',
  `auth_id` smallint(3) unsigned NOT NULL default '0',
  `auth_setting` enum('N','Y') NOT NULL default 'N',
  UNIQUE KEY `user_auth` (`user_id`,`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_classes`
--

DROP TABLE IF EXISTS `eqdkp_classes`;
CREATE TABLE `eqdkp_classes` (
  `class_id` smallint(3) unsigned NOT NULL default '0',
  `class_name` varchar(50) NOT NULL default '',
  `class_key` varchar(30) NOT NULL default '',
  `class_min_level` smallint(3) NOT NULL default '0',
  `class_max_level` smallint(3) NOT NULL default '999',
  `class_armor_type` varchar(50) NOT NULL default '',
  `class_hide` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_class_armor`
--

DROP TABLE IF EXISTS `eqdkp_class_armor`;
CREATE TABLE `eqdkp_class_armor` (
  `class_id` smallint(3) unsigned NOT NULL default '0',
  `armor_type_id` smallint(3) unsigned NOT NULL default '0',
  `armor_min_level` smallint(3) NOT NULL default '0',
  `armor_max_level` smallint(3) default NULL,
  PRIMARY KEY  (`class_id`,`armor_type_id`),
  KEY `classes` (`class_id`),
  KEY `armor_types` (`armor_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_config`
--

DROP TABLE IF EXISTS `eqdkp_config`;
CREATE TABLE `eqdkp_config` (
  `config_name` varchar(255) NOT NULL default '',
  `config_value` varchar(255) default NULL,
  PRIMARY KEY  (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_ctrt_aliases`
--

CREATE TABLE IF NOT EXISTS `eqdkp_ctrt_aliases` (
  `alias_id` smallint(5) unsigned NOT NULL auto_increment,
  `alias_member_id` mediumint(9) NOT NULL default '0',
  `alias_name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`alias_id`),
  UNIQUE KEY `alias_name` (`alias_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_events`
--

DROP TABLE IF EXISTS `eqdkp_events`;
CREATE TABLE `eqdkp_events` (
  `event_id` smallint(5) unsigned NOT NULL auto_increment,
  `event_name` varchar(255) default NULL,
  `event_value` double(11,2) NOT NULL default '0.00',
  `event_added_by` varchar(30) NOT NULL default '',
  `event_updated_by` varchar(30) default NULL,
  PRIMARY KEY  (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_factions`
--

DROP TABLE IF EXISTS `eqdkp_factions`;
CREATE TABLE `eqdkp_factions` (
  `faction_id` smallint(3) unsigned NOT NULL default '0',
  `faction_name` varchar(50) NOT NULL default '',
  `faction_key` varchar(30) NOT NULL default '',
  `faction_hide` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`faction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_items`
--

DROP TABLE IF EXISTS `eqdkp_items`;
CREATE TABLE `eqdkp_items` (
  `item_id` mediumint(8) unsigned NOT NULL auto_increment,
  `item_name` varchar(255) default NULL,
  `item_buyer` varchar(50) default NULL,
  `raid_id` int(10) unsigned NOT NULL default '0',
  `item_value` double(11,2) NOT NULL default '0.00',
  `item_date` int(11) NOT NULL default '0',
  `item_added_by` varchar(30) NOT NULL default '',
  `item_updated_by` varchar(30) default NULL,
  `item_group_key` varchar(32) default NULL,
  `item_ctrt_wowitemid` int(10) unsigned default NULL,
  PRIMARY KEY  (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_item_cache`
--

CREATE TABLE IF NOT EXISTS `eqdkp_item_cache` (
  `item_name` varchar(100) NOT NULL default '',
  `item_id` varchar(100) default '0',
  `item_lang` char(2) default '',
  `item_link` varchar(100) default NULL,
  `item_color` varchar(20) NOT NULL default '',
  `item_icon` varchar(50) NOT NULL default '',
  `item_html` text NOT NULL,
  UNIQUE KEY `name` (`item_name`),
  FULLTEXT KEY `data` (`item_html`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_logs`
--

DROP TABLE IF EXISTS `eqdkp_logs`;
CREATE TABLE `eqdkp_logs` (
  `log_id` int(11) unsigned NOT NULL auto_increment,
  `log_date` int(11) NOT NULL default '0',
  `log_type` varchar(255) NOT NULL default '',
  `log_action` text NOT NULL,
  `log_ipaddress` varchar(15) NOT NULL default '',
  `log_sid` varchar(32) NOT NULL default '',
  `log_result` varchar(255) NOT NULL default '',
  `admin_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`log_id`),
  KEY `admin_id` (`admin_id`),
  KEY `log_type` (`log_type`),
  KEY `log_ipaddress` (`log_ipaddress`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_members`
--

DROP TABLE IF EXISTS `eqdkp_members`;
CREATE TABLE `eqdkp_members` (
  `member_id` smallint(5) unsigned NOT NULL auto_increment,
  `member_name` varchar(30) NOT NULL default '',
  `member_earned` double(11,2) NOT NULL default '0.00',
  `member_spent` double(11,2) NOT NULL default '0.00',
  `member_adjustment` double(11,2) NOT NULL default '0.00',
  `member_status` enum('0','1') NOT NULL default '1',
  `member_firstraid` int(11) NOT NULL default '0',
  `member_lastraid` int(11) NOT NULL default '0',
  `member_raidcount` int(11) NOT NULL default '0',
  `member_level` tinyint(2) default NULL,
  `member_race_id` smallint(3) unsigned NOT NULL default '0',
  `member_class_id` smallint(3) unsigned NOT NULL default '0',
  `member_rank_id` smallint(3) NOT NULL default '0',
  `member_main_id` smallint(5) default NULL,
  `member_ctprofile` varchar(8) default NULL,
  `member_gender` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`member_id`),
  UNIQUE KEY `member_name` (`member_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_member_ranks`
--

DROP TABLE IF EXISTS `eqdkp_member_ranks`;
CREATE TABLE `eqdkp_member_ranks` (
  `rank_id` smallint(5) unsigned NOT NULL default '0',
  `rank_name` varchar(50) NOT NULL default '',
  `rank_hide` enum('0','1') NOT NULL default '0',
  `rank_prefix` varchar(75) NOT NULL default '',
  `rank_suffix` varchar(75) NOT NULL default '',
  UNIQUE KEY `rank_id` (`rank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_member_user`
--

DROP TABLE IF EXISTS `eqdkp_member_user`;
CREATE TABLE `eqdkp_member_user` (
  `member_id` smallint(5) unsigned NOT NULL default '0',
  `user_id` smallint(5) unsigned NOT NULL default '0',
  KEY `member_id` (`member_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_news`
--

DROP TABLE IF EXISTS `eqdkp_news`;
CREATE TABLE `eqdkp_news` (
  `news_id` smallint(5) unsigned NOT NULL auto_increment,
  `news_headline` varchar(255) NOT NULL default '',
  `news_message` text NOT NULL,
  `news_date` int(11) NOT NULL default '0',
  `user_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`news_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_plugins`
--

DROP TABLE IF EXISTS `eqdkp_plugins`;
CREATE TABLE `eqdkp_plugins` (
  `plugin_id` smallint(2) unsigned NOT NULL auto_increment,
  `plugin_name` varchar(50) NOT NULL default '',
  `plugin_code` varchar(20) NOT NULL default '',
  `plugin_installed` enum('0','1') NOT NULL default '0',
  `plugin_path` varchar(255) NOT NULL default '',
  `plugin_contact` varchar(100) default NULL,
  `plugin_version` varchar(7) NOT NULL default '',
  PRIMARY KEY  (`plugin_id`),
  KEY `plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_races`
--

DROP TABLE IF EXISTS `eqdkp_races`;
CREATE TABLE `eqdkp_races` (
  `race_id` smallint(3) unsigned NOT NULL default '0',
  `race_name` varchar(50) NOT NULL default '',
  `race_key` varchar(30) NOT NULL default '',
  `race_faction_id` smallint(3) NOT NULL default '0',
  `race_hide` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`race_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_raidgroups_raidgroups`
--

CREATE TABLE IF NOT EXISTS `eqdkp_raidgroups_raidgroups` (
  `raidgroup_id` mediumint(8) NOT NULL auto_increment,
  `raidgroup_name` varchar(255) NOT NULL default '',
  `raidgroup_raid_ids` varchar(255) NOT NULL default '',
  `raidgroup_display` enum('Y','N') NOT NULL default 'Y',
  `raidgroup_display_order` smallint(6) NOT NULL default '0',
  `raidgroup_added_by` varchar(30) NOT NULL default '',
  `raidgroup_updated_by` varchar(30) default NULL,
  PRIMARY KEY  (`raidgroup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_raids`
--

DROP TABLE IF EXISTS `eqdkp_raids`;
CREATE TABLE `eqdkp_raids` (
  `raid_id` mediumint(8) unsigned NOT NULL auto_increment,
  `raid_name` varchar(255) default NULL,
  `raid_date` int(11) NOT NULL default '0',
  `raid_note` varchar(255) default NULL,
  `raid_value` double(11,2) NOT NULL default '0.00',
  `raid_added_by` varchar(30) NOT NULL default '',
  `raid_updated_by` varchar(30) default NULL,
  PRIMARY KEY  (`raid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_raid_attendees`
--

DROP TABLE IF EXISTS `eqdkp_raid_attendees`;
CREATE TABLE `eqdkp_raid_attendees` (
  `raid_id` mediumint(8) unsigned NOT NULL default '0',
  `member_name` varchar(30) NOT NULL default '',
  UNIQUE KEY `raid_member` (`raid_id`,`member_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_sessions`
--

DROP TABLE IF EXISTS `eqdkp_sessions`;
CREATE TABLE `eqdkp_sessions` (
  `session_id` varchar(32) NOT NULL default '',
  `session_user_id` smallint(5) NOT NULL default '-1',
  `session_last_visit` int(11) NOT NULL default '0',
  `session_start` int(11) NOT NULL default '0',
  `session_current` int(11) NOT NULL default '0',
  `session_page` varchar(100) NOT NULL default '0',
  `session_ip` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_styles`
--

DROP TABLE IF EXISTS `eqdkp_styles`;
CREATE TABLE `eqdkp_styles` (
  `style_id` smallint(5) unsigned NOT NULL auto_increment,
  `style_name` varchar(100) NOT NULL default '',
  `template_path` varchar(30) NOT NULL default 'default',
  `body_background` varchar(6) default NULL,
  `body_link` varchar(6) default NULL,
  `body_link_style` varchar(30) default NULL,
  `body_hlink` varchar(6) default NULL,
  `body_hlink_style` varchar(30) default NULL,
  `header_link` varchar(6) default NULL,
  `header_link_style` varchar(30) default NULL,
  `header_hlink` varchar(6) default NULL,
  `header_hlink_style` varchar(30) default NULL,
  `tr_color1` varchar(6) default NULL,
  `tr_color2` varchar(6) default NULL,
  `th_color1` varchar(6) default NULL,
  `fontface1` varchar(60) default NULL,
  `fontface2` varchar(60) default NULL,
  `fontface3` varchar(60) default NULL,
  `fontsize1` tinyint(4) default NULL,
  `fontsize2` tinyint(4) default NULL,
  `fontsize3` tinyint(4) default NULL,
  `fontcolor1` varchar(6) default NULL,
  `fontcolor2` varchar(6) default NULL,
  `fontcolor3` varchar(6) default NULL,
  `fontcolor_neg` varchar(6) default NULL,
  `fontcolor_pos` varchar(6) default NULL,
  `table_border_width` tinyint(3) default NULL,
  `table_border_color` varchar(6) default NULL,
  `table_border_style` varchar(30) default NULL,
  `input_color` varchar(6) default NULL,
  `input_border_width` tinyint(3) default NULL,
  `input_border_color` varchar(6) default NULL,
  `input_border_style` varchar(30) default NULL,
  PRIMARY KEY  (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_style_config`
--

DROP TABLE IF EXISTS `eqdkp_style_config`;
CREATE TABLE `eqdkp_style_config` (
  `style_id` smallint(5) unsigned NOT NULL default '0',
  `attendees_columns` enum('1','2','3','4','5','6','7','8','9','10') NOT NULL default '6',
  `date_notime_long` varchar(10) NOT NULL default 'F j, Y',
  `date_notime_short` varchar(10) NOT NULL default 'm/d/y',
  `date_time` varchar(20) NOT NULL default 'm/d/y h:ia T',
  `logo_path` varchar(255) NOT NULL default 'logo.gif',
  PRIMARY KEY  (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eqdkp_users`
--

DROP TABLE IF EXISTS `eqdkp_users`;
CREATE TABLE `eqdkp_users` (
  `user_id` smallint(5) unsigned NOT NULL auto_increment,
  `username` varchar(30) NOT NULL default '',
  `user_password` varchar(40) NOT NULL default '',
  `user_salt` varchar(40) NOT NULL default '',
  `user_email` varchar(100) default NULL,
  `user_alimit` smallint(4) NOT NULL default '100',
  `user_elimit` smallint(4) NOT NULL default '100',
  `user_ilimit` smallint(4) NOT NULL default '100',
  `user_nlimit` smallint(2) NOT NULL default '10',
  `user_rlimit` smallint(4) NOT NULL default '100',
  `user_style` tinyint(4) default NULL,
  `user_lang` varchar(255) default NULL,
  `user_key` varchar(32) default NULL,
  `user_lastvisit` int(11) NOT NULL default '0',
  `user_lastpage` varchar(100) default '',
  `user_active` enum('0','1') NOT NULL default '1',
  `user_newpassword` varchar(40) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;