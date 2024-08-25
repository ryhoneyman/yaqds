DROP TABLE IF EXISTS `yaqds_npc_loottable`;

CREATE TABLE `yaqds_npc_loottable` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nt_id` INT UNSIGNED NOT NULL,
  `nt_name` VARCHAR(32) NOT NULL,
  `nt_level` TINYINT UNSIGNED,
  `nt_loottable_id` INT UNSIGNED,
  `se_spawngroup_id` INT UNSIGNED,
  `s2_id` INT UNSIGNED,
  `s2_zone` VARCHAR(32),
  `s2_min_expansion` DECIMAL(3,1) DEFAULT 0,
  `s2_max_expansion` DECIMAL(3,1) DEFAULT 0,
  `se_min_expansion` DECIMAL(3,1) DEFAULT 0,
  `se_max_expansion` DECIMAL(3,1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE (`nt_name`,`nt_loottable_id`,`s2_zone`)
);

INSERT INTO yaqds_npc_loottable (nt_id,nt_name,nt_level,nt_loottable_id,se_spawngroup_id,s2_id,s2_zone,s2_min_expansion,s2_max_expansion,se_min_expansion,se_max_expansion) 
  SELECT nt.id,nt.name,nt.level,nt.loottable_id,se.spawngroupID,s2.id,s2.zone,s2.min_expansion,s2.max_expansion,se.min_expansion,se.max_expansion 
  FROM npc_types nt 
  LEFT JOIN spawnentry se ON nt.id = se.npcID 
  LEFT JOIN spawn2 s2 ON se.spawngroupID = s2.spawngroupID 
  WHERE nt.loottable_id > 0 AND s2.id IS NOT NULL
  ON DUPLICATE KEY UPDATE nt_id = nt_id;