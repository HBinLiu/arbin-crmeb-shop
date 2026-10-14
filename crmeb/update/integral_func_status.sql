-- 积分功能独立开关。可重复执行。
-- 后台：营销 → 积分配置 →「积分功能」

SET @cfg := (
    SELECT COUNT(*) FROM `eb_system_config` WHERE `menu_name` = 'integral_func_status'
);
SET @tab_id := (
    SELECT IFNULL(
        (SELECT `config_tab_id` FROM `eb_system_config` WHERE `menu_name` = 'integral_ratio' LIMIT 1),
        11
    )
);
SET @sql := IF(
    @cfg = 0,
    CONCAT(
        'INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`) VALUES (',
        '''integral_func_status'', ''radio'', ''input'', ', @tab_id, ', ''1=>开启\\n0=>关闭'', 1, '''', 0, 0, ''1'', ''积分功能'', ''关闭后用户端不展示积分中心、下单不可用积分抵扣等'', 100, 1, 0, 0, 0)'
    ),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
