<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\agent;

use app\services\BaseServices;
use app\services\user\UserServices;

/**
 * 平级/越级分销资产奖
 * Class BrokeragePeerServices
 * @package app\services\agent
 */
class BrokeragePeerServices extends BaseServices
{
    /**
     * 是否开启平级/越级资产奖
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (int)sys_config('peer_brokerage_status', 0) === 1;
    }

    /**
     * 平级/越级资产奖比例（百分比，如 1.8 表示 1.8%）
     * @return string
     */
    public function getRatio(): string
    {
        $ratio = sys_config('peer_brokerage_ratio', '');
        if ($ratio === '' || $ratio === null) {
            return '1.8';
        }
        return (string)$ratio;
    }

    /**
     * 用户分销等级 grade（无等级为 0）
     * @param int $uid
     * @return int
     */
    public function getUserGrade(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $agentLevelId = (int)$userServices->value(['uid' => $uid], 'agent_level');
        if (!$agentLevelId) {
            return 0;
        }
        /** @var AgentLevelServices $agentLevelServices */
        $agentLevelServices = app()->make(AgentLevelServices::class);
        $levelInfo = $agentLevelServices->getLevelInfo($agentLevelId);
        if (!$levelInfo) {
            return 0;
        }
        return (int)($levelInfo['grade'] ?? 0);
    }

    /**
     * 购买人与一级推广人是否触发平级/越级资产奖
     * 自购（购买人=一级）不参与
     * @param int $buyerUid 购买人
     * @param int $spreadOneUid 一级推广人
     * @return bool
     */
    public function isPeerBrokerage(int $buyerUid, int $spreadOneUid): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        if ($buyerUid <= 0 || $spreadOneUid <= 0) {
            return false;
        }
        // 自购不参与平级/越级规则
        if ($buyerUid === $spreadOneUid) {
            return false;
        }
        $buyerGrade = $this->getUserGrade($buyerUid);
        $spreadOneGrade = $this->getUserGrade($spreadOneUid);
        return $buyerGrade >= $spreadOneGrade;
    }
}
