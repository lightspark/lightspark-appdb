create database if not exists bugs;

use bugs;

drop table if exists versions;


/*
 * versions information
 */
create table versions (
       value tinytext,
       product_id smallint not null
);

INSERT INTO versions VALUES ('unspecified', 1 );
INSERT INTO versions VALUES ('20010112', 1 );
INSERT INTO versions VALUES ('20010216', 1 );
INSERT INTO versions VALUES ('20010305', 1 );
INSERT INTO versions VALUES ('20010326', 1 );
INSERT INTO versions VALUES ('20010418', 1 );
INSERT INTO versions VALUES ('20010510', 1 );
INSERT INTO versions VALUES ('20010629', 1 );
INSERT INTO versions VALUES ('20010824', 1 );
INSERT INTO versions VALUES ('20011004', 1 );
INSERT INTO versions VALUES ('20011108', 1 );
INSERT INTO versions VALUES ('20020228', 1 );
INSERT INTO versions VALUES ('20020310', 1 );
INSERT INTO versions VALUES ('20020411', 1 );
INSERT INTO versions VALUES ('20020509', 1 );
INSERT INTO versions VALUES ('20020605', 1 );
INSERT INTO versions VALUES ('20020710', 1 );
INSERT INTO versions VALUES ('20020804', 1 );
INSERT INTO versions VALUES ('20020904', 1 );
INSERT INTO versions VALUES ('20021007', 1 );
INSERT INTO versions VALUES ('20021031', 1 );
INSERT INTO versions VALUES ('20021125', 1 );
INSERT INTO versions VALUES ('20021219', 1 );
INSERT INTO versions VALUES ('20030115', 1 );
INSERT INTO versions VALUES ('20030219', 1 );
INSERT INTO versions VALUES ('20030318', 1 );
INSERT INTO versions VALUES ('20030408', 1 );
INSERT INTO versions VALUES ('20030508', 1 );
INSERT INTO versions VALUES ('20030618', 1 );
INSERT INTO versions VALUES ('20030709', 1 );
INSERT INTO versions VALUES ('20030813', 1 );
INSERT INTO versions VALUES ('20030911', 1 );
INSERT INTO versions VALUES ('20031016', 1 );
INSERT INTO versions VALUES ('20031118', 1 );
INSERT INTO versions VALUES ('20031212', 1 );
INSERT INTO versions VALUES ('20040121', 1 );
INSERT INTO versions VALUES ('20040213', 1 );
INSERT INTO versions VALUES ('20040309', 1 );
INSERT INTO versions VALUES ('20040408', 1 );
INSERT INTO versions VALUES ('20040505', 1 );
INSERT INTO versions VALUES ('20040615', 1 );
INSERT INTO versions VALUES ('20040716', 1 );
INSERT INTO versions VALUES ('20040813', 1 );
INSERT INTO versions VALUES ('20040914', 1 );
INSERT INTO versions VALUES ('20041019', 1 );
INSERT INTO versions VALUES ('20041201', 1 );
INSERT INTO versions VALUES ('CVS', 1 );
