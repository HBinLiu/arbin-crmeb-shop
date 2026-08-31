-- 平级/越级资产奖配置（分销配置 tab_id=72）
-- 已有环境执行本脚本；新装可在 crmeb.sql 中同步追加

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'peer_brokerage_status', 'radio', 'input', 72, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '平级/越级资产奖', '购买人分销等级大于等于一级推广人时，一级仅按资产奖比例返佣，二级不返佣', 97, 1, 0, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'peer_brokerage_status');

INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'peer_brokerage_ratio', 'text', 'input', 72, '', 0, 'required:true,min:0,max:100,number:true', 100, 0, '\"1.8\"', '平级/越级资产奖比例', '平级或越级时一级推广人资产奖比例，0-100，例：1.8 表示 1.8%', 96, 1, 0, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'peer_brokerage_ratio');
