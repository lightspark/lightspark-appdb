use apidb;

drop table if exists distributions;

/*
 * Distributions table.
 */
create table distributions (
        distributionId      int not null auto_increment,
        name                varchar(255) default NULL,
        url                 varchar(255) default NULL,
        submitTime          datetime NOT NULL,
        submitterId         int(11) NOT NULL default '0',
        queued              enum('true','false','rejected') NOT NULL default 'false',
        key(distributionId),
        index(name)
);

