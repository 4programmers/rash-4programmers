create table quotes (id int NOT NULL auto_increment primary key,
			   quote text NOT NULL,
			   rating int NOT NULL DEFAULT 0,
			   flag int(1) NOT NULL DEFAULT 0,
			   date int(10),
			   submitip varchar(64));

create table queue (id int NOT NULL auto_increment primary key,
			  quote text NOT NULL,
			  rating int NOT NULL DEFAULT 0,
			  flag int(1) NOT NULL DEFAULT 0,
			  date int(10),
			  submitip varchar(64));

create table users (id int NOT NULL auto_increment primary key,
			   user varchar(20) NOT NULL,
			   `password` varchar(255) NOT NULL,
			   level int(1) NOT NULL,
			   salt text);

create table tracking (id int NOT NULL auto_increment primary key,
                              user_ip varchar(64) NOT NULL,
                              user_id int,
                              quote_id int NOT NULL,
                              vote int NOT NULL,
			      date int(10));

create table news (id int NOT NULL auto_increment primary key,
			  news text NOT NULL,
			  date int(10));

create table spam (id int NOT NULL auto_increment primary key,
			  submitip varchar(64),
			  date int(10),
			  quote text);

create table dupes (id int NOT NULL auto_increment primary key,
                          normalized text not null,
			  quote_id int not null);

insert into users (user, password, level, salt) values (
       	    		 'msm', '$1$ETZPpXlJ$kS2UkwWGzpiANgDP3bgL71', 1, '$1$ETZPpXlJ');
