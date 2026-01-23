# CRMEB 订单开发流程文档

## 📋 需求背景
为了规范CRMEB系统的订单功能开发，提供完整的订单业务流程、技术实现规范和开发指导，确保开发团队能够高效、标准化地进行订单相关功能的开发与维护。

## 🏗️ 订单系统架构概览

### 整体架构模式
- **框架**: ThinkPHP 6.0 + 多应用模式
- **架构模式**: MVC + 三层架构（Controller → Service → DAO/Model）
- **请求流程**: 路由分发 → 中间件 → 控制器 → 服务层 → DAO层 → 数据库

### 核心模块结构
```
订单模块/
├── Controller层/
│   ├── 前端API: api/controller/v1/order/StoreOrderController.php
│   ├── 管理端API: api/controller/v1/admin/StoreOrderController.php
│   └── PC端API: api/controller/pc/OrderController.php
├── Service层/
│   ├── StoreOrderServices.php (核心订单服务)
│   ├── StoreOrderCreateServices.php (订单创建服务)
│   ├── StoreOrderComputedServices.php (订单计算服务)
│   ├── StoreOrderDeliveryServices.php (订单配送服务)
│   ├── StoreOrderRefundServices.php (订单退款服务)
│   ├── StoreOrderStatusServices.php (订单状态服务)
│   └── StoreOrderSuccessServices.php (订单成功服务)
├── DAO层/
│   └── StoreOrderDao.php (订单数据访问)
└── Model层/
    └── StoreOrder.php (订单模型)
```

## 🔄 订单业务流程详解

### 1. 订单创建流程
```
用户下单 → 购物车确认 → 订单计算 → 订单创建 → 库存扣减 → 支付处理
```

#### 1.1 订单确认页面 (`/order/confirm`)
**Controller**: `StoreOrderController::confirm()`
**Service**: `StoreOrderServices::getOrderConfirmData()`
**数据流转**:
```php
// 接收参数
$cartId, $new, $addressId, $shipping_type, $is_gift

// 业务处理
1. 验证购物车商品有效性
2. 计算商品价格、运费、优惠
3. 检查用户地址信息
4. 获取可用优惠券
5. 返回确认页面数据
```

#### 1.2 订单金额计算 (`/order/computed/:key`)
**Controller**: `StoreOrderController::computedOrder()`
**Service**: `StoreOrderComputedServices`
**核心计算逻辑**:
```php
// 计算参数
$addressId, $couponId, $payType, $useIntegral, $mark, $shipping_type

// 计算流程
1. 商品总价计算
2. 运费计算 (ShippingTemplatesServices)
3. 优惠券抵扣计算
4. 积分抵扣计算
5. 会员折扣计算
6. 最终支付金额确认
```

#### 1.3 订单创建 (`/order/create/:key`)
**Controller**: `StoreOrderController::create()`
**Service**: `StoreOrderCreateServices::createOrder()`
**关键步骤**:
```php
// 订单生成流程
1. 生成唯一订单ID (雪花算法)
2. 订单基本信息入库
3. 订单商品信息入库 (StoreOrderCartInfoServices)
4. 库存扣减 (StoreProductServices)
5. 优惠券使用记录
6. 积分扣减记录
7. 订单状态记录 (StoreOrderStatusServices)
8. 返回订单信息
```

### 2. 订单支付流程
```
选择支付方式 → 支付参数生成 → 第三方支付 → 支付回调 → 订单状态更新
```

#### 2.1 支付处理 (`/order/pay`)
**Service**: `OrderPayServices`
**支持支付方式**:
- 微信支付 (WechatPayServices)
- 支付宝支付 (AlipayPayServices) 
- 余额支付 (YuePayServices)
- 线下支付 (OrderOfflineServices)

#### 2.2 支付回调处理
```php
// 支付成功后处理流程
1. 验证回调签名
2. 更新订单支付状态 (paid=1, pay_time)
3. 扣减商品库存
4. 生成用户账单记录
5. 触发相关营销活动
6. 发送支付成功通知
```

### 3. 订单状态流转
```
待支付 → 已支付 → 待发货 → 待收货 → 已完成 → 已评价
      ↓
   已取消 → 已删除
      ↓
   退款中 → 已退款
```

