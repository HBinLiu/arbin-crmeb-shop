-- 优惠券增加折扣券：1满减 2折扣。折扣券 coupon_price 存支付比例，80 表示 8 折。
-- 已有环境执行；可重复执行

SET @exist_issue := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_issue'
      AND COLUMN_NAME = 'coupon_type'
);
SET @sql_issue := IF(
    @exist_issue = 0,
    'ALTER TABLE `eb_store_coupon_issue` ADD COLUMN `coupon_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1满减 2折扣'' AFTER `coupon_price`',
    'SELECT 1'
);
PREPARE stmt_issue FROM @sql_issue;
EXECUTE stmt_issue;
DEALLOCATE PREPARE stmt_issue;

SET @exist_user := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_user'
      AND COLUMN_NAME = 'coupon_type'
);
SET @sql_user := IF(
    @exist_user = 0,
    'ALTER TABLE `eb_store_coupon_user` ADD COLUMN `coupon_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1满减 2折扣'' AFTER `coupon_price`',
    'SELECT 1'
);
PREPARE stmt_user FROM @sql_user;
EXECUTE stmt_user;
DEALLOCATE PREPARE stmt_user;
