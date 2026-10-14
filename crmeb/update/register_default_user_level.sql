-- 给「尚无等级」的老用户补挂：解锁经验 <= 当前经验 的最高已显示等级
-- 需先开启用户等级，并建好经验为 0、折扣 88 的默认等级。可重复执行（只处理 level=0）。

-- 1) 更新用户表 level
UPDATE `eb_user` AS u
INNER JOIN (
    SELECT u2.uid, (
        SELECT l.id
        FROM `eb_system_user_level` AS l
        WHERE l.is_del = 0 AND l.is_show = 1 AND l.exp_num <= IFNULL(u2.exp, 0)
        ORDER BY l.grade DESC, l.exp_num DESC, l.id DESC
        LIMIT 1
    ) AS level_id
    FROM `eb_user` AS u2
    WHERE u2.is_del = 0 AND IFNULL(u2.level, 0) = 0
) AS t ON t.uid = u.uid AND t.level_id IS NOT NULL
SET u.level = t.level_id;

-- 2) 补用户等级记录（无则插入）
INSERT INTO `eb_user_level` (
    `uid`, `level_id`, `grade`, `discount`, `status`, `is_del`, `is_forever`, `mark`, `add_time`
)
SELECT
    u.uid,
    l.id,
    l.grade,
    l.discount,
    1,
    0,
    IFNULL(l.is_forever, 1),
    CONCAT('补挂默认等级:', l.name),
    UNIX_TIMESTAMP()
FROM `eb_user` AS u
INNER JOIN `eb_system_user_level` AS l ON l.id = u.level AND l.is_del = 0
LEFT JOIN `eb_user_level` AS ul ON ul.uid = u.uid AND ul.level_id = l.id AND ul.is_del = 0
WHERE u.is_del = 0
  AND IFNULL(u.level, 0) > 0
  AND ul.id IS NULL;