#### 3.1 状态定义
```php
// StoreOrderServices 订单状态常量
const STATUS_UNPAID = 0;        // 待支付
const STATUS_PAID = 1;          // 已支付/待发货  
const STATUS_SHIPPED = 2;       // 待收货
const STATUS_RECEIVED = 3;      // 已完成
const STATUS_EVALUATE = 4;       // 已评价
const STATUS_PART_SHIPPED = 5;   // 部分发货
```

#### 3.2 状态变更服务
- `StoreOrderStatusServices`: 订单状态变更记录
- `StoreOrderSuccessServices`: 订单成功处理
- `StoreOrderTakeServices`: 订单收货处理

### 4. 订单退款流程
```
申请退款 → 审核处理 → 退款处理 → 状态更新 → 库存恢复
```

#### 4.1 退款申请
**Controller**: `StoreOrderController::refund_verify()`
**Service**: `StoreOrderRefundServices`

#### 4.2 退款处理类型
- 部分退款 (部分商品退款)
- 整单退款 (整个订单退款)
- 仅退款 (不退货)
- 退货退款 (需要退货)

### 5. 订单发货流程
```
订单发货 → 物流信息录入 → 状态更新 → 用户通知 → 物流跟踪
```

#### 5.1 发货类型
```php
// StoreOrderServices 发货类型
public $deliveryType = [
    'send' => '商家配送',           // 同城配送
    'express' => '快递配送',        // 快递发货
    'fictitious' => '虚拟发货',     // 虚拟商品
    'delivery_part_split' => '拆分部分发货', // 部分发货
    'delivery_split' => '拆分发货完成'       // 完成发货
];
```

#### 5.2 发货服务
- `StoreOrderDeliveryServices`: 发货处理
- `ExpressServices`: 物流信息查询

## 🗄️ 数据库设计

### 核心表结构

