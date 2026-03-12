# adminapi 目录结构说明

## 目录结构

```
.
├── config/                  # 配置目录
├── controller/              # 控制器目录
├── lang/                    # 语言包目录
├── middleware/              # 中间件目录
├── route/                   # 路由配置目录
├── validate/                # 验证器目录
├── AdminApiExceptionHandle.php # 异常处理器
├── common.php               # 公共方法
├── event.php                # 事件配置
└── provider.php             # 服务提供者
```

## 目录说明

- **config/** - 后台管理端专用配置
- **controller/** - 后台管理控制器，处理管理端业务逻辑
- **lang/** - 后台管理端多语言文件
- **middleware/** - 后台管理中间件，如权限验证、日志记录等
- **route/** - 后台管理路由配置
- **validate/** - 后台管理数据验证器

## 功能说明

adminapi模块专门用于处理后台管理系统的API接口，包括：
- 用户权限管理
- 商品管理
- 订单处理
- 数据统计
- 系统设置等后台管理功能