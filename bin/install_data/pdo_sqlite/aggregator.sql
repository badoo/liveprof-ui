CREATE TABLE IF NOT EXISTS aggregator_metods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT
);
CREATE TABLE IF NOT EXISTS aggregator_snapshots (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  calls_count INTEGER  NOT NULL,
  app TEXT DEFAULT NULL,
  label TEXT DEFAULT NULL,
  date TEXT NOT NULL,
  %SNAPSHOT_CUSTOM_FIELDS%
  type TEXT NOT NULL DEFAULT 'auto'
);
CREATE INDEX IF NOT EXISTS app_idx ON aggregator_snapshots (app);
CREATE TABLE IF NOT EXISTS aggregator_tree (
  snapshot_id INTEGER NOT NULL,
  method_id INTEGER  NOT NULL,
  parent_id INTEGER  NOT NULL,
  %TREE_CUSTOM_FIELDS%
  FOREIGN KEY (method_id) REFERENCES aggregator_metods (id) ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY (parent_id) REFERENCES aggregator_metods (id) ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY (snapshot_id) REFERENCES aggregator_snapshots (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE TABLE IF NOT EXISTS aggregator_method_data (
  snapshot_id INTEGER NOT NULL,
  method_id INTEGER  NOT NULL,
  %DATA_CUSTOM_FIELDS%
  FOREIGN KEY (method_id) REFERENCES aggregator_metods (id) ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY (snapshot_id) REFERENCES aggregator_snapshots (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX IF NOT EXISTS snapshot_id_method_id_idx ON aggregator_method_data (snapshot_id, method_id);
