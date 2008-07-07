use apidb;

drop table if exists buglinks;

/*
 * link a bug to a version of an application
 */
create table buglinks (
        linkId          int not null auto_increment,
	bug_id          int not null,
	versionId       int not null,
	submitTime	datetime NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	state 		enum('accepted','queued') NOT NULL default 'accepted',
        key(linkId),
	index(bug_id),
	index(versionId)
);
