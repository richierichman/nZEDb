ALTER TABLE releases ALTER COLUMN nzbstatus TYPE smallint;
ALTER TABLE releases ALTER COLUMN nzbstatus SET NOT NULL;
ALTER TABLE releases ALTER COLUMN nzbstatus SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN iscategorized TYPE smallint;
ALTER TABLE releases ALTER COLUMN iscategorized SET NOT NULL;
ALTER TABLE releases ALTER COLUMN iscategorized SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN isrenamed TYPE smallint;
ALTER TABLE releases ALTER COLUMN isrenamed SET NOT NULL;
ALTER TABLE releases ALTER COLUMN isrenamed SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN ishashed TYPE smallint;
ALTER TABLE releases ALTER COLUMN ishashed SET NOT NULL;
ALTER TABLE releases ALTER COLUMN ishashed SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN isrequestid TYPE smallint;
ALTER TABLE releases ALTER COLUMN isrequestid SET NOT NULL;
ALTER TABLE releases ALTER COLUMN isrequestid SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN jpgstatus TYPE smallint;
ALTER TABLE releases ALTER COLUMN jpgstatus SET NOT NULL;
ALTER TABLE releases ALTER COLUMN jpgstatus SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN videostatus TYPE smallint;
ALTER TABLE releases ALTER COLUMN videostatus SET NOT NULL;
ALTER TABLE releases ALTER COLUMN videostatus SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN audiostatus TYPE smallint;
ALTER TABLE releases ALTER COLUMN audiostatus SET NOT NULL;
ALTER TABLE releases ALTER COLUMN audiostatus SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN proc_pp TYPE smallint;
ALTER TABLE releases ALTER COLUMN proc_pp SET NOT NULL;
ALTER TABLE releases ALTER COLUMN proc_pp SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN proc_sorter TYPE smallint;
ALTER TABLE releases ALTER COLUMN proc_sorter SET NOT NULL;
ALTER TABLE releases ALTER COLUMN proc_sorter SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN proc_par2 TYPE smallint;
ALTER TABLE releases ALTER COLUMN proc_par2 SET NOT NULL;
ALTER TABLE releases ALTER COLUMN proc_par2 SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN proc_nfo TYPE smallint;
ALTER TABLE releases ALTER COLUMN proc_nfo SET NOT NULL;
ALTER TABLE releases ALTER COLUMN proc_nfo SET DEFAULT 0;
ALTER TABLE releases ALTER COLUMN proc_files TYPE smallint;
ALTER TABLE releases ALTER COLUMN proc_files SET NOT NULL;
ALTER TABLE releases ALTER COLUMN proc_files SET DEFAULT 0;

UPDATE site set value = '184' where setting = 'sqlpatch';