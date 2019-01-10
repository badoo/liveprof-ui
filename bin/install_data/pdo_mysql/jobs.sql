CREATE TABLE IF NOT EXISTS  `aggregator_jobs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(32) NOT NULL,
  `date` date NOT NULL,
  `label` varchar(100) NOT NULL,
  `type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `status` enum('new','processing','finished','error') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`),
  KEY `status_idx` (`status`),
  KEY `app_label_date_idx` (`app`,`label`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
