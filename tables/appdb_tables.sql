create database if not exists apidb;

use apidb;

drop table if exists vendor;
drop table if exists appFamily;
drop table if exists appVersion;
drop table if exists userExperience;
drop table if exists appCategory;
drop table if exists appHitStats;
drop table if exists catHitStats;
drop table if exists appComments;
drop table if exists appData;
drop table if exists appBundle;
drop table if exists appVotes;
drop table if exists appNotes;
drop table if exists sessionMessages;


/*
 * vendor information
 */
create table vendor (
       vendorId		int not null auto_increment,
       vendorName	varchar(100) not null,
       vendorURL	varchar(200),
       key(vendorId)
);


/*
 * application
 */
create table appFamily (
	appId		int not null auto_increment,
	appName		varchar(100) not null,
	vendorId	int not null,
	keywords	text,
	description	text,
	webPage		varchar(100),
	catId		int,
	submitTime	timestamp(14) NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	queued		enum('true','false') NOT NULL default 'false',
	key(appId)
);


/*
 * a version of an application
 */
create table appVersion (
	versionId          int not null auto_increment,
	appId              int not null,
	versionName        varchar(100) not null,
	description        text,
	maintainer_rating  text,
	maintainer_release text,
	submitTime	timestamp(14) NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	queued		enum('true','false') NOT NULL default 'false',
	key(versionId)
);


create table userExperience (
       uExpId		int not null auto_increment,
       versionId	int not null,
       userComments	text,
       testPlatform	varchar(100),
       wineVintage	varchar(100),
       entryDate	timestamp not null,
       userId		int not null,
       wineCfgFile	text,
       key(uExpId)
);
       

/*
 * application category
 */    
create table appCategory (
	catId		int not null auto_increment,
	catName		varchar(64) not null,
	catDescription	text,
	catParent	int default 0,
	key(catId)
);


/*
 * bundleId is the appId of the 'owner app'
 */
create table appBundle (
	bundleId	int not null,
	appId		int not null,
	key(bundleId),
	index(appId)
);


/*
 * appHitStats and catHitStats are to record statistics
 */
create table appHitStats (
	appHitId	int not null auto_increment,
	time		timestamp,
	ip		varchar(16),
	appId		int not null,	
	count		int,
	key(appHitId)
);

create table catHitStats (
	catHitId	int not null auto_increment,
	time		timestamp,
	ip		varchar(16),
	catId		int not null,
	count		int,
	key(catHitId)
);


/*
 * user comments
 */
create table appComments (
	time		datetime,
	commentId	int not null auto_increment,
	parentId	int default 0,
	versionId	int not null,
	userId		int,
	hostname	varchar(80),
	subject		varchar(128),
	body		text,
	key(commentId),
	index(appId),
	index(versionId)
);



/*
 * links to screenshots and other stuff
 */
create table appData (
	id		int not null auto_increment,
	appId		int not null,
	versionId	int default 0,
	type		enum('image', 'url', 'bug'),
	description	text,
	url		varchar(255) default NULL,
	submitTime	timestamp(14) NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	queued		enum('true','false') NOT NULL default 'false',
	KEY id (id),
	KEY versionId (versionId)
);
 


/*
 * allow users to vote for apps, as in, request that an app gets better support
 */
create table appVotes (
	id		int not null auto_increment,
	time		timestamp,
	appId		int not null,
	userId		int not null,
	slot		int not null,
	key(id),
	index(appId),
	index(userId)
);


/*
 * application notes
 */
create table appNotes (
	noteId          int not null auto_increment,
	noteTitle       varchar(255),
	noteDesc        text,
	versionId       int not null,
	key(noteId)
);


/*
 *
 */
create table sessionMessages (
	id		int not null auto_increment,
	time		timestamp,
	sessionId	varchar(32),
	message		text,
	key(id),
	index(sessionId)
);
