# CRMEB订单流程开发文档

## 1. 订单流程概述

CRMEB系统订单流程是一个完整的电商交易处理系统，涵盖从用户下单到订单完成的全生命周期管理。系统支持多种商品类型（普通商品、秒杀、拼团、砍价、预售）、多种支付方式和配送方式，并集成了推广佣金、退款处理等功能。

## 2. 核心架构技术方案

### 2.1 系统架构
- **MVC架构**: 基于ThinkPHP6框架
- **服务层设计**: 采用Services层处理业务逻辑，Dao层数据访问
- **队列处理**: 使用ThinkPHP队列处理异步任务（如自动评价、订单取消）
- **事务管理**: 关键操作使用数据库事务保证数据一致性

### 2.2 核心模块
- **订单管理**: StoreOrder模型和StoreOrderServices服务
- **购物车**: StoreCartServices处理购物车逻辑
- **支付处理**: 支持微信支付、支付宝、余额支付、线下支付
- **物流管理**: 快递发货、门店自提、虚拟发货
- **退款管理**: StoreOrderRefund处理退款申请和流程
- **推广佣金**: 多级分销体系，支持事业部、代理、员工分佣

## 3. 影响文件分析

### 3.1 核心模型文件
- `crmeb/app/model/order/StoreOrder.php` - 订单主模型，包含订单基础信息和关联关系
- `crmeb/app/model/order/StoreOrderStatus.php` - 订单状态变更记录
- `crmeb/app/model/order/StoreOrderRefund.php` - 退款订单模型
- `crmeb/app/model/order/StoreOrderCartInfo.php` - 订单商品信息模型

### 3.2 服务层文件
- `crmeb/app/services/order/StoreOrderServices.php` - 订单核心业务逻辑（3086行）
- `crmeb/app/services/pay/OrderPayServices.php` - 支付处理服务
- `crmeb/app/services/pay/PayNotifyServices.php` - 支付回调处理
- `crmeb/app/services/user/UserBillServices.php` - 用户账单处理

### 3.3 控制器文件
- `crmeb/app/adminapi/controller/order/` - 后台订单管理控制器
- `crmeb/app/api/controller/order/` - 前端订单API控制器

### 3.4 数据库表结构
- `eb_store_order` - 订单主表（72个字段）
- `eb_store_order_cart_info` - 订单商品明细表
- `eb_store_order_status` - 订单状态变更记录表
- `eb_store_order_refund` - 退款订单表

## 4. 实现细节

### 4.1 订单状态流转
```php
// 订单状态定义
const ORDER_STATUS = [
    -2 => '退货成功',
    -1 => '申请退款/已退款',
    0 => '待发货',
    1 => '待收货', 
    2 => '已收货',
    3 => '待评价'
];

// 支付状态
const PAY_STATUS = [
    0 => '未支付',
    1 => '已支付'
];

// 退款状态
const REFUND_STATUS = [
    0 => '未退款',
    1 => '申请中', 
    2 => '已退款'
];
```

### 4.2 订单创建流程
```php
public function createOrder($data) {
    // 1. 验证商品库存和价格
    $this->checkProductStock($data['cart_info']);
    
    // 2. 计算订单金额（含优惠券、积分抵扣）
    $orderData = $this->calculateOrderPrice($data);
    
    // 3. 生成订单号和唯一标识
    $orderData['order_id'] = $this->generateOrderId();
    $orderData['unique'] = md5(uniqid(mt_rand(), true));
    
    // 4. 处理优惠券使用
    if ($orderData['coupon_id']) {
        $this->useCoupon($orderData['coupon_id'], $orderData['uid']);
    }
    
    // 5. 扣减库存
    $this->reduceStock($data['cart_info']);
    
    // 6. 创建订单记录
    $order = $this->dao->save($orderData);
    
    // 7. 创建订单商品明细
    $this->createOrderCartInfo($order['id'], $data['cart_info']);
    
    // 8. 记录订单状态变更
    $this->recordOrderStatus($order['id'], 'create', '订单创建');
    
    return $order;
}
```

### 4.3 支付处理流程
```php
public function paySuccess($orderId, $payType, $tradeNo = '') {
    // 开启事务
    Db::startTrans();
    try {
        // 1. 更新订单支付状态
        $this->dao->update($orderId, [
            'paid' => 1,
            'pay_time' => time(),
            'pay_type' => $payType,
            'trade_no' => $tradeNo
        ]);
        
        // 2. 处理推广佣金
        $this->handleBrokerage($orderId);
        
        // 3. 增加用户积分
        $this->addUserIntegral($orderId);
        
        // 4. 更新会员等级和经验
        $this->updateUserLevel($orderId);
        
        // 5. 处理拼团逻辑
        if ($orderInfo['pink_id']) {
            app()->make(StorePinkServices::class)->handlePinkPay($orderInfo);
        }
        
        // 6. 发送消息通知
        $this->sendPaySuccessNotice($orderId);
        
        Db::commit();
        return true;
    } catch (\Exception $e) {
        Db::rollback();
        throw new \Exception('支付处理失败: ' . $e->getMessage());
    }
}
```

