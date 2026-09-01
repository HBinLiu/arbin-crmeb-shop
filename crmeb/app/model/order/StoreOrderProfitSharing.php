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

namespace app\model\order;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 微信分账流水
 * Class StoreOrderProfitSharing
 * @package app\model\order
 */
class StoreOrderProfitSharing extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'store_order_profit_sharing';

    protected $updateTime = false;
}
