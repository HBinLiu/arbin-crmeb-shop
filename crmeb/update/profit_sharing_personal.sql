-- 分账接收方支持微信用户（PERSONAL_OPENID）。可重复执行。

SET @exist_type := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_order_profit_sharing'
      AND COLUMN_NAME = 'receiver_type'
);
SET @sql_type := IF(
    @exist_type = 0,
    'ALTER TABLE `eb_store_order_profit_sharing` ADD COLUMN `receiver_type` varchar(32) NOT NULL DEFAULT ''MERCHANT_ID'' COMMENT ''接收方类型 MERCHANT_ID/PERSONAL_OPENID'' AFTER `sub_mchid`',
    'SELECT 1'
);
PREPARE stmt_type FROM @sql_type;
EXECUTE stmt_type;
DEALLOCATE PREPARE stmt_type;

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_type', 'radio', 'input', 4, '1=>商户号\n2=>微信用户', 1, '', 0, 0, '1', '分账接收方类型', '商户号分到商户；微信用户分到个人零钱（openid 须属小程序 appid）', 78, 1, 1, 0, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_type');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_openid', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '分账接收方OpenID', '接收方类型为微信用户时必填；须为店铺小程序下的 openid；须先登录过该小程序', 76, 1, 1, 0, 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_openid');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'profit_sharing_receiver_user_name', 'text', 'input', 4, '', 0, '', 100, 0, '\"\"', '接收方微信实名', '选填；若填写会与微信实名校验，不匹配会拒绝；须配置支付公钥加密', 75, 1, 1, 0, 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'profit_sharing_receiver_user_name');

UPDATE `eb_system_config`
SET `info` = '分账接收方商户号',
    `desc` = '接收方类型为商户号时必填，一般为服务商商户号',
    `required` = ''
WHERE `menu_name` = 'profit_sharing_receiver_mchid';

UPDATE `eb_system_config`
SET `info` = '分账接收方商户全称',
    `desc` = '接收方类型为商户号时必填（微信要求），与微信商户全称一致；小微/个体户填开户人姓名',
    `required` = ''
WHERE `menu_name` = 'profit_sharing_receiver_name';

UPDATE `eb_system_config`
SET `info` = '分账接收方OpenID',
    `desc` = '接收方类型为微信用户时必填；须为店铺小程序下的 openid',
    `required` = ''
WHERE `menu_name` = 'profit_sharing_receiver_openid';

UPDATE `eb_system_config`
SET `info` = '接收方微信实名',
    `desc` = '选填；若填写会与微信实名校验，不匹配会拒绝；须配置支付公钥加密',
    `required` = ''
WHERE `menu_name` = 'profit_sharing_receiver_user_name';

UPDATE `eb_system_config`
SET `desc` = '服务商模式下，支付成功后按比例分账给配置的接收方（商户号或微信用户）'
WHERE `menu_name` = 'profit_sharing_open';

-- 挂到「微信分账抽成」开启时显示
UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_open'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 1
WHERE c.`menu_name` IN ('profit_sharing_ratio', 'profit_sharing_receiver_type');

-- 商户号相关：接收方类型=1
UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_receiver_type'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 1
WHERE c.`menu_name` IN ('profit_sharing_receiver_mchid', 'profit_sharing_receiver_name');

-- 微信用户相关：接收方类型=2
UPDATE `eb_system_config` AS c
INNER JOIN `eb_system_config` AS p ON p.`menu_name` = 'profit_sharing_receiver_type'
SET c.`level` = 1, c.`link_id` = p.`id`, c.`link_value` = 2
WHERE c.`menu_name` IN ('profit_sharing_receiver_openid', 'profit_sharing_receiver_user_name');
