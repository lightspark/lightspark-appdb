use apidb;

drop table vendor;
drop table appFamily;
drop table appVersion;
drop table userExperience;
drop table apiUsage;
drop table appCategory;
drop table appHitStats;
drop table catHitStats;
drop table appOwners;
drop table appComments;
drop table appData;


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
       vendorId		int not null,
       keywords		text,
       description	text,
       webPage		varchar(100),
       catId		int,
       key(appId)
);


/*
 * a version of an application
 */
create table appVersion (
       versionId	int not null auto_increment,
       appId		int not null,
       versionName	varchar(100) not null,
       keywords		text,
       description	text,
       webPage		varchar(100),
       rating_windows   float default 0.0,
       rating_fake      float default 0.0,
       key(versionId)
);

create table appQueue (
       queueId		int not null auto_increment,
       queueName	varchar(100) not null,
       queueVersion	varchar(100) not null,
       queueVendor	varchar(100) not null,
       queueDesc	text,
       queueEmail	varchar(100),
       queueURL		varchar(100),
       queueImage	varchar(100) not null,
       key(queueId)
);

create table userExperience (
       uExpId		int not null auto_increment,
       versionId	int not null,
       userComments	text,
       testPlatform	varchar(100),
       wineVintage	varchar(100),
       entryDate	timestamp not null,
       userName		text not null,
       wineCfgFile	text,
       key(uExpId)
);

create table apiUsage (
       apiUsageId     int not null auto_increment,
       versionId      int not null,
       apiid	      int(11) not null,
       key(apiUsageId)
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
 * not used yet
 */
create table appCrosslink (
	appId		int not null,
	catId		int not null,
	key(appId),
	index(catId)
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
 * application <> owner mapping
 */
create table appOwners (
	appId		int not null,
	ownerId		int not null,
	key(appId)
);


/*
 * user comments
 */
create table appComments (
	time		timestamp,
	commentId	int not null auto_increment,
	parentId	int default 0,
	appId		int not null,
	versionId	int default 0,
	userId		int,
	hostname	varchar(80),
	subject		varchar(128),
	body		text,
	score		int,
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
	type		enum('image', 'url'),
	description	text,
	url		varchar(255),
	key(id),
	index(appId),
	index(versionId)
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
 * app ratings
 */
create table appRating (
	id		int not null auto_increment,
	time		timestamp,  /* we might wanna expire old ratings */
	versionId	int not null,
	userId		int not null,
	system          enum('windows', 'fake'),
	score		int not null,
	key(id),
	index(versionId),
	index(userId)
);


/*
 * application notes
 */
create table appNotes (
	noteId          int not null auto_increment,
	noteTitle       varchar(255),
	noteDesc        text,
	appId           int not null,
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
