-- 优惠券：仅已绑定上级推广员可领取
-- 已有环境执行本脚本；可重复执行

SET @exist := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_issue'
      AND COLUMN_NAME = 'spread_limit'
);
SET @sql := IF(
    @exist = 0,
    'ALTER TABLE `eb_store_coupon_issue` ADD COLUMN `spread_limit` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=仅已绑定上级推广员可领取'' AFTER `receive_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
