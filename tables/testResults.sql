use apidb;

drop table if exists testResults;
drop table if exists TestResults;

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
	installs	enum('Yes','No') NOT NULL default 'Yes',
	runs		enum('Yes','No','???') NOT NULL default 'Yes',
	testedRating  	tinytext,
        comments        text,
	submitTime	timestamp(14) NOT NULL,
	submitterId	int(11) NOT NULL default '0',
	queued		enum('true','false','rejected') NOT NULL default 'false',
        key(testingId)
);
