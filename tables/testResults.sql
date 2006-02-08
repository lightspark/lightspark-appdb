use apidb;

drop table if exists testResults;

/*
 * Version Testing results
 */
create table testResults (
        testingId       int not null auto_increment,
	versionId       int not null,
        whatWorks	text,
        whatDoesnt	text,
        whatNotTested   text,
        testedDate      datetime not null,
        distributionId  int not null,
	testedRelease 	tinytext,
	installs	enum('Yes','No','N/A') NOT NULL default 'Yes',
	runs		enum('Yes','No','Not Installable') NOT NULL default 'Yes',
	testedRating  	tinytext,
        comments        text,
	submitTime	timestamp(14) NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	queued		enum('true','false','rejected') NOT NULL default 'false',
        key(testingId)
);
