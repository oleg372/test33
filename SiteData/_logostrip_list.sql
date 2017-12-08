
CREATE TABLE IF NOT EXISTS `logostrip_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 't=ID;a=r;ro=y;iu=',
  `ts_ins` bigint(20) NOT NULL DEFAULT '0',
  `ts_upd` bigint(20) NOT NULL DEFAULT '0' COMMENT 't=Модиф.;iu=;*;f=d.m.Y H:i;ts=dt;a=r;ro=y',
  `ts_del` bigint(20) NOT NULL DEFAULT '0',
  `pos` int(11) NOT NULL DEFAULT '10000',
  `is_pub` tinyint(4) NOT NULL DEFAULT '1' COMMENT 't=;iu=;a=c',
  `list_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 't=Группа логотипов;iu=Название группы логотипов;w=99%;*',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Группы логотипов' AUTO_INCREMENT=2 ;
