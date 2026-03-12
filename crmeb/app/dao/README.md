# dao 目录结构说明

## 目录结构

```
.
├── activity/                # 营销活动相关DAO
│   ├── advance/             # 预售活动
│   ├── bargain/             # 砍价活动
│   ├── combination/         # 拼团活动
│   ├── coupon/              # 优惠券
│   ├── integral/            # 积分活动
│   ├── live/                # 直播活动
│   ├── lottery/             # 抽奖活动
│   └── seckill/             # 秒杀活动
├── agent/                   # 分销代理相关DAO
│   ├── AgentLevelDao.php    # 代理等级
│   ├── AgentLevelTaskDao.php # 代理等级任务
│   ├── AgentLevelTaskRecordDao.php # 代理等级任务记录
│   └── DivisionAgentApplyDao.php # 分销代理申请
├── article/                 # 文章相关DAO
│   ├── ArticleCategoryDao.php # 文章分类
│   ├── ArticleContentDao.php # 文章内容
│   └── ArticleDao.php       # 文章主表
├── diy/                     # 自定义页面相关DAO
│   ├── DiyDao.php           # DIY页面
│   ├── PageCategoryDao.php  # 页面分类
│   └── PageLinkDao.php      # 页面链接
├── order/                   # 订单相关DAO
│   ├── DeliveryServiceDao.php # 配送服务
│   ├── OtherOrderDao.php    # 其他订单
│   ├── OtherOrderStatusDao.php # 其他订单状态
│   ├── StoreCartDao.php     # 购物车
│   ├── StoreOrderCartInfoDao.php # 订单商品信息
│   ├── StoreOrderDao.php    # 订单主表
│   ├── StoreOrderEconomizeDao.php # 订单节省金额
│   ├── StoreOrderInvoiceDao.php # 订单发票
│   ├── StoreOrderRefundDao.php # 订单退款
│   ├── StoreOrderStatusDao.php # 订单状态
│   ├── StoreOrderStoreOrderCartInfoDao.php # 订单商品关联
│   └── StoreOrderStoreOrderStatusDao.php # 订单状态关联
├── other/                   # 其他功能DAO
│   ├── AgreementDao.php     # 协议
│   ├── AuxiliaryDao.php     # 辅助数据
│   ├── CacheDao.php         # 缓存
│   ├── CategoryDao.php      # 分类
│   ├── QrcodeDao.php        # 二维码
│   └── TemplateMessageDao.php # 模板消息
├── product/                 # 商品相关DAO
│   ├── product/             # 商品主表
│   └── sku/                 # SKU管理
├── service/                 # 客服相关DAO
│   ├── StoreServiceAuxiliaryDao.php # 客服辅助
│   ├── StoreServiceDao.php  # 客服主表
│   ├── StoreServiceFeedbackDao.php # 客服反馈
│   ├── StoreServiceLogDao.php # 客服日志
│   ├── StoreServiceRecordDao.php # 客服记录
│   └── StoreServiceSpeechcraftDao.php # 客服话术
├── shipping/                # 物流配送相关DAO
│   ├── ExpressDao.php       # 快递公司
│   ├── ShippingTemplatesDao.php # 运费模板
│   ├── ShippingTemplatesFreeCityDao.php # 运费模板免费城市
│   ├── ShippingTemplatesFreeDao.php # 运费模板免费设置
│   ├── ShippingTemplatesNoDeliveryCityDao.php # 运费模板不配送城市
│   ├── ShippingTemplatesNoDeliveryDao.php # 运费模板不配送设置
│   ├── ShippingTemplatesRegionCityDao.php # 运费模板区域城市
│   ├── ShippingTemplatesRegionDao.php # 运费模板区域
│   └── SystemCityDao.php    # 系统城市
├── sms/                     # 短信相关DAO
│   ├── SmsAdminDao.php      # 短信管理
│   └── SmsRecordDao.php     # 短信记录
├── system/                  # 系统相关DAO
│   ├── admin/               # 管理员
│   ├── attachment/          # 附件
│   ├── config/              # 配置
│   ├── log/                 # 日志
│   ├── statistics/          # 统计
│   ├── store/               # 店铺
│   ├── AppVersionDao.php    # 应用版本
│   ├── MessageSystemDao.php # 系统消息
│   ├── SystemMenusDao.php   # 系统菜单
│   ├── SystemNotificationDao.php # 系统通知
│   └── SystemUserLevelDao.php # 系统用户等级
├── user/                    # 用户相关DAO
│   ├── MemberCardBatchDao.php # 会员卡批次
│   ├── MemberCardDao.php    # 会员卡
│   ├── MemberRightDao.php   # 会员权益
│   ├── MemberShipDao.php    # 会员关系
│   ├── UserAddressDao.php   # 用户地址
│   ├── UserAuthDao.php      # 用户认证
│   ├── UserBillDao.php      # 用户账单
│   ├── UserBillStoreOrderDao.php # 用户账单订单关联
│   ├── UserBrokerageDao.php # 用户佣金
│   ├── UserBrokerageFrozenDao.php # 用户冻结佣金
│   ├── UserCancelDao.php    # 用户注销
│   ├── UserDao.php          # 用户主表
│   ├── UserExtractDao.php   # 用户提现
│   ├── UserFriendsDao.php   # 用户好友
│   ├── UserGroupDao.php     # 用户分组
│   ├── UserInvoiceDao.php   # 用户发票
│   ├── UserLabelCateDao.php # 用户标签分类
│   ├── UserLabelDao.php     # 用户标签
│   ├── UserLabelRelationDao.php # 用户标签关系
│   ├── UserLevelDao.php     # 用户等级
│   ├── UserMoneyDao.php     # 用户余额
│   ├── UserRechargeDao.php  # 用户充值
│   ├── UserSearchDao.php    # 用户搜索
│   ├── UserSignDao.php      # 用户签到
│   ├── UserSpreadDao.php    # 用户推广
│   ├── UserStoreOrderDao.php # 用户订单关联
│   ├── UserTaskFinishDao.php # 用户任务完成
│   ├── UserUserBillDao.php  # 用户账单关联
│   ├── UserUserBrokerageDao.php # 用户佣金关联
│   ├── UserVisitDao.php     # 用户访问
│   └── UserWechatUserDao.php # 用户微信关联
├── wechat/                  # 微信相关DAO
│   ├── WechatKeyDao.php     # 微信密钥
│   ├── WechatMediaDao.php   # 微信媒体
│   ├── WechatMenuDao.php    # 微信菜单
│   ├── WechatMessageDao.php # 微信消息
│   ├── WechatNewsCategoryDao.php # 微信图文分类
│   ├── WechatQrcodeCateDao.php # 微信二维码分类
│   ├── WechatQrcodeDao.php  # 微信二维码
│   ├── WechatQrcodeRecordDao.php # 微信二维码记录
│   ├── WechatReplyDao.php   # 微信回复
│   ├── WechatReplyKeyDao.php # 微信回复关键词
│   └── WechatUserDao.php    # 微信用户
├── BaseDao.php              # DAO基类
└── readme.md             # 目录说明文件
```

## 功能说明

dao（数据访问对象）层负责数据库操作，封装了所有业务模块的数据访问逻辑：

- **活动模块** - 管理各种营销活动的数据访问
- **代理模块** - 处理分销代理相关的数据操作
- **订单模块** - 订单数据的增删改查
- **用户模块** - 用户信息管理和操作
- **商品模块** - 商品数据管理
- **系统模块** - 系统配置和数据管理

DAO层提供统一的数据访问接口，确保数据操作的一致性和安全性。