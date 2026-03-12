# listener 目录结构说明

## 目录结构

```
:.
├── admin/                   # 管理员相关监听器目录
│   └── AdminLoginListener.php # 管理员登录监听器
├── http/                    # HTTP相关监听器目录
│   └── HttpEndListener.php # HTTP请求结束监听器
├── notice/                  # 通知相关监听器目录
│   ├── CustomNoticeListener.php # 自定义通知监听器
│   └── NoticeListener.php   # 通知监听器主类
├── order/                   # 订单相关监听器目录
│   ├── OrderCreateAfterListener.php # 订单创建后监听器
│   ├── OrderDeliveryListener.php # 订单配送监听器
│   ├── OrderPaySuccessListener.php # 订单支付成功监听器
│   ├── OrderRefundCancelAfterListener.php # 订单退款取消后监听器
│   ├── OrderRefundCreateAfterListener.php # 订单退款创建后监听器
│   ├── OrderShippingListener.php # 订单发货监听器
│   └── OrderTakeListener.php # 订单核销监听器
├── out/                     # 外部推送监听器目录
│   └── OutPushListener.php  # 外部推送监听器
├── pay/                     # 支付相关监听器目录
│   └── NotifyListener.php   # 支付回调监听器
├── queue/                   # 队列相关监听器目录
│   └── QueueStartListener.php # 队列开始监听器
├── user/                    # 用户相关监听器目录
│   ├── LoginListener.php    # 用户登录监听器
│   ├── RegisterListener.php # 用户注册监听器
│   ├── UserLevelListener.php # 用户等级监听器
│   └── UserVisitListener.php # 用户访问监听器
├── wechat/                  # 微信相关监听器目录
│   └── AuthListener.php     # 微信授权监听器
├── CustomEventListener.php  # 自定义事件监听器
└── README.md               # 目录说明文件
```

## 目录说明

- **admin/** - 管理员相关事件监听器
- **http/** - HTTP请求生命周期事件监听器
- **notice/** - 通知发送事件监听器
- **order/** - 订单状态变化事件监听器
- **out/** - 外部推送事件监听器
- **pay/** - 支付回调事件监听器
- **queue/** - 队列执行事件监听器
- **user/** - 用户行为事件监听器
- **wechat/** - 微信相关事件监听器
- **CustomEventListener.php** - 自定义事件监听器

## 功能说明

listener目录包含事件监听器，用于响应系统事件：

- **HTTP事件监听** - 监听HTTP请求生命周期事件，如请求结束
- **订单事件监听** - 监听订单创建、支付成功、配送、发货、退款、核销等状态变化
- **用户事件监听** - 监听用户登录、注册、等级变化、访问记录等
- **通知事件监听** - 监听通知发送事件，触发短信、微信模板消息等
- **支付事件监听** - 监听支付回调事件，处理支付结果
- **微信事件监听** - 监听微信授权、OAuth等事件
- **队列事件监听** - 监听队列执行相关事件
- **管理员事件监听** - 监听管理员登录等事件
- **外部推送事件监听** - 监听外部系统推送事件

监听器采用观察者模式，实现系统解耦和异步处理，提高代码的可维护性和扩展性。
