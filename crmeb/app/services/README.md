# services 目录结构说明

## 目录结构

```
.
├── activity/                # 营销活动服务
│   ├── advance/             # 预售活动服务
│   ├── bargain/             # 砍价活动服务
│   ├── combination/         # 拼团活动服务
│   ├── coupon/              # 优惠券服务
│   ├── integral/            # 积分活动服务
│   ├── live/                # 直播活动服务
│   ├── lottery/             # 抽奖活动服务
│   └── seckill/             # 秒杀活动服务
├── agent/                   # 分销代理服务
│   ├── AgentLevelServices.php # 代理等级服务
│   ├── AgentLevelTaskRecordServices.php # 代理等级任务记录服务
│   ├── AgentLevelTaskServices.php # 代理等级任务服务
│   ├── AgentManageServices.php # 代理管理服务
│   ├── DivisionAgentApplyServices.php # 分销代理申请服务
│   └── DivisionServices.php # 分销服务
├── article/                 # 文章服务
│   ├── ArticleCategoryServices.php # 文章分类服务
│   ├── ArticleContentServices.php # 文章内容服务
│   └── ArticleServices.php  # 文章服务
├── diy/                     # 自定义页面服务
│   ├── DiyServices.php      # DIY页面服务
│   ├── PageCategoryServices.php # 页面分类服务
│   └── PageLinkServices.php # 页面链接服务
├── kefu/                    # 客服服务
│   ├── service/             # 客服相关服务
│   ├── KefuServices.php     # 客服主服务
│   ├── LoginServices.php    # 客服登录服务
│   ├── ProductServices.php  # 客服商品服务
│   └── UserServices.php     # 客服用户服务
├── message/                 # 消息服务
│   ├── notice/              # 通知服务
│   ├── wechat/              # 微信消息服务
│   ├── MessageSystemServices.php # 系统消息服务
│   ├── NoticeService.php    # 通知服务
│   ├── SystemNotificationServices.php # 系统通知服务
│   └── TemplateMessageServices.php # 模板消息服务
├── order/                   # 订单服务
│   ├── DeliveryServiceServices.php # 配送服务
│   ├── OtherOrderServices.php # 其他订单服务
│   ├── OtherOrderStatusServices.php # 其他订单状态服务
│   ├── StoreCartServices.php # 购物车服务
│   ├── StoreOrderCartInfoServices.php # 订单商品信息服务
│   ├── StoreOrderComputedServices.php # 订单计算服务
│   ├── StoreOrderCreateServices.php # 订单创建服务
│   ├── StoreOrderDeliveryServices.php # 订单配送服务
│   ├── StoreOrderEconomizeServices.php # 订单节省服务
│   ├── StoreOrderInvoiceServices.php # 订单发票服务
│   ├── StoreOrderRefundServices.php # 订单退款服务
│   ├── StoreOrderServices.php # 订单主服务
│   ├── StoreOrderSplitServices.php # 订单拆分服务
│   ├── StoreOrderStatusServices.php # 订单状态服务
│   ├── StoreOrderStoreOrderCartInfoServices.php # 订单商品关联服务
│   ├── StoreOrderStoreOrderStatusServices.php # 订单状态关联服务
│   ├── StoreOrderSuccessServices.php # 订单成功服务
│   ├── StoreOrderTakeServices.php # 订单接单服务
│   ├── StoreOrderWapServices.php # WAP订单服务
│   └── StoreOrderWriteOffServices.php # 订单核销服务
├── other/                   # 其他服务
│   ├── export/              # 导出服务
│   ├── AgreementServices.php # 协议服务
│   ├── CacheServices.php    # 缓存服务
│   ├── CategoryServices.php # 分类服务
│   ├── PosterServices.php   # 海报服务
│   ├── QrcodeServices.php   # 二维码服务
│   └── UploadService.php    # 上传服务
├── out/                     # 外部服务
│   └── LoginServices.php    # 外部登录服务
├── pay/                     # 支付服务
│   ├── OrderOfflineServices.php # 线下支付服务
│   ├── OrderPayServices.php # 订单支付服务
│   ├── PayNotifyServices.php # 支付通知服务
│   ├── PayServices.php      # 支付主服务
│   ├── RechargeServices.php # 充值服务
│   └── YuePayServices.php   # 余额支付服务
├── pc/                      # PC端服务
│   ├── CartServices.php     # 购物车服务
│   ├── HomeServices.php     # 首页服务
│   ├── LoginServices.php    # 登录服务
│   ├── OrderServices.php    # 订单服务
│   ├── ProductServices.php  # 商品服务
│   ├── PublicServices.php   # 公共服务
│   └── UserServices.php     # 用户服务
├── product/                 # 商品服务
│   ├── product/             # 商品主服务
│   └── sku/                 # SKU服务
├── serve/                   # 服务平台服务
│   └── ServeServices.php    # 服务平台主服务
├── shipping/                # 物流配送服务
│   ├── ExpressServices.php  # 快递服务
│   ├── ShippingTemplatesFreeCityServices.php # 运费模板免费城市服务
│   ├── ShippingTemplatesFreeServices.php # 运费模板免费设置服务
│   ├── ShippingTemplatesNoDeliveryCityServices.php # 运费模板不配送城市服务
│   ├── ShippingTemplatesNoDeliveryServices.php # 运费模板不配送设置服务
│   ├── ShippingTemplatesRegionCityServices.php # 运费模板区域城市服务
│   ├── ShippingTemplatesRegionServices.php # 运费模板区域服务
│   ├── ShippingTemplatesServices.php # 运费模板主服务
│   └── SystemCityServices.php # 系统城市服务
├── statistic/               # 统计服务
│   ├── CapitalFlowServices.php # 资金流水统计
│   ├── OrderStatisticServices.php # 订单统计
│   ├── ProductStatisticServices.php # 商品统计
│   ├── TradeStatisticServices.php # 交易统计
│   └── UserStatisticServices.php # 用户统计
├── system/                  # 系统服务
│   ├── admin/               # 管理员服务
│   ├── attachment/          # 附件服务
│   ├── config/              # 配置服务
│   ├── log/                 # 日志服务
│   ├── store/               # 店铺服务
│   ├── AppVersionServices.php # 应用版本服务
│   ├── SystemAuthServices.php # 系统权限服务
│   ├── SystemClearServices.php # 系统清理服务
│   ├── SystemDatabackupServices.php # 系统备份服务
│   ├── SystemMenusServices.php # 系统菜单服务
│   └── SystemUserLevelServices.php # 系统用户等级服务
├── user/                    # 用户服务
│   ├── member/              # 会员服务
│   ├── LoginServices.php    # 登录服务
│   ├── UserAddressServices.php # 用户地址服务
│   ├── UserAuthServices.php # 用户认证服务
│   ├── UserBillServices.php # 用户账单服务
│   ├── UserBillStoreOrderServices.php # 用户账单订单关联服务
│   ├── UserBrokerageFrozenServices.php # 用户冻结佣金服务
│   ├── UserBrokerageServices.php # 用户佣金服务
│   ├── UserCancelServices.php # 用户注销服务
│   ├── UserExtractServices.php # 用户提现服务
│   ├── UserFriendsServices.php # 用户好友服务
│   ├── UserGroupServices.php # 用户分组服务
│   ├── UserInvoiceServices.php # 用户发票服务
│   ├── UserLabelCateServices.php # 用户标签分类服务
│   ├── UserLabelRelationServices.php # 用户标签关系服务
│   ├── UserLabelServices.php # 用户标签服务
│   ├── UserLevelServices.php # 用户等级服务
│   ├── UserMoneyServices.php # 用户余额服务
│   ├── UserRechargeServices.php # 用户充值服务
│   ├── UserSearchServices.php # 用户搜索服务
│   ├── UserServices.php     # 用户主服务
│   ├── UserSignServices.php # 用户签到服务
│   ├── UserSpreadServices.php # 用户推广服务
│   ├── UserStoreOrderServices.php # 用户订单关联服务
│   ├── UserTaskFinishServices.php # 用户任务完成服务
│   ├── UserUserBillServices.php # 用户账单关联服务
│   ├── UserUserBrokerageServices.php # 用户佣金关联服务
│   ├── UserVisitServices.php # 用户访问服务
│   └── UserWechatuserServices.php # 用户微信关联服务
├── wechat/                  # 微信服务
│   ├── RoutineServices.php  # 小程序服务
│   ├── WechatKeyServices.php # 微信密钥服务
│   ├── WechatMediaServices.php # 微信媒体服务
│   ├── WechatMenuServices.php # 微信菜单服务
│   ├── WechatMessageServices.php # 微信消息服务
│   ├── WechatNewsCategoryServices.php # 微信图文分类服务
│   ├── WechatQrcodeCateServices.php # 微信二维码分类服务
│   ├── WechatQrcodeRecordServices.php # 微信二维码记录服务
│   ├── WechatQrcodeServices.php # 微信二维码服务
│   ├── WechatReplyKeyServices.php # 微信回复关键词服务
│   ├── WechatReplyServices.php # 微信回复服务
│   ├── WechatServices.php   # 微信主服务
│   └── WechatUserServices.php # 微信用户服务
├── yihaotong/               # 易号通短信服务
│   ├── SmsAdminServices.php # 短信管理服务
│   ├── SmsRecordServices.php # 短信记录服务
│   └── SmsTemplateApplyServices.php # 短信模板申请服务
├── BaseServices.php         # 服务基类
└── readme.md             # 目录说明文件
```

## 功能说明

services层封装复杂的业务逻辑，提供统一的业务接口：

- **业务逻辑封装** - 将复杂业务逻辑封装为服务方法
- **事务管理** - 提供数据库事务管理
- **数据验证** - 业务数据验证和过滤
- **第三方服务集成** - 集成支付、短信、微信等第三方服务
- **数据统计** - 提供各种数据统计和分析功能

Services层是业务逻辑的核心，负责协调各个模块完成复杂的业务操作。