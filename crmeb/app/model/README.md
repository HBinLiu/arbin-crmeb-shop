# model 目录结构说明

## 目录结构

```
.
├── activity/                # 营销活动相关Model
│   ├── advance/             # 预售活动
│   ├── bargain/             # 砍价活动
│   ├── combination/         # 拼团活动
│   ├── coupon/              # 优惠券
│   ├── integral/            # 积分活动
│   ├── live/                # 直播活动
│   ├── lottery/             # 抽奖活动
│   └── seckill/             # 秒杀活动
├── agent/                   # 分销代理相关Model
│   ├── AgentLevel.php       # 代理等级
│   ├── AgentLevelTask.php   # 代理等级任务
│   ├── AgentLevelTaskRecord.php # 代理等级任务记录
│   └── DivisionAgentApply.php # 分销代理申请
├── article/                 # 文章相关Model
│   ├── Article.php          # 文章主表
│   ├── ArticleCategory.php  # 文章分类
│   └── ArticleContent.php   # 文章内容
├── diy/                     # 自定义页面相关Model
│   ├── Diy.php              # DIY页面
│   ├── PageCategory.php     # 页面分类
│   └── PageLink.php         # 页面链接
├── order/                   # 订单相关Model
│   ├── DeliveryService.php  # 配送服务
│   ├── OtherOrder.php       # 其他订单
│   ├── OtherOrderStatus.php # 其他订单状态
│   ├── StoreCart.php        # 购物车
│   ├── StoreOrder.php       # 订单主表
│   ├── StoreOrderCartInfo.php # 订单商品信息
│   ├── StoreOrderEconomize.php # 订单节省金额
│   ├── StoreOrderInvoice.php # 订单发票
│   ├── StoreOrderRefund.php # 订单退款
│   ├── StoreOrderStatus.php # 订单状态
│   └── StorePink.php        # 拼团订单
├── other/                   # 其他功能Model
│   ├── Agreement.php        # 协议
│   ├── Auxiliary.php        # 辅助数据
│   ├── Cache.php            # 缓存
│   ├── Category.php         # 分类
│   ├── Express.php          # 快递
│   ├── Qrcode.php           # 二维码
│   └── TemplateMessage.php  # 模板消息
├── product/                 # 商品相关Model
│   ├── product/             # 商品主表
│   └── sku/                 # SKU管理
├── service/                 # 客服相关Model
│   ├── StoreService.php     # 客服主表
│   ├── StoreServiceFeedback.php # 客服反馈
│   ├── StoreServiceLog.php  # 客服日志
│   ├── StoreServiceRecord.php # 客服记录
│   └── StoreServiceSpeechcraft.php # 客服话术
├── shipping/                # 物流配送相关Model
│   ├── ShippingTemplates.php # 运费模板
│   ├── ShippingTemplatesFree.php # 运费模板免费设置
│   ├── ShippingTemplatesNoDelivery.php # 运费模板不配送设置
│   ├── ShippingTemplatesRegion.php # 运费模板区域
│   └── SystemCity.php       # 系统城市
├── sms/                     # 短信相关Model
│   └── SmsRecord.php        # 短信记录
├── system/                  # 系统相关Model
│   ├── admin/               # 管理员
│   ├── attachment/          # 附件
│   ├── config/              # 配置
│   ├── log/                 # 日志
│   ├── statistics/          # 统计
│   ├── store/               # 店铺
│   ├── AppVersion.php       # 应用版本
│   ├── MessageSystem.php    # 系统消息
│   ├── SystemMenus.php      # 系统菜单
│   ├── SystemNotification.php # 系统通知
│   ├── SystemUserLevel.php  # 系统用户等级
│   └── SystemUserTask.php   # 系统用户任务
├── user/                    # 用户相关Model
│   ├── MemberCard.php       # 会员卡
│   ├── MemberCardBatch.php  # 会员卡批次
│   ├── MemberRight.php      # 会员权益
│   ├── MemberShip.php       # 会员关系
│   ├── User.php             # 用户主表
│   ├── UserAddress.php      # 用户地址
│   ├── UserBill.php         # 用户账单
│   ├── UserBrokerage.php    # 用户佣金
│   ├── UserBrokerageFrozen.php # 用户冻结佣金
│   ├── UserCancel.php       # 用户注销
│   ├── UserExtract.php      # 用户提现
│   ├── UserFriends.php      # 用户好友
│   ├── UserGroup.php        # 用户分组
│   ├── UserInvoice.php      # 用户发票
│   ├── UserLabel.php        # 用户标签
│   ├── UserLabelCate.php    # 用户标签分类
│   ├── UserLabelRelation.php # 用户标签关系
│   ├── UserLevel.php        # 用户等级
│   ├── UserMoney.php        # 用户余额
│   ├── UserRecharge.php     # 用户充值
│   ├── UserSearch.php       # 用户搜索
│   ├── UserSign.php         # 用户签到
│   ├── UserSpread.php       # 用户推广
│   ├── UserTaskFinish.php   # 用户任务完成
│   └── UserVisit.php        # 用户访问
├── wechat/                  # 微信相关Model
│   ├── WechatKey.php        # 微信密钥
│   ├── WechatMedia.php      # 微信媒体
│   ├── WechatMessage.php    # 微信消息
│   ├── WechatNewsCategory.php # 微信图文分类
│   ├── WechatQrcode.php     # 微信二维码
│   ├── WechatQrcodeCate.php # 微信二维码分类
│   ├── WechatQrcodeRecord.php # 微信二维码记录
│   ├── WechatReply.php      # 微信回复
│   └── WechatUser.php       # 微信用户
└── readme.md             # 目录说明文件
```

## 功能说明

model层定义数据结构和业务逻辑，每个Model对应数据库中的一个表：

- **数据映射** - 将数据库表映射为PHP对象
- **业务逻辑** - 封装数据操作和业务规则
- **数据验证** - 提供数据验证和过滤功能
- **关联关系** - 定义表之间的关联关系

Model层是MVC架构中的核心组件，负责数据的持久化和业务逻辑处理。