#### 订单主表 (eb_store_order)
```sql
CREATE TABLE `eb_store_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_id` varchar(32) NOT NULL COMMENT '订单号',
  `uid` int(11) NOT NULL COMMENT '用户ID',
  `real_name` varchar(32) DEFAULT NULL COMMENT '收货人姓名',
  `user_phone` varchar(16) DEFAULT NULL COMMENT '收货人电话',
  `user_address` varchar(255) DEFAULT NULL COMMENT '收货地址',
  `cart_id` varchar(255) DEFAULT NULL COMMENT '购物车ID',
  `total_num` int(11) DEFAULT '0' COMMENT '订单商品总数',
  `total_price` decimal(10,2) DEFAULT '0.00' COMMENT '订单总价',
  `total_postage` decimal(10,2) DEFAULT '0.00' COMMENT '邮费',
  `pay_price` decimal(10,2) DEFAULT '0.00' COMMENT '实际支付金额',
  `pay_postage` decimal(10,2) DEFAULT '0.00' COMMENT '支付邮费',
  `deduction_price` decimal(10,2) DEFAULT '0.00' COMMENT '抵扣金额',
  `coupon_id` int(11) DEFAULT '0' COMMENT '优惠券ID',
  `coupon_price` decimal(10,2) DEFAULT '0.00' COMMENT '优惠券金额',
  `paid` tinyint(1) DEFAULT '0' COMMENT '支付状态',
  `pay_time` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `pay_type` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `status` tinyint(1) DEFAULT '0' COMMENT '订单状态',
  `refund_status` tinyint(1) DEFAULT '0' COMMENT '退款状态',
  `refund_reason` varchar(255) DEFAULT NULL COMMENT '退款理由',
  `refund_reason_wap_img` varchar(255) DEFAULT NULL COMMENT '退款图片',
  `refund_reason_wap_explain` varchar(255) DEFAULT NULL COMMENT '退款用户说明',
  `refund_price` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_price_time` timestamp NULL DEFAULT NULL COMMENT '退款时间',
  `delivery_name` varchar(64) DEFAULT NULL COMMENT '快递公司',
  `delivery_id` varchar(100) DEFAULT NULL COMMENT '快递单号',
  `delivery_type` varchar(32) DEFAULT NULL COMMENT '发货方式',
  `delivery_uid` int(11) DEFAULT '0' COMMENT '配送员ID',
  `delivery_code` varchar(50) DEFAULT NULL COMMENT '快递公司编码',
  `store_id` int(11) DEFAULT '0' COMMENT '门店ID',
  `shipping_type` tinyint(1) DEFAULT '1' COMMENT '配送方式 1=快递 2=自提',
  `pick_up_code` varchar(50) DEFAULT NULL COMMENT '核销码',
  `mark` varchar(512) DEFAULT NULL COMMENT '备注',
  `unique` varchar(32) DEFAULT NULL COMMENT '唯一key(md5加密)用于查询',
  `remark` varchar(512) DEFAULT NULL COMMENT '管理员备注',
  `mer_id` int(11) DEFAULT '0' COMMENT '商户ID',
  `is_del` tinyint(1) DEFAULT '0' COMMENT '是否删除',
  `is_system_del` tinyint(1) DEFAULT '0' COMMENT '是否系统删除',
  `combination_id` int(11) DEFAULT '0' COMMENT '拼团产品ID',
  `pink_id` int(11) DEFAULT '0' COMMENT '拼团ID',
  `seckill_id` int(11) DEFAULT '0' COMMENT '秒杀产品ID',
  `bargain_id` int(11) DEFAULT '0' COMMENT '砍价产品ID',
  `order_type` varchar(32) DEFAULT '' COMMENT '订单类型',
  `activity_type` varchar(32) DEFAULT '' COMMENT '活动类型',
  `channel_type` tinyint(1) DEFAULT '0' COMMENT '渠道类型',
  `extension_type` varchar(32) DEFAULT '' COMMENT '扩展类型',
  `staff_id` int(11) DEFAULT '0' COMMENT '客服ID',
  `vip_price` decimal(10,2) DEFAULT '0.00' COMMENT '会员价格',
  `finance_status` tinyint(1) DEFAULT '0' COMMENT '财务状态',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `uid` (`uid`),
  KEY `paid` (`paid`),
  KEY `status` (`status`),
  KEY `is_del` (`is_del`),
  KEY `unique` (`unique`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
```

#### 订单商品表 (eb_store_order_cart_info)
```sql
CREATE TABLE `eb_store_order_cart_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL COMMENT '订单ID',
  `cart_id` int(11) DEFAULT NULL COMMENT '购物车ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `cart_num` int(11) NOT NULL COMMENT '购买数量',
  `unique` varchar(50) NOT NULL COMMENT '唯一ID',
  `product_name` varchar(255) NOT NULL COMMENT '产品名称',
  `image` varchar(255) DEFAULT NULL COMMENT '产品图片',
  `unit_name` varchar(32) DEFAULT NULL COMMENT '单位名',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '产品价格',
  `cost` decimal(10,2) DEFAULT '0.00' COMMENT '产品成本',
  `vip_price` decimal(10,2) DEFAULT '0.00' COMMENT '会员价格',
  `pay_price` decimal(10,2) DEFAULT '0.00' COMMENT '实际支付价格',
  `pay_postage` decimal(10,2) DEFAULT '0.00' COMMENT '实际支付邮费',
  `is_gift` tinyint(1) DEFAULT '0' COMMENT '是否是赠品',
  `cart_info` text COMMENT '购物车信息',
  `refund_num` int(11) DEFAULT '0' COMMENT '退款数量',
  `is_refund` tinyint(1) DEFAULT '0' COMMENT '是否退款',
  `refund_price` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oid` (`oid`),
  KEY `product_id` (`product_id`),
  KEY `unique` (`unique`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单商品表';
```

## 🔧 开发规范与最佳实践

### 1. Service层开发规范

#### 1.1 基础服务结构
```php
class StoreOrderServices extends BaseServices
{
    /**
     * 构造函数
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }
    
    /**
     * 业务方法示例
     * @param int $orderId 订单ID
     * @param array $data 请求数据
     * @return array
     * @throws ApiException
     */
    public function businessMethod(int $orderId, array $data): array
    {
        // 1. 参数验证
        if (!$orderId) {
            throw new ApiException('参数错误');
        }
        
        // 2. 数据查询
        $order = $this->dao->get($orderId);
        if (!$order) {
            throw new ApiException('订单不存在');
        }
        
        // 3. 业务逻辑处理
        // ...业务处理代码
        
        // 4. 数据更新
        $this->dao->update($orderId, $updateData);
        
        // 5. 返回结果
        return $result;
    }
}
```

#### 1.2 异常处理规范
```php
// 使用项目特定异常类
throw new ApiException('用户端错误信息', 400);
throw new AdminException('管理端错误信息', 500);

// 统一返回格式
return $this->success($data);
return $this->fail('错误信息');
```

### 2. DAO层开发规范

#### 2.1 基础DAO结构
```php
class StoreOrderDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrder::class;
    }
    
    /**
     * 订单搜索方法
     * @param array $where 搜索条件
     * @param bool $search 是否精确搜索
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search)
            ->when(isset($where['status']), function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['paid']), function ($query) use ($where) {
                $query->where('paid', $where['paid']);
            });
    }
}
```

### 3. Controller层开发规范

#### 3.1 基础Controller结构
```php
class StoreOrderController
{
    /**
     * @var StoreOrderServices
     */
    protected $services;
    
    /**
     * 构造函数依赖注入
     * @param StoreOrderServices $services
     */
    public function __construct(StoreOrderServices $services)
    {
        $this->services = $services;
    }
    
    /**
     * 接口方法示例
     * @param Request $request
     * @return mixed
     */
    public function apiMethod(Request $request)
    {
        // 1. 获取参数
        [$param1, $param2] = $request->postMore([
            'param1',
            ['param2', 'defaultValue']
        ], true);
        
        // 2. 调用服务层
        $result = $this->services->businessMethod($param1, $param2);
        
        // 3. 返回结果
        return app('json')->success($result);
    }
}
```

### 4. 参数验证规范

#### 4.1 Validate层验证
```php
// 在 validate/order/ 目录下创建验证类
class OrderValidate extends Validate
{
    protected $rule = [
        'order_id' => 'require|number',
        'status' => 'require|in:0,1,2,3',
        'pay_type' => 'require|in:wechat,alipay,balance'
    ];
    
