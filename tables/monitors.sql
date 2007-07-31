use apidb;

drop table if exists appMonitors;

/*
 * Let users monitor changes to applications
 */
create table appMonitors (
        monitorId       int not null auto_increment,
	appId           int not null,
	versionId       int not null,
	submitTime	datetime NOT NULL,
	userId	        int(11) NOT NULL default '0',
        key(monitorId),
	index(appid),
	index(versionId),
	index(userId)
);
