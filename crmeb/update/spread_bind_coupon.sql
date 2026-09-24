-- 优惠券：仅已绑定上级推广员的被分享人可领取
-- 已有环境执行本脚本；可重复执行

SET @exist := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_issue'
      AND COLUMN_NAME = 'spread_limit'
);
SET @sql := IF(
    @exist = 0,
    'ALTER TABLE `eb_store_coupon_issue` ADD COLUMN `spread_limit` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=仅被分享人可领（上级为推广员）'' AFTER `receive_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_ut := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_issue'
      AND COLUMN_NAME = 'user_type'
);
SET @sql_ut := IF(
    @exist_ut = 0,
    'ALTER TABLE `eb_store_coupon_issue` ADD COLUMN `user_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1普通 2付费会员 3上级为推广员'' AFTER `spread_limit`',
    'SELECT 1'
);
PREPARE stmt_ut FROM @sql_ut;
EXECUTE stmt_ut;
DEALLOCATE PREPARE stmt_ut;