    protected $message = [
        'order_id.require' => '订单ID不能为空',
        'order_id.number' => '订单ID必须是数字',
        'status.in' => '订单状态不正确'
    ];
}
```

## 🔍 关键业务场景实现

### 1. 库存管理
```php
// 订单创建时库存扣减
public function reduceStock(array $cartInfo)
{
    foreach ($cartInfo as $item) {
        $product = $this->productServices->get($item['product_id']);
        if ($product['stock'] < $item['cart_num']) {
            throw new ApiException('商品库存不足');
        }
        
        // 扣减库存
        $this->productServices->decStock($item['product_id'], $item['cart_num']);
        
        // 记录库存变更日志
        $this->productLogServices->createLog([
            'product_id' => $item['product_id'],
            'num' => -$item['cart_num'],
            'type' => 'order_reduce',
            'order_id' => $this->orderId
        ]);
    }
}

// 订单取消时库存恢复
public function restoreStock(array $cartInfo)
{
    foreach ($cartInfo as $item) {
        $this->productServices->incStock($item['product_id'], $item['cart_num']);
        
        // 记录库存恢复日志
        $this->productLogServices->createLog([
            'product_id' => $item['product_id'],
            'num' => $item['cart_num'],
            'type' => 'order_restore',
            'order_id' => $this->orderId
        ]);
    }
}
```

### 2. 价格计算引擎
```php
class OrderPriceCalculator
{
    public function calculate(array $params): array
    {
        // 1. 商品总价
        $totalPrice = $this->calculateProductTotal($params['cart_info']);
        
        // 2. 运费计算
        $postage = $this->calculatePostage($params['cart_info'], $params['address']);
        
        // 3. 优惠计算
        $discount = $this->calculateDiscount($params);
        
        // 4. 最终价格
        $payPrice = $totalPrice + $postage - $discount;
        
        return [
            'total_price' => $totalPrice,
            'postage' => $postage,
            'discount' => $discount,
            'pay_price' => $payPrice
        ];
    }
}
```

### 3. 订单状态机
```php
class OrderStateMachine
{
    const STATUS_TRANSITIONS = [
        self::STATUS_UNPAID => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAID => [self::STATUS_SHIPPED, self::STATUS_REFUNDING],
        self::STATUS_SHIPPED => [self::STATUS_RECEIVED, self::STATUS_REFUNDING],
        self::STATUS_RECEIVED => [self::STATUS_COMPLETED, self::STATUS_REFUNDING],
    ];
    
