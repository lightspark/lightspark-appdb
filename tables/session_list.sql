use apidb;

DROP TABLE IF EXISTS session_list;

CREATE TABLE session_list (
 session_id varchar(64) NOT NULL default '',
 userid int(11) default NULL,
 ip varchar(64) default NULL,
 data text,
 messages text,
 stamp datetime NOT NULL,
 PRIMARY KEY (session_id)
) TYPE=MyISAM;
