USE asteriskcdrdb;
/* Procedimiento para agregar a la tabla asteriskcdrdb.cdr el indice  IDX_UNIQUEID al campo uniqueid*/
DELIMITER ++ ;

DROP PROCEDURE IF EXISTS add_index_idx_uniqueid ++
CREATE PROCEDURE add_index_idx_uniqueid()
BEGIN
        DECLARE Index_cnt tinyint(1);

	set Index_cnt = 0;
        
	select count(*) into Index_cnt 
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_schema = 'asteriskcdrdb'
        AND table_name = 'cdr'
        and index_name = 'IDX_UNIQUEID';
	IF Index_cnt = 0 THEN
		Alter table asteriskcdrdb.cdr ADD INDEX IDX_UNIQUEID (uniqueid);
        END IF;
END;
++
DELIMITER ; ++

CALL add_index_idx_uniqueid();
DROP PROCEDURE IF EXISTS add_index_idx_uniqueid;

ALTER TABLE asteriskcdrdb.cdr add column intraforward varchar(250) default NULL;
ALTER TABLE asteriskcdrdb.cdr add column fromout enum('1','0') default '0';
ALTER TABLE asteriskcdrdb.cdr add column toout enum('1','0') default '0';
ALTER TABLE asteriskcdrdb.cdr add column organization_domain varchar(100) default NULL;
Alter table asteriskcdrdb.cdr ADD INDEX organization_domain (organization_domain);