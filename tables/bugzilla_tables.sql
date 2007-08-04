create database if not exists bugs;

use bugs;

/* make sure the wineowner user has access to the bugs database */
grant all on bugs.* to wineowner;

drop table if exists versions;


/*
 * versions information
 */
create table versions (
       value tinytext,
       product_id smallint not null
);

INSERT INTO versions VALUES ('unspecified', 1 );
INSERT INTO versions VALUES ('20010112', 1 );
INSERT INTO versions VALUES ('20010216', 1 );
INSERT INTO versions VALUES ('20010305', 1 );
INSERT INTO versions VALUES ('20010326', 1 );
INSERT INTO versions VALUES ('20010418', 1 );
INSERT INTO versions VALUES ('20010510', 1 );
INSERT INTO versions VALUES ('20010629', 1 );
INSERT INTO versions VALUES ('20010824', 1 );
INSERT INTO versions VALUES ('20011004', 1 );
INSERT INTO versions VALUES ('20011108', 1 );
INSERT INTO versions VALUES ('20020228', 1 );
INSERT INTO versions VALUES ('20020310', 1 );
INSERT INTO versions VALUES ('20020411', 1 );
INSERT INTO versions VALUES ('20020509', 1 );
INSERT INTO versions VALUES ('20020605', 1 );
INSERT INTO versions VALUES ('20020710', 1 );
INSERT INTO versions VALUES ('20020804', 1 );
INSERT INTO versions VALUES ('20020904', 1 );
INSERT INTO versions VALUES ('20021007', 1 );
INSERT INTO versions VALUES ('20021031', 1 );
INSERT INTO versions VALUES ('20021125', 1 );
INSERT INTO versions VALUES ('20021219', 1 );
INSERT INTO versions VALUES ('20030115', 1 );
INSERT INTO versions VALUES ('20030219', 1 );
INSERT INTO versions VALUES ('20030318', 1 );
INSERT INTO versions VALUES ('20030408', 1 );
INSERT INTO versions VALUES ('20030508', 1 );
INSERT INTO versions VALUES ('20030618', 1 );
INSERT INTO versions VALUES ('20030709', 1 );
INSERT INTO versions VALUES ('20030813', 1 );
INSERT INTO versions VALUES ('20030911', 1 );
INSERT INTO versions VALUES ('20031016', 1 );
INSERT INTO versions VALUES ('20031118', 1 );
INSERT INTO versions VALUES ('20031212', 1 );
INSERT INTO versions VALUES ('20040121', 1 );
INSERT INTO versions VALUES ('20040213', 1 );
INSERT INTO versions VALUES ('20040309', 1 );
INSERT INTO versions VALUES ('20040408', 1 );
INSERT INTO versions VALUES ('20040505', 1 );
INSERT INTO versions VALUES ('20040615', 1 );
INSERT INTO versions VALUES ('20040716', 1 );
INSERT INTO versions VALUES ('20040813', 1 );
INSERT INTO versions VALUES ('20040914', 1 );
INSERT INTO versions VALUES ('20041019', 1 );
INSERT INTO versions VALUES ('20041201', 1 );
INSERT INTO versions VALUES ('CVS', 1 );


--
-- Table structure for table `bugs`
--

DROP TABLE IF EXISTS `bugs`;
CREATE TABLE `bugs` (
  `bug_id` mediumint(9) NOT NULL auto_increment,
  `assigned_to` mediumint(9) NOT NULL default '0',
  `bug_file_loc` text,
  `bug_severity` enum('blocker','critical','major','normal','minor','trivial','enhancement') 
NOT NULL default 'blocker',
  `bug_status` enum('UNCONFIRMED','NEW','ASSIGNED','REOPENED','RESOLVED','VERIFIED','CLOSED') 
NOT NULL default 'UNCONFIRMED',
  `creation_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `delta_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `short_desc` mediumtext,
  `op_sys` enum('All','Windows 3.1','Windows 95','Windows 98','Windows ME','Windows 
2000','Windows NT','Windows XP','Mac System 7','Mac System 7.5','Mac System 7.6.1','Mac System 
8.0','Mac System 8.5','Mac System 8.6','Mac System 9.x','Mac OS X 10.0','Mac OS X 10.1','Mac OS 
X 
10.2','Linux','BSDI','FreeBSD','NetBSD','OpenBSD','AIX','BeOS','HP-UX','IRIX','Neutrino','OpenVMS','OS/2','OSF/1','Solaris','SunOS','other') 
NOT NULL default 'All',
  `priority` enum('P1','P2','P3','P4','P5') NOT NULL default 'P1',
  `rep_platform` enum('All','DEC','HP','Macintosh','PC','PC-x86-64','SGI','Sun','Other') 
default NULL,
  `reporter` mediumint(9) NOT NULL default '0',
  `version` varchar(64) NOT NULL default '',
  `resolution` 
enum('','FIXED','INVALID','WONTFIX','LATER','REMIND','DUPLICATE','WORKSFORME','MOVED','ABANDONED') 
NOT NULL default '',
  `target_milestone` varchar(20) NOT NULL default '---',
  `qa_contact` mediumint(9) NOT NULL default '0',
  `status_whiteboard` mediumtext NOT NULL,
  `votes` mediumint(9) NOT NULL default '0',
  `keywords` mediumtext NOT NULL,
  `lastdiffed` datetime NOT NULL default '0000-00-00 00:00:00',
  `everconfirmed` tinyint(4) NOT NULL default '0',
  `environment` varchar(80) default NULL,
  `reporter_accessible` tinyint(4) NOT NULL default '1',
  `cclist_accessible` tinyint(4) NOT NULL default '1',
  `estimated_time` decimal(5,2) NOT NULL default '0.00',
  `remaining_time` decimal(5,2) NOT NULL default '0.00',
  `alias` varchar(20) default NULL,
  `product_id` smallint(6) NOT NULL default '0',
  `component_id` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`bug_id`),
  UNIQUE KEY `alias` (`alias`),
  KEY `assigned_to` (`assigned_to`),
  KEY `creation_ts` (`creation_ts`),
  KEY `delta_ts` (`delta_ts`),
  KEY `bug_severity` (`bug_severity`),
  KEY `bug_status` (`bug_status`),
  KEY `op_sys` (`op_sys`),
  KEY `priority` (`priority`),
  KEY `reporter` (`reporter`),
  KEY `version` (`version`),
  KEY `resolution` (`resolution`),
  KEY `target_milestone` (`target_milestone`),
  KEY `qa_contact` (`qa_contact`),
  KEY `votes` (`votes`),
  KEY `product_id` (`product_id`),
  KEY `component_id` (`component_id`)
);
