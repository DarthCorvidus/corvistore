CREATE TABLE `d_catalog` (
`dc_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dc_name` TEXT,
`dc_parent` INTEGER
);

CREATE TABLE `d_version` (
`dvs_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dvs_mtime` INTEGER,
`dvs_size` INTEGER,
`dvs_created_local` TEXT,
`dvs_created_epoch` INTEGER,
`dvs_type` INTEGER,
`dvs_permissions` INTEGER,
`dvs_owner` TEXT,
`dvs_group` TEXT,
`dc_id` INTEGER
);
CREATE INDEX d_version_index ON d_version(dc_id, dvs_size, dvs_mtime, dvs_type);
CREATE INDEX d_catalog_index ON d_catalog(dc_name, dc_parent);
CREATE INDEX d_catalog_parent_index ON d_catalog(dc_parent);