    public function canTransition(int $from, int $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? []);
    }
    
    public function transition(int $orderId, int $toStatus, string $reason = ''): bool
    {
        $order = $this->orderServices->get($orderId);
        
        if (!$this->canTransition($order['status'], $toStatus)) {
            throw new ApiException('状态流转不合法');
        }
        
        // 更新订单状态
        $this->orderServices->updateStatus($orderId, $toStatus);
        
        // 记录状态变更日志
        $this->statusServices->createLog([
            'order_id' => $orderId,
            'from_status' => $order['status'],
            'to_status' => $toStatus,
            'reason' => $reason
        ]);
        
        return true;
    }
}
```

## 📊 性能优化建议

### 1. 数据库优化
- 订单表按时间分表存储
- 建立复合索引优化查询性能
- 使用缓存减少数据库访问

### 2. 缓存策略
```php
// 订单信息缓存
$orderCacheKey = "order_info_{$orderId}";
$orderInfo = Cache::remember($orderCacheKey, 3600, function() use ($orderId) {
    return $this->dao->get($orderId);
});

// 订单状态缓存
$statusCacheKey = "order_status_{$orderId}";
$status = Cache::get($statusCacheKey);
```

### 3. 异步处理
```php
// 订单创建后的异步处理
public function afterOrderCreate(int $orderId)
{
    // 发送邮件通知
    MailJob::dispatch($orderId);
    
    // 更新用户等级
    UserLevelJob::dispatch($orderId);
    
    // 统计数据更新
    StatisticJob::dispatch($orderId);
}
```

## 🧪 测试规范

### 1. 单元测试
```php
class StoreOrderServicesTest extends TestCase
{
    public function testCreateOrder()
    {
        // 准备测试数据
        $orderData = [
            'uid' => 1,
            'cart_info' => [...],
            'address_id' => 1
        ];
        
        // 执行测试
        $result = $this->orderServices->createOrder($orderData);
        
        // 断言结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertNotEmpty($result['order_id']);
    }
}
```

### 2. 集成测试
```php
class OrderFlowTest extends TestCase
{
    public function testCompleteOrderFlow()
    {
        // 1. 创建订单
        $order = $this->createTestOrder();
        
        // 2. 支付订单
        $this->payOrder($order['order_id']);
        
        // 3. 发货
        $this->shipOrder($order['order_id']);
        
        // 4. 确认收货
        $this->confirmOrder($order['order_id']);
        
        // 5. 验证订单状态
        $finalOrder = $this->orderServices->get($order['order_id']);
        $this->assertEquals(StoreOrderServices::STATUS_COMPLETED, $finalOrder['status']);
    }
}
```

## 📝 开发检查清单

### 订单开发前检查
- [ ] 理解业务需求和订单流程
- [ ] 设计数据库表结构
- [ ] 确认状态流转规则
- [ ] 设计接口参数和返回格式

### 代码开发检查
- [ ] 遵循项目架构规范
- [ ] 实现完整的异常处理
- [ ] 添加必要的日志记录
- [ ] 实现参数验证
- [ ] 编写业务逻辑注释

### 测试验证检查
- [ ] 单元测试覆盖核心逻辑
- [ ] 集成测试验证完整流程
- [ ] 性能测试确保响应时间
- [ ] 边界条件测试

### 部署上线检查
- [ ] 数据库迁移脚本
- [ ] 缓存预热脚本
- [ ] 监控指标配置
- [ ] 回滚方案准备

## 🔮 扩展建议

### 1. 微服务拆分
- 订单服务独立部署
- 库存服务独立部署
- 支付服务独立部署

### 2. 消息队列集成
- 使用RabbitMQ/Redis队列处理异步任务
- 实现订单事件驱动架构

### 3. 分布式事务
- 使用Seata处理跨服务事务
- 实现最终一致性方案

### 4. 大数据分析
- 订单数据实时统计
- 用户行为分析
- 销售预测分析

这份文档涵盖了CRMEB订单系统的完整开发流程，为开发团队提供了详细的指导。在实际开发中，请根据具体业务需求进行相应的调整和扩展。