CREATE TABLE `d_flat` (
`dfl_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dfl_path` TEXT,
`dfl_mtime` INTEGER,
`dfl_size` INTEGER,
`dfl_created_local` TEXT,
`dfl_created_epoch` INTEGER,
`dfl_type` INTEGER,
`dfl_permissions` INTEGER,
`dfl_owner` TEXT,
`dfl_group` TEXT
);
CREATE INDEX d_flat_main ON d_flat(dfl_path, dfl_size, dfl_mtime);