use apidb;

drop table if exists appMaintainerQueue;
drop table if exists appMaintainers;

create table appMaintainerQueue (
    queueId         int not null auto_increment,
    appId           int,
    versionId       int,
    userId          int,
    maintainReason  text,
    submitTime  timestamp,
    key(queueId)
);

create table appMaintainers (
    maintainerId int not null auto_increment,
    appId        int,
    versionId    int,
    userId       int,
    submitTime   timestamp,
    key(maintainerId)
);
