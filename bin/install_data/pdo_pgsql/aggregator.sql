CREATE TABLE IF NOT EXISTS aggregator_metods (
  id SERIAL NOT NULL PRIMARY KEY,
  name CHAR(300) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name_idx on aggregator_metods (name);

DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'types') THEN
create type types AS ENUM('auto','manual');
END IF;
END
$$;

CREATE TABLE IF NOT EXISTS aggregator_snapshots (
  id SERIAL NOT NULL PRIMARY KEY,
  calls_count INT NOT NULL,
  app CHAR(32) DEFAULT NULL,
  date date NOT NULL,
  label CHAR(100) DEFAULT NULL,
  type types NOT NULL DEFAULT 'auto',
  %SNAPSHOT_CUSTOM_FIELDS%
);

CREATE TABLE IF NOT EXISTS aggregator_tree (
  snapshot_id INT NOT NULL,
  method_id INT references aggregator_metods(id),
  parent_id INT references aggregator_metods(id),
  %TREE_CUSTOM_FIELDS%
);
CREATE INDEX IF NOT EXISTS snapshot_id_parent_id_idx ON aggregator_tree (snapshot_id, parent_id);
CREATE INDEX IF NOT EXISTS snapshot_id_method_id_idx ON aggregator_tree (snapshot_id, method_id);

CREATE TABLE IF NOT EXISTS aggregator_method_data (
  snapshot_id INT references aggregator_snapshots(id),
  method_id INT references aggregator_metods(id),
  %DATA_CUSTOM_FIELDS%
);
CREATE INDEX IF NOT EXISTS aggregator_method_data_ibfk_1 ON aggregator_method_data (snapshot_id,method_id);
CREATE INDEX IF NOT EXISTS aggregator_method_data_ibfk_2 ON aggregator_method_data (method_id);
