
create table user_list (
	stamp           timestamp not null,
        userid          int not null auto_increment,
        username        text not null,
	password	text not null,
	realname	text not null,
	email		text not null,
        created         datetime not null,
        status          int(4),
	perm		int(4),
        unique key(userid),
	unique(username(12))
);

insert into user_list values (NOW(), 0, 'int', password('testing'), 'Charles Loep',
	'charles@codeweavers.com', NOW(), 0, 0xffffffff);
update user_list set userid = 1000 where username = 'int';

