# app 目录结构说明

## 目录结构

```
.
├── adminapi/                 # 后台管理端API接口
├── api/                      # 移动端API接口
├── dao/                      # DAO层（数据访问对象）
├── http/                     # HTTP中间件
├── jobs/                     # 队列任务
├── kefuapi/                  # 客服端API接口
├── lang/                     # 语言包
├── listener/                 # 事件监听器目录
├── model/                    # Model层
├── services/                 # Services层
├── subscribes/               # 事件订阅
├── AppService.php           # 应用服务类
├── ExceptionHandle.php      # 异常处理器
├── Request.php              # 封装Request类
├── build.php                # 构建配置
├── common.php               # 公共方法
├── event.php                # 事件配置
├── middleware.php           # 中间件配置
├── provider.php             # 容器Provider定义文件
└── service.php              # 服务配置
```

## 目录说明

- **adminapi/** - 后台管理端API接口模块
- **api/** - 移动端API接口模块
- **dao/** - 数据访问对象层，负责数据库操作
- **http/** - HTTP中间件相关文件
- **jobs/** - 异步队列任务处理
- **kefuapi/** - 客服系统API接口
- **lang/** - 多语言包文件
- **listener/** - 事件监听器，处理系统事件
- **model/** - 数据模型层，定义数据结构和业务逻辑
- **services/** - 业务服务层，封装复杂业务逻辑
- **subscribes/** - 事件订阅器