CREATE TABLE IF NOT EXISTS `aggregator_metods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(300) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_idx` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=178027 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aggregator_snapshots` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `calls_count` int(11) unsigned NOT NULL,
  `app` varchar(32) DEFAULT NULL,
  `date` date NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  %SNAPSHOT_CUSTOM_FIELDS%
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5479 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aggregator_tree` (
  `snapshot_id` int(11) unsigned NOT NULL,
  `method_id` int(11) unsigned NOT NULL,
  `parent_id` int(11) unsigned NOT NULL,
  %TREE_CUSTOM_FIELDS%
  KEY `snapshot_id_parent_id_idx` (`snapshot_id`,`parent_id`),
  KEY `snapshot_id_method_id_idx` (`snapshot_id`,`method_id`),
  CONSTRAINT `aggregator_tree_ibfk_3` FOREIGN KEY (`snapshot_id`) REFERENCES `aggregator_snapshots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `aggregator_method_data` (
  `snapshot_id` int(11) unsigned NOT NULL,
  `method_id` int(11) unsigned NOT NULL,
  %DATA_CUSTOM_FIELDS%
  KEY `snapshot_id_method_id_idx` (`snapshot_id`,`method_id`),
  KEY `method_id` (`method_id`),
  CONSTRAINT `aggregator_method_data_ibfk_1` FOREIGN KEY (`method_id`) REFERENCES `aggregator_metods` (`id`),
  CONSTRAINT `aggregator_method_data_ibfk_2` FOREIGN KEY (`snapshot_id`) REFERENCES `aggregator_snapshots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
