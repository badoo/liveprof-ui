CREATE TABLE IF NOT EXISTS  aggregator_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  app TEXT NOT NULL,
  date TEXT NOT NULL,
  label TEXT NOT NULL,
  type TEXT NOT NULL DEFAULT 'auto',
  status TEXT NOT NULL DEFAULT 'new'
);
CREATE INDEX IF NOT EXISTS status_idx ON aggregator_jobs (status);
CREATE INDEX IF NOT EXISTS app_label_date_idx ON aggregator_jobs (app,label,date);