### 4.4 发货处理流程
```php
public function delivery($orderId, $deliveryData) {
    // 1. 验证订单状态
    $order = $this->dao->get($orderId);
    if (!$order || $order['paid'] != 1 || $order['status'] != 0) {
        throw new ApiException('订单状态不允许发货');
    }
    
    // 2. 处理不同发货类型
    switch ($deliveryData['delivery_type']) {
        case 'express': // 快递发货
            $updateData = [
                'delivery_name' => $deliveryData['delivery_name'],
                'delivery_id' => $deliveryData['delivery_id'],
                'delivery_type' => 'express',
                'status' => 1 // 待收货
            ];
            break;
            
        case 'send': // 商家配送
            $updateData = [
                'delivery_name' => $deliveryData['delivery_name'],
                'delivery_id' => $deliveryData['delivery_id'],
                'delivery_type' => 'send',
                'status' => 1,
                'delivery_uid' => $deliveryData['delivery_uid']
            ];
            break;
            
        case 'fictitious': // 虚拟发货
            $updateData = [
                'fictitious_content' => $deliveryData['fictitious_content'],
                'delivery_type' => 'fictitious',
                'status' => 2 // 直接完成
            ];
            break;
    }
    
    // 3. 更新订单状态
    $this->dao->update($orderId, $updateData);
    
    // 4. 记录订单状态变更
    $this->recordOrderStatus($orderId, 'delivery', '订单发货');
    
    // 5. 发送发货通知
    $this->sendDeliveryNotice($orderId);
    
    return true;
}
```

### 4.5 退款处理流程
```php
public function applyRefund($orderId, $refundData) {
    // 1. 验证订单和退款条件
    $order = $this->validateRefundCondition($orderId);
    
    // 2. 创建退款记录
    $refundData = [
        'store_order_id' => $orderId,
        'refund_type' => $refundData['refund_type'],
        'refund_price' => $refundData['refund_price'],
        'refund_reason' => $refundData['refund_reason'],
        'refund_reason_wap_explain' => $refundData['explain'] ?? '',
        'refund_reason_wap_img' => $refundData['images'] ?? '',
        'add_time' => time()
    ];
    
    $refund = app()->make(StoreOrderRefundServices::class)->save($refundData);
    
    // 3. 更新订单退款状态
    $this->dao->update($orderId, [
        'refund_status' => 1, // 申请中
        'refund_type' => $refundData['refund_type']
    ]);
    
    // 4. 记录订单状态变更
    $this->recordOrderStatus($orderId, 'apply_refund', '申请退款');
    
    // 5. 发送退款申请通知
    $this->sendRefundApplyNotice($orderId, $refund['id']);
    
    return $refund;
}
```

## 5. 边界条件与异常处理

### 5.1 库存管理
- **超卖防护**: 使用Redis分布式锁或数据库乐观锁防止超卖
- **库存回滚**: 订单取消或支付失败时自动回滚库存
- **预售商品**: 支持预售商品的库存管理

### 5.2 支付安全
- **重复支付防护**: 通过订单唯一标识防止重复支付
- **支付超时**: 未支付订单自动取消（可配置超时时间）
- **金额校验**: 支付回调时校验金额一致性

### 5.3 退款控制
- **退款时限**: 根据商品类型设置不同的退款时限
- **退款次数**: 限制同一订单的退款申请次数
- **权限控制**: 不同角色处理退款的权限控制

### 5.4 异常处理机制
```php
// 订单创建异常处理
try {
    $order = $this->createOrder($orderData);
} catch (\Exception $e) {
    // 记录错误日志
    Log::error('订单创建失败: ' . $e->getMessage(), ['data' => $orderData]);
    
    // 回滚已处理的数据
    if (isset($orderData['coupon_id'])) {
        $this->rollbackCoupon($orderData['coupon_id']);
    }
    
    throw new ApiException('订单创建失败，请重试');
}
```

## 6. 数据流动路径

### 6.1 订单创建数据流
```
用户下单 → 购物车验证 → 价格计算 → 订单生成 → 库存扣减 → 支付处理 → 订单确认
```

### 6.2 支付处理数据流
```
支付请求 → 支付网关 → 支付回调 → 订单状态更新 → 佣金计算 → 积分奖励 → 消息通知
```

### 6.3 发货处理数据流
```
发货操作 → 物流信息更新 → 订单状态变更 → 状态记录 → 用户通知 → 物流跟踪
```

### 6.4 退款处理数据流
```
退款申请 → 条件验证 → 退款记录 → 状态更新 → 退款处理 → 资金返还 → 完成通知
```

## 7. 预期成果

### 7.1 功能完整性
- ✅ 支持多种商品类型的订单处理
- ✅ 完整的订单状态流转机制
- ✅ 多种支付方式和配送方式
- ✅ 完善的退款处理流程
- ✅ 推广佣金自动计算
- ✅ 积分和会员等级体系

### 7.2 系统性能
- 订单处理响应时间 < 200ms
- 支持高并发订单创建（使用队列异步处理）
- 数据库查询优化，关键索引完备
- 缓存策略应用，减少数据库压力

### 7.3 安全性
- 防重复支付机制
- 订单数据加密存储
- 敏感信息脱敏处理
- 完整的日志审计

### 7.4 扩展性
- 模块化设计，便于功能扩展
- 插件化支付和物流接口
- 灵活的订单状态配置
- 支持多种业务场景定制

## 8. 技术要点总结

1. **数据一致性**: 关键操作使用数据库事务保证
2. **异步处理**: 耗时操作使用队列异步执行
3. **状态机**: 订单状态流转严格遵循状态机模式
4. **缓存策略**: 商品信息、用户信息等使用缓存提升性能
5. **日志记录**: 完整的操作日志便于问题排查
6. **异常处理**: 完善的异常处理和回滚机制
7. **监控告警**: 关键指标监控和异常告警机制

该订单流程设计具备高可用性、高性能、高安全性的特点，能够满足大型电商平台的业务需求。