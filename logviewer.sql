
CREATE TABLE `vts_fehlerlog` (
  `id` bigint(20) NOT NULL,
  `logfile` varchar(255) NOT NULL,
  `modul` varchar(30) NOT NULL,
  `text` varchar(512) NOT NULL,
  `anzahl` bigint(20) NOT NULL,
  `letzter` datetime NOT NULL,
  `anzahlsolved` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `vts_fehlerlog`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `vts_fehlerlog`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

