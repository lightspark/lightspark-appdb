use apidb;

drop table if exists sessions;

create table sessions (
    `id` varchar(40) NOT NULL,
    `data` text NOT NULL,
    `expire` datetime NOT NULL,
    PRIMARY KEY (`id`)
);
