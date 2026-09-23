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

namespace app\jobs;

use app\services\pay\ProfitSharingServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Env;
use think\facade\Log;

/**
 * 确认收货/会员结算后微信分账
 * Class ProfitSharingJob
 * @package app\jobs
 */
class ProfitSharingJob extends BaseJobs
{
    use QueueTrait;

    /** 队列侧最大补试次数（不含任务内的一次清缓存重试） */
    const MAX_ATTEMPT = 5;

    public function doJob(int $orderId, int $attempt = 1, string $bizType = 'product')
    {
        $finalAttempt = $attempt >= self::MAX_ATTEMPT;
        try {
            /** @var ProfitSharingServices $services */
            $services = app()->make(ProfitSharingServices::class);
            $ok = $services->handleOrderPaid($orderId, $finalAttempt, $bizType);
            if (!$ok && !$finalAttempt) {
                $this->scheduleRetry($orderId, $attempt + 1, 120 * $attempt, $bizType);
            } elseif (!$ok && $finalAttempt) {
                $failMsg = $services->getLatestFailMsg($orderId, $bizType) ?: '分账最终失败';
                $services->unfreezeAfterShareFail($orderId, $failMsg, $bizType);
            }
        } catch (\Throwable $e) {
            Log::error('ProfitSharingJob失败:' . $e->getMessage());
            if (!$finalAttempt) {
                $this->scheduleRetry($orderId, $attempt + 1, 120 * $attempt, $bizType);
            } else {
                try {
                    /** @var ProfitSharingServices $services */
                    $services = app()->make(ProfitSharingServices::class);
                    $services->unfreezeAfterShareFail($orderId, $e->getMessage(), $bizType);
                } catch (\Throwable $e2) {
                    Log::error('ProfitSharingJob最终解冻失败:' . $e2->getMessage());
                }
            }
        }
        return true;
    }

    /**
     * 有队列则延迟重试；无队列则同步再跑一轮
     */
    protected function scheduleRetry(int $orderId, int $nextAttempt, int $secs, string $bizType = 'product'): void
    {
        if ((int)sys_config('queue_open', 0) === 1 && Env::get('cache.driver', 'file') == 'redis') {
            self::dispatchSecs($secs, 'doJob', [$orderId, $nextAttempt, $bizType]);
            return;
        }
        self::dispatch('doJob', [$orderId, $nextAttempt, $bizType]);
    }
}
