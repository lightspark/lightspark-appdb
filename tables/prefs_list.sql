use apidb;

drop table if exists prefs_list;

CREATE TABLE prefs_list (
	id		int auto_increment not null,
	name		varchar(32),
	def_value	text,
	value_list	text,
	description	text,

	primary key(id)
);

INSERT INTO prefs_list VALUES (0, 'debug', 'no', 'yes|no', 'Enable debugging information');
INSERT INTO prefs_list VALUES (0, 'sidebar', 'left', 'left|right', 'Sidebar location');
INSERT INTO prefs_list VALUES (0, 'window:query', 'no', 'yes|no', 'Display query results in a new window');
INSERT INTO prefs_list VALUES (0, 'window:help', 'no', 'yes|no', 'Display help in a new window');
INSERT INTO prefs_list VALUES (0, 'window:offsite', 'no', 'yes|no', 'Display offsite URLs in a new window');
INSERT INTO prefs_list VALUES (0, 'confirm_comment_deletion', 'yes', 'yes|no', 'Asks why you are deleting a comment before to delete it');
INSERT INTO prefs_list VALUES (0, 'query:mode', 'view', 'view|edit', 'Default API details mode');
INSERT INTO prefs_list VALUES (0, 'query:hide_header', 'no', 'yes|no', 'Hide apidb header in query results');
INSERT INTO prefs_list VALUES (0, 'query:hide_sidebar', 'no', 'yes|no', 'Hide apidb sidebar in query results');
INSERT INTO prefs_list VALUES (0, 'send_email', 'yes', 'yes|no', 'Send email notifications');
