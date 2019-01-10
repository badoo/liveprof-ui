DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'types') THEN
create type types AS ENUM('auto','manual');
END IF;
END
$$;

DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'statuses') THEN
create type statuses AS ENUM('new','processing','finished','error');
END IF;
END
$$;

CREATE TABLE IF NOT EXISTS  aggregator_jobs (
  id SERIAL NOT NULL PRIMARY KEY,
  app CHAR(32) NOT NULL,
  date date NOT NULL,
  label CHAR(100) NOT NULL,
  type types NOT NULL DEFAULT 'auto',
  status statuses NOT NULL DEFAULT 'new'
);
CREATE INDEX IF NOT EXISTS status_idx ON aggregator_jobs (status);
CREATE INDEX IF NOT EXISTS app_label_date_idx ON aggregator_jobs (app,label,date);
