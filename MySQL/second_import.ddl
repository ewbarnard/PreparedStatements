CREATE TABLE `second_import` (
  `id`          SMALLINT(8) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `motion_id`   TINYINT(3) UNSIGNED     NOT NULL DEFAULT '0',
  `waypoint_id` SMALLINT(5) UNSIGNED    NOT NULL DEFAULT '0',
  `lat`         DECIMAL(10, 6)          NOT NULL,
  `lon`         DECIMAL(10, 6)          NOT NULL DEFAULT '0.000000',
  `ele`         DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT '0.00',
  `time`        DATETIME                NOT NULL,
  `distance`    MEDIUMINT(8) UNSIGNED   NOT NULL DEFAULT '0',
  `feet`        MEDIUMINT(8) UNSIGNED   NOT NULL DEFAULT '0',
  `seconds`     MEDIUMINT(8) UNSIGNED   NOT NULL DEFAULT '0',
  `mph`         DECIMAL(10, 2)          NOT NULL DEFAULT '0.00',
  `climb`       DECIMAL(10, 2)          NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
