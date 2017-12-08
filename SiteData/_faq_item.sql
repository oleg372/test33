--
-- Структура таблицы `faq_item`
--

CREATE TABLE IF NOT EXISTS `faq_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ts_ins` bigint(20) NOT NULL DEFAULT '0',
  `ts_upd` bigint(20) NOT NULL DEFAULT '0',
  `ts_del` bigint(20) NOT NULL DEFAULT '0',
  `pos` int(11) NOT NULL DEFAULT '1000',
  `id_list` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 't=;iu=Group;*;->=faq_list.id;ref=SELECT faq_list.id,faq_list.list_name as `item` FROM faq_list ORDER BY faq_list.list_name',
  `is_pub` tinyint(4) NOT NULL DEFAULT '1' COMMENT 't=icon-eye-open;iu=Publish on website;a=c',
  `qsn` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 't=Question;iu=Question;w=99%;*',
  `ans` text COLLATE utf8_unicode_ci COMMENT 't=Answer;iu=Answer;h=400px;w=99%;e=wys',
  `i_pic` text CHARACTER SET ascii COLLATE ascii_bin COMMENT 't=;iu=;a=c;ext=jpg,jpeg,png;media=Sliders',
  `video_link` varchar(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 't=;iu=;w=99%',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Frequently Asked Questions' AUTO_INCREMENT=8 ;
