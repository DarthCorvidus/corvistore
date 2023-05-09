-- Table: public.d_catalog

-- DROP TABLE public.d_storage;

CREATE TABLE public.d_storage (
  dst_id BIGSERIAL,
  dst_name character varying(50),
  dst_location character varying(255),
  dst_type character varying(10),
  CONSTRAINT d_storage_pkey PRIMARY KEY (dst_id),
  CONSTRAINT d_storage_name_unique UNIQUE (dst_name)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_storage
  OWNER TO postgres;

CREATE TABLE public.d_partition (
	dpt_id BIGSERIAL,
	dpt_name character varying(50),
	dpt_type character varying(10),
    dst_id BIGINT,
	CONSTRAINT d_partition_pkey PRIMARY KEY (dpt_id),
	CONSTRAINT d_partition_name_unique UNIQUE (dpt_name)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_partition
  OWNER TO postgres;


CREATE TABLE public.d_policy (
	dpo_id BIGSERIAL,
	dpo_name character varying(50),
	dpt_id bigint,
	dpo_version_exists int,
	dpo_version_deleted int,
	dpo_retention_exists int,
	dpo_retention_deleted int,
	CONSTRAINT d_policy_pkey PRIMARY KEY (dpo_id),
	CONSTRAINT d_policy_name_unique UNIQUE (dpo_name)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_policy
  OWNER TO postgres;

CREATE TABLE public.d_node (
	dnd_id BIGSERIAL,
	dnd_name character varying(50),
	dpo_id bigint,
	CONSTRAINT d_node_pkey PRIMARY KEY (dnd_id),
	CONSTRAINT d_node_name_unique UNIQUE (dnd_name)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_node
  OWNER TO postgres;

CREATE TABLE public.d_catalog (
	dc_id BIGSERIAL,
	dc_name character varying(255),
	dc_type smallint,
	dnd_id BIGINT,
	dc_parent BIGINT,
	CONSTRAINT d_catalog_pkey PRIMARY KEY (dc_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_catalog
  OWNER TO postgres;

CREATE TABLE d_version (
	dvs_id BIGSERIAL,
	dvs_atime INT,
	dvs_mtime INT,
	dvs_ctime INT,
	dvs_permissions INT,
	dvs_owner character varying(50),
	dvs_group character varying(50),
	dvs_size BIGINT,
	dvs_created INT,
	dvs_stored SMALLINT,
	dvs_deleted SMALLINT,
	dc_id BIGINT,
	CONSTRAINT d_version_pkey PRIMARY KEY (dvs_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.d_version
  OWNER TO postgres;

CREATE TABLE n_version2basic (
nvb_id BIGSERIAL,
dv_id BIGINT,
dst_id BIGINT,
dpt_id BIGINT,
nvb_stored SMALLINT,
CONSTRAINT n_version2basic_pkey PRIMARY KEY (nvb_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.n_version2basic
  OWNER TO postgres;
