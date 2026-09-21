-- 分销码：扫码成为分销员，限制数量和过期时间。已有环境执行；可重复执行。

CREATE TABLE IF NOT EXISTS `eb_spread_code` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
  `code` varchar(16) NOT NULL DEFAULT '' COMMENT '短码，小程序 scene 使用',
  `limit_num` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '可扫码成为分销员的人数',
  `used_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已开通人数',
  `expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间，0为永不过期',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0停用',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `is_del` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分销码';

CREATE TABLE IF NOT EXISTS `eb_spread_code_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code_id` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_uid` (`code_id`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分销码开通记录';

INSERT INTO `eb_system_menus` (`pid`, `icon`, `menu_name`, `module`, `controller`, `action`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`)
SELECT 26, '', '分销码管理', 'admin', '', '', '', '', '[]', 96, 1, 1, 1, '/agent/spread_code/index', '26', 1, 'user', 0, 'admin-agent-spread-code', 0, '分销码管理'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `eb_system_menus` WHERE `unique_auth` = 'admin-agent-spread-code' AND `is_del` = 0
);

UPDATE `eb_system_menus`
SET `menu_name` = '分销码管理', `mark` = '分销码管理'
WHERE `unique_auth` = 'admin-agent-spread-code' AND `is_del` = 0;
