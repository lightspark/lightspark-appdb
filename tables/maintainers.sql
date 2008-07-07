use apidb;

drop table if exists appMaintainerQueue;
drop table if exists appMaintainers;

/*
 * List of the application maintainers.  These users have the rights
 * to delete comments, edit the application description and otherwise
 * care for an application.
 *
 * We also store the reason they asked to be a maintainer since we use this table
 * for both queued and unqueued maintainers
 */
create table appMaintainers (
    maintainerId        int not null auto_increment,
    appId               int,
    versionId           int,
    userId              int,
    maintainReason      text,
    superMaintainer     tinyint(1),
    submitTime          datetime,
    state               enum('accepted','queued','pending') NOT NULL default 'accepted',
    notificationLevel   int not null default '0',
    notificationTime    datetime,
    key(maintainerId)
);
