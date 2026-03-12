# jobs 目录结构说明

## 目录结构

```
:.
├── notice/                  # 通知相关任务目录
│   ├── EnterpriseWechatJob.php # 企业微信通知任务
│   ├── PrintJob.php         # 小票打印任务
│   ├── SmsJob.php           # 短信通知任务
│   └── SyncMessageJob.php   # 同步消息任务
├── AgentJob.php             # 代理相关任务
├── AutoCommentJob.php       # 自动评论任务
├── CheckQueueJob.php        # 队列检查任务
├── LiveJob.php              # 直播相关任务
├── MiniOrderJob.php         # 小程序订单任务
├── OrderCreateAfterJob.php  # 订单创建后任务
├── OrderExpressJob.php      # 订单物流任务
├── OrderInvoiceJob.php      # 订单发票任务
├── OrderJob.php             # 订单处理主任务
├── OtherOrderJob.php        # 其他订单任务
├── OutPushJob.php           # 外部推送任务
├── PinkJob.php              # 拼团任务
├── PosterJob.php            # 海报生成任务
├── ProductCopyJob.php       # 商品复制任务
├── ProductLogJob.php        # 商品日志任务
├── ProductStockJob.php       # 商品库存任务
├── RefundOrderJob.php       # 退款订单任务
├── TakeOrderJob.php         # 接单任务
├── TaskJob.php              # 定时任务
├── TemplateJob.php          # 模板消息任务
├── TranslateJob.php        # 翻译任务
├── UnpaidOrderCancelJob.php # 未支付订单取消任务
├── UnpaidOrderSend.php      # 未支付订单发送任务
├── UpgradeJob.php           # 升级任务
├── UserJob.php              # 用户相关任务
└── README.md                # 目录说明文件
```

## 目录说明

- **notice/** - 通知相关任务，处理短信、微信、企业微信、小票打印等通知
- **AgentJob.php** - 代理相关异步任务
- **AutoCommentJob.php** - 自动评论任务
- **CheckQueueJob.php** - 队列检查任务
- **LiveJob.php** - 直播相关任务
- **MiniOrderJob.php** - 小程序订单任务
- **OrderCreateAfterJob.php** - 订单创建后的异步处理任务
- **OrderExpressJob.php** - 订单物流相关任务
- **OrderInvoiceJob.php** - 订单发票相关任务
- **OrderJob.php** - 订单主任务类
- **OtherOrderJob.php** - 其他类型订单任务
- **OutPushJob.php** - 外部系统推送任务
- **PinkJob.php** - 拼团活动相关任务
- **PosterJob.php** - 海报生成任务
- **ProductCopyJob.php** - 商品复制任务
- **ProductLogJob.php** - 商品日志记录任务
- **ProductStockJob.php** - 商品库存同步任务
- **RefundOrderJob.php** - 退款订单处理任务
- **TakeOrderJob.php** - 订单核销任务
- **TaskJob.php** - 定时任务调度
- **TemplateJob.php** - 模板消息发送任务
- **TranslateJob.php** - 商品翻译任务
- **UnpaidOrderCancelJob.php** - 未支付订单自动取消任务
- **UnpaidOrderSend.php** - 未支付订单提醒发送任务
- **UpgradeJob.php** - 系统升级任务
- **UserJob.php** - 用户相关异步任务

## 功能说明

jobs目录包含所有异步队列任务，用于处理耗时较长的操作：

- **通知类任务** - 发送短信、微信模板消息、企业微信通知、小票打印等
- **订单类任务** - 订单创建后的处理、未支付订单取消、物流查询、发票开具、核销等
- **商品类任务** - 商品复制、商品日志记录、库存同步、商品翻译等
- **用户类任务** - 用户相关异步操作
- **营销类任务** - 拼团活动处理、海报生成等
- **系统类任务** - 定时任务、系统升级、队列检查等

这些任务通过消息队列异步执行，提高系统响应速度和并发处理能力。
