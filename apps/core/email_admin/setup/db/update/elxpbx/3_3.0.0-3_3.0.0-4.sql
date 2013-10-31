use elxpbx;

drop table messages_vacations;

CREATE TABLE IF NOT EXIST vacations (
    id int(11) NOT NULL AUTO_INCREMENT,
    id_user int(11) NOT NULL,
    id_recording int(10) unsigned NULL,
    email_subject varchar(150) NOT NULL,
    email_body text,
    init_date DATE,
    end_date DATE,
    vacation varchar(5) DEFAULT 'no',
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES acl_user (id),
    FOREIGN KEY (id_recording) REFERENCES recordings (uniqueid)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=latin1;
