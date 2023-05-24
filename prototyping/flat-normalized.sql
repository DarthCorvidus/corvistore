CREATE TABLE `l_path` (
`lp_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`lp_path` TEXT UNIQUE
);

CREATE TABLE `d_flat` (
`dfl_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`lp_id` INTEGER,
`dfl_mtime` INTEGER,
`dfl_size` INTEGER,
`dfl_created_local` TEXT,
`dfl_created_epoch` INTEGER,
`dfl_type` INTEGER,
`dfl_permissions` INTEGER,
`dfl_owner` TEXT,
`dfl_group` TEXT
);
CREATE INDEX d_flat_main ON d_flat(lp_id, dfl_size, dfl_mtime);