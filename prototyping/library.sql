CREATE TABLE `d_file` (
`dfl_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dfl_path` TEXT,
`dfl_size` INTEGER
);

CREATE TABLE `d_volume` (
`dvl_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dvl_name` TEXT,
`dvl_blocksize` INTEGER,
`dvl_blocks` INTEGER,
`dvl_blocks_used` INTEGER
);

CREATE TABLE `n_volume2file` (
`nvf_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dfl_id` INTEGER,
`dvl_id` INTEGER,
`nvf_part` INTEGER,
`nvf_offset` INTEGER,
`nvf_length` INTEGER
);

