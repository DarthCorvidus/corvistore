CREATE TABLE `d_storage` (
`dst_id` INTEGER PRIMARY KEY AUTOINCREMENT, 
`dst_name` TEXT UNIQUE,
`dst_location` TEXT,
`dst_type` TEXT
);

CREATE TABLE `d_fileobject` (
`dfo_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`df_path` TEXT,
`df_ctime` INTEGER,
`df_mtime` INTEGER, 
`df_size` INTEGER,
`df_perms` INTEGER,
`df_owner` TEXT,
`df_group` TEXT,
`df_state` INTEGER,
`df_active` INTEGER,
`df_type` INTEGER,
`df_created` INTEGER,
`df_node` INTEGER
);

CREATE TABLE `d_partition` (
`dpt_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dpt_name` TEXT UNIQUE,
`dpt_type` TEXT,
`dst_id` INTEGER,
`dpt_copy` INTEGER,
`dpt_nextpt` INTEGER
);

CREATE TABLE `d_policy` (
`dpo_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dpo_name` TEXT UNIQUE,
`dpt_id` INTEGER,
`dpo_version_exists` INTEGER,
`dpo_version_deleted` INTEGER,
`dpo_retention_exists` INTEGER,
`dpo_retention_deleted` INTEGER
);

CREATE TABLE `d_node` (
`dnd_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dnd_name` TEXT UNIQUE,
`dpo_id` INTEGER,
`dnd_password` TEXT,
`dnd_salt` TEXT
);

CREATE TABLE `d_user` (
`du_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`du_name` TEXT UNIQUE,
`du_password` TEXT,
`du_salt` TEXT
);

CREATE TABLE `d_catalog` (
`dc_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dc_dirname` TEXT,
`dc_name` TEXT,
`dnd_id` INTEGER,
`dc_parent` INTEGER
);

CREATE TABLE `d_version` (
`dvs_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dvs_atime` INTEGER,
`dvs_mtime` INTEGER,
`dvs_ctime` INTEGER,
`dvs_permissions` INTEGER,
`dvs_owner` TEXT,
`dvs_group` TEXT,
`dvs_size` INTEGER,
`dvs_created_epoch` INTEGER,
`dvs_created_local` TEXT,
`dvs_type` TEXT,
`dvs_stored` INTEGER,
`dc_id` INTEGER
);

CREATE TABLE `d_content` (
`dco_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dvs_id` INTEGER,
`dst_id` INTEGER,
`dpt_id` INTEGER,
`dco_serial` INTEGER,
`dco_stored` INTEGER
);

CREATE INDEX d_version_index ON d_version(dvs_mtime, dvs_stored, dvs_type, dvs_size, dvs_mtime, dc_id);
CREATE INDEX d_catalog_index ON d_catalog(dc_name, dnd_id, dc_parent);
CREATE INDEX d_catalog_parent_index ON d_catalog(dc_parent, dnd_id);
create index d_version_index_dc_id ON d_version(dc_id);
create unique index d_catalog_unique ON d_catalog(dc_dirname, dc_name, dnd_id);