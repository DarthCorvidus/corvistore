CREATE TABLE `d_catalog` (
`dc_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dc_name` TEXT,
`dc_parent` INTEGER
);

CREATE TABLE `d_version` (
`dvs_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dvs_mtime` INTEGER,
`dvs_size` INTEGER,
`dvs_created` TEXT,
`dvs_type` INTEGER,
`dc_id` INTEGER
);
CREATE INDEX d_version_index ON d_version(dc_id, dvs_size, dvs_mtime, dvs_type);
CREATE INDEX d_catalog_index ON d_catalog(dc_name, dc_parent);