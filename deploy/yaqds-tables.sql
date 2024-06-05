CREATE TABLE `yaqds_npc_loottable` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nt_id` INT UNSIGNED NOT NULL,
  `nt_name` VARCHAR(32) NOT NULL,
  `nt_level` TINYINT UNSIGNED,
  `nt_loottable_id` INT UNSIGNED,
  `se_spawngroup_id` INT UNSIGNED,
  `s2_id` INT UNSIGNED,
  `s2_zone` VARCHAR(32),
  `s2_min_expansion` DECIMAL(2,1) DEFAULT 0,
  `s2_max_expansion` DECIMAL(2,1) DEFAULT 0,
  `se_min_expansion` DECIMAL(2,1) DEFAULT 0,
  `se_max_expansion` DECIMAL(2,1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE (`nt_name`,`nt_loottable_id`,`s2_zone`)
);