# outapi 目录结构说明

## 目录结构

```
:.
├── config/                  # 配置目录
├── controller/              # 控制器目录
├── middleware/              # 中间件目录
├── route/                   # 路由配置目录
├── validate/                # 验证器目录
├── OutApiExceptionHandle.php # 异常处理器
├── provider.php             # 服务提供者
└── README.md               # 目录说明文件
```

## 目录说明

- **config/** - 外部API专用配置
- **controller/** - 外部API控制器
- **middleware/** - 外部API中间件
- **route/** - 外部API路由配置
- **validate/** - 外部API数据验证器
- **OutApiExceptionHandle.php** - 外部API异常处理器
- **provider.php** - 外部API服务提供者

## 控制器说明

- **AuthController.php** - 授权控制器，处理第三方授权
- **Login.php** - 登录控制器，处理外部登录
- **RefundOrder.php** - 退款订单控制器
- **StoreCategory.php** - 商品分类控制器
- **StoreCoupon.php** - 优惠券控制器
- **StoreOrder.php** - 订单控制器
- **StoreProduct.php** - 商品控制器
- **User.php** - 用户控制器
- **UserLevel.php** - 用户等级控制器

## 功能说明

outapi目录用于定义项目对外开放的接口：

- 是项目对合作第三方开放的API接口定义目录
- 这里的接口可以被第三方直接调用来获取数据或完成相关业务
- 与内部使用的API有区别：
  - 对外开放，不需要登录授权（部分接口）
  - 安全限制较严，只提供必要接口
  - 接口规范遵循RESTful原则
- 常见场景：
  - 第三方小程序/APP直接获取商品数据
  - 第三方商户后台系统同步订单信息
  - 小程序支付回调通知接口

使用这个目录定义外部接口可以：
- 实现与其他系统的深度集成
- 让更多场景能够使用CRMEB提供的能力
- 降低对第三方的侵入性，仅开放必要接口
