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
`dpt_name` TEXT,
`dst_id` INTEGER
);


CREATE TABLE `n_fileobject2basic` (
`nfod_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`dfo_id` INTEGER,
`dv_id` INTEGER,
`dsp_id` INTEGER,
`nfod_serial` INTEGER
);