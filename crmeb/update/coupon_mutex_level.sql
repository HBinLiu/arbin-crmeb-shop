-- 优惠券：与其他优惠互斥。开启后使用该券时不叠加等级折/会员价/积分抵扣等，按商品原价计券。
-- 已有环境执行；可重复执行

SET @exist := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_coupon_issue'
      AND COLUMN_NAME = 'mutex_level'
);
SET @sql := IF(
    @exist = 0,
    'ALTER TABLE `eb_store_coupon_issue` ADD COLUMN `mutex_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=与其他优惠互斥(等级/会员价/积分等)'' AFTER `spread_limit`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
