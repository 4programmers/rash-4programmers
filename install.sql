-- Use the following commands to create the database and the users:

--create database $DATABASE$;
--create user '$USERNAME$'@'$HOSTSPEC$' identified by '$PASSWORD$';
--grant all privileges on $DATABASE$.* to '$USERNAME$'@'$HOSTSPEC$';

create table $QUOTETABLE$ (id int NOT NULL auto_increment primary key,
			   quote text NOT NULL,
			   rating int NOT NULL DEFAULT 0,
			   flag int(1) NOT NULL DEFAULT 0,
			   date int(10),
			   submitip varchar(64));

create table $QUEUETABLE$ (id int NOT NULL auto_increment primary key,
			  quote text NOT NULL,
			  rating int NOT NULL DEFAULT 0,
			  flag int(1) NOT NULL DEFAULT 0,
			  date int(10),
			  submitip varchar(64));

create table $USERSTABLE$ (id int NOT NULL auto_increment primary key,
			   user varchar(20) NOT NULL,
			   `password` varchar(255) NOT NULL,
			   level int(1) NOT NULL,
			   salt text);

create table $TRACKINGTABLE$ (id int NOT NULL auto_increment primary key,
                              user_ip varchar(64) NOT NULL,
                              user_id int,
                              quote_id int NOT NULL,
                              vote int NOT NULL,
			      date int(10));

create table $NEWSTABLE$ (id int NOT NULL auto_increment primary key,
			  news text NOT NULL,
			  date int(10));

create table $SPAMTABLE$ (id int NOT NULL auto_increment primary key,
			  submitip varchar(64),
			  date int(10),
			  quote text);

create table $DUPETABLE$ (id int NOT NULL auto_increment primary key,
                          normalized text not null,
			  quote_id int not null);

insert into $USERSTABLE$ (user, password, level, salt) values (
       	    		 '$ADMINUSER$', '$ADMINPASS$', 1, '$ADMINSALT$');
