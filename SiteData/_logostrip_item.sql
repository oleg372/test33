CREATE TABLE IF NOT EXISTS `logostrip_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ts_ins` bigint(20) NOT NULL DEFAULT '0',
  `ts_upd` bigint(20) NOT NULL DEFAULT '0',
  `ts_del` bigint(20) NOT NULL DEFAULT '0',
  `pos` int(11) NOT NULL DEFAULT '1000',
  `id_list` int(10) unsigned NOT NULL COMMENT 't=;iu=Группа;*;->=logostrip_list.id;ref=SELECT logostrip_list.id,logostrip_list.list_name as `item` FROM logostrip_list ORDER BY logostrip_list.list_name',
  `is_pub` tinyint(4) NOT NULL DEFAULT '1' COMMENT 't=icon-eye-open;iu=Публиковать на сайте;a=c;*',
  `head` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 't=Заголовок;iu=Заголовок;w=99%;*',
  `head_ua` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `head_en` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `abstr` text COLLATE utf8_unicode_ci COMMENT 't=Аннотация;iu=Аннотация;h=5;w=99%',
  `abstr_ua` text COLLATE utf8_unicode_ci,
  `abstr_en` text COLLATE utf8_unicode_ci,
  `i_pic` text CHARACTER SET ascii COLLATE ascii_bin COMMENT 't=Изображение;iu=Изображение;a=c;ext=jpg,jpeg,png,gif;media=Logostrip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Логотипы|ru,ua,en' AUTO_INCREMENT=1 ;
