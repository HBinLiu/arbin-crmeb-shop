-- 分账流水区分商品单 / 会员单。可重复执行。

SET @exist_biz := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eb_store_order_profit_sharing'
      AND COLUMN_NAME = 'biz_type'
);
SET @sql_biz := IF(
    @exist_biz = 0,
    'ALTER TABLE `eb_store_order_profit_sharing` ADD COLUMN `biz_type` varchar(16) NOT NULL DEFAULT ''product'' COMMENT ''业务类型 product商品/member会员'' AFTER `oid`, ADD KEY `biz_oid` (`biz_type`, `oid`)',
    'SELECT 1'
);
PREPARE stmt_biz FROM @sql_biz;
EXECUTE stmt_biz;
DEALLOCATE PREPARE stmt_biz;
