use apidb;

drop table if exists appMaintainerQueue;
drop table if exists appMaintainers;

/*
 * List of the application maintainers.  These users have the rights
 * to delete comments, edit the application description and otherwise
 * care for an application.
 */
create table appMaintainers (
    maintainerId    int not null auto_increment,
    appId           int,
    versionId       int,
    userId          int,
    superMaintainer bool,
    submitTime      datetime,
    key(maintainerId)
);

/*
 * Queue where users names will go if they request to become an application
 * maintainer.  This includes the reason they want to become a maintainer.
 */
create table appMaintainerQueue (
    queueId         int not null auto_increment,
    appId           int,
    versionId       int,
    userId          int,
    maintainReason  text,
    superMaintainer bool,
    submitTime	    datetime,
    key(queueId)
);
