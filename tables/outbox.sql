use apidb;

drop table if exists outbox;

create table outbox (
    `id` int NOT NULL AUTO_INCREMENT,
    `to` text NOT NULL,
    `subject` text NOT NULL,
    `message` text NOT NULL,
    `headers` text,
    `parameters` text,
    `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);
