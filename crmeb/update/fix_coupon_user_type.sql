-- 修复：下单自动领折扣券时未写 coupon_type，默认成满减，8折(存80)被显示成80元。
-- 将「模板是折扣券、用户券仍是满减」的记录改回折扣类型。可重复执行。

UPDATE `eb_store_coupon_user` AS u
INNER JOIN `eb_store_coupon_issue` AS i ON i.`id` = u.`cid`
SET u.`coupon_type` = 2
WHERE i.`coupon_type` = 2
  AND (u.`coupon_type` IS NULL OR u.`coupon_type` <> 2)
  AND u.`status` = 0;
