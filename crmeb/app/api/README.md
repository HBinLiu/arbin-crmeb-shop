# api 目录结构说明

## 目录结构

```
.
├── config/                  # 配置目录
├── controller/              # 控制器目录
├── lang/                    # 语言包目录
├── middleware/              # 中间件目录
├── route/                   # 路由配置目录
├── validate/                # 验证器目录
├── ApiExceptionHandle.php   # 异常处理器
├── common.php               # 公共方法
├── event.php                # 事件配置
└── provider.php             # 服务提供者
```

## 目录说明

- **config/** - 移动端API专用配置
- **controller/** - 移动端控制器，处理移动端业务逻辑
- **lang/** - 移动端多语言文件
- **middleware/** - 移动端中间件，如用户认证、请求日志等
- **route/** - 移动端路由配置
- **validate/** - 移动端数据验证器

## 功能说明

api模块专门用于处理移动端应用的API接口，包括：
- 用户注册登录
- 商品浏览购买
- 订单管理
- 支付功能
- 个人中心等移动端功能