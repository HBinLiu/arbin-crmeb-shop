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
use think\facade\Log;

/**
 * 支付成功后微信分账
 * Class ProfitSharingJob
 * @package app\jobs
 */
class ProfitSharingJob extends BaseJobs
{
    use QueueTrait;

    public function doJob(int $orderId)
    {
        try {
            /** @var ProfitSharingServices $services */
            $services = app()->make(ProfitSharingServices::class);
            $services->handleOrderPaid($orderId);
        } catch (\Throwable $e) {
            Log::error('ProfitSharingJob失败:' . $e->getMessage());
        }
        return true;
    }
}
