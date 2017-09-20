CREATE TABLE `result` (
  `id`              SMALLINT(5) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `method`          VARCHAR(31)             NOT NULL DEFAULT '',
  `rows`            INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `seconds`         DECIMAL(12, 6) UNSIGNED NOT NULL DEFAULT '0.000000',
  `rows_per_second` DECIMAL(12, 3) UNSIGNED NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`id`),
  KEY `method` (`method`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
