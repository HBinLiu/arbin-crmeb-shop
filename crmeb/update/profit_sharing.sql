-- 微信服务商分账（单店平台抽成）
-- 1) 配置项  2) 分账流水表

CREATE TABLE IF NOT EXISTS `eb_store_order_profit_sharing` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL DEFAULT '0' COMMENT '订单id',
  `order_id` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '微信支付交易号',
  `sub_mchid` varchar(32) NOT NULL DEFAULT '' COMMENT '特约商户号',
  `receiver_type` varchar(32) NOT NULL DEFAULT 'MERCHANT_ID' COMMENT '接收方类型 MERCHANT_ID/PERSONAL_OPENID',
  `receiver_mchid` varchar(64) NOT NULL DEFAULT '' COMMENT '分账接收方账号（商户号或openid）',
  `ratio` varchar(16) NOT NULL DEFAULT '0' COMMENT '抽成比例%',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分账金额元',
  `amount_fen` int(11) NOT NULL DEFAULT '0' COMMENT '分账金额分',
  `return_amount_fen` int(11) NOT NULL DEFAULT '0' COMMENT '已回退金额分',
  `out_order_no` varchar(64) NOT NULL DEFAULT '' COMMENT '商户分账单号',
  `wx_order_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信分账单号',
  `out_return_no` varchar(64) NOT NULL DEFAULT '' COMMENT '商户回退单号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待分账1成功2失败3已回退4无需分账已解冻',
  `fail_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '失败原因',
  `result` text COMMENT '分账结果',
  `return_result` text COMMENT '回退结果',
  `add_time` int(11) NOT NULL DEFAULT '0',
  `finish_time` int(11) NOT NULL DEFAULT '0',
  `return_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `oid` (`oid`),
  KEY `order_id` (`order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信分账流水';

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_open', 'radio', 'input', 4, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '微信分账抽成', '服务商模式下，支付成功后按比例分账给配置的接收方（商户号或微信用户）', 80, 1, 0, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_open');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_ratio', 'text', 'input', 4, '', 0, 'required:true,min:0,max:30,number:true', 100, 0, '\"1\"', '分账抽成比例', '按订单实付金额抽成，0-30，例：1 表示 1%', 79, 1, 1, 0, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_ratio');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_type', 'radio', 'input', 4, '1=>商户号\n2=>微信用户', 1, '', 0, 0, '1', '分账接收方类型', '商户号分到商户；微信用户分到个人零钱（openid 须属小程序 appid）', 78, 1, 1, 0, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_type');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_mchid', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '分账接收方商户号', '接收方类型为商户号时必填，一般为服务商商户号', 77, 1, 1, 0, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_mchid');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_name', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '分账接收方商户全称', '接收方类型为商户号时必填（微信要求），与微信商户全称一致；小微/个体户填开户人姓名', 76, 1, 1, 0, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_name');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_openid', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '分账接收方OpenID', '接收方类型为微信用户时必填；须为店铺小程序下的 openid', 75, 1, 1, 0, 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_openid');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_user_name', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '接收方微信实名', '选填；若填写会与微信实名校验，不匹配会拒绝；须配置支付公钥加密', 74, 1, 1, 0, 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_user_name');

-- 挂到「微信分账抽成」开启时显示
UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_open'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 1
WHERE c.`menu_name` IN ('profit_sharing_ratio', 'profit_sharing_receiver_type');

UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_receiver_type'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 1
WHERE c.`menu_name` IN ('profit_sharing_receiver_mchid', 'profit_sharing_receiver_name');

UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_receiver_type'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 2
WHERE c.`menu_name` IN ('profit_sharing_receiver_openid', 'profit_sharing_receiver_user_name');
