CREATE TABLE banner (
	id		int not null,
	desc		text,
	img		varchar(255),
	url		varchar(255),
	alt		varchar(255),
	imp		int not null,
	clk		int not null,
	lastmod		timestamp,

	primary key(id)
)
