use apidb;

drop table if exists error_log;

create table error_log (
  id            int not null auto_increment,
  submitTime    datetime,
  userid        int not null default '0',
  type          enum('sql_error', 'general_error'),
  log_text      text,
  request_text  text,
  deleted       bool,
  key(id)
);