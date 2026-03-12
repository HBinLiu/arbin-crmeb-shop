# crmeb 核心目录结构说明

## 目录结构

```
.
├── basic/                   # 基础类库
│   ├── BaseController.php   # 控制器基类
│   ├── BaseJobs.php         # 队列基类
│   ├── BaseManager.php      # 驱动管理基类
│   ├── BaseModel.php        # Model基类
│   └── BaseStorage.php      # 驱动基类
├── command/                 # 命令行工具
│   ├── stubs/               # 命令模板
│   ├── Dao.php              # 创建Dao类命令
│   ├── Service.php          # 创建Service类命令
│   ├── Timer.php            # 定时任务命令类
│   └── Workerman.php        # Workerman命令类
├── exceptions/              # 异常处理类
│   ├── AdminException.php   # 后台管理端异常
│   ├── ApiException.php     # 移动端接口异常
│   ├── AuthException.php    # 用户授权异常
│   ├── PayException.php     # 支付异常
│   ├── SmsException.php     # 短信异常
│   ├── TemplateException.php # 微信模板消息异常
│   ├── UploadException.php  # 上传异常
│   └── WechatReplyException.php # 微信自动回复异常
├── interfaces/              # 接口定义
│   ├── JobInterface.php     # 队列任务接口
│   ├── ListenerInterface.php # 事件接口
│   ├── MiddlewareInterface.php # 中间件接口
│   └── ProviderInterface.php # 容器接口
├── services/                # 服务类
│   ├── app/                 # 应用端服务
│   ├── easywechat/          # EasyWechat集成
│   ├── express/             # 物流查询服务
│   ├── pay/                 # 支付服务
│   ├── printer/             # 打印机服务
│   ├── copyproduct/         # 商品采集服务
│   ├── serve/               # 服务平台服务
│   ├── sms/                 # 短信服务
│   ├── template/            # 模板消息服务
│   ├── upload/              # 上传服务
│   ├── workerman/           # Workerman服务
│   ├── AccessTokenServeService.php # Token获取服务
│   ├── AliPayService.php    # 支付宝服务
│   ├── CacheService.php     # 缓存服务
│   ├── FileService.php      # 文件处理服务
│   ├── FormBuilder.php      # 表单生成器
│   ├── GroupDataService.php # 组合数据服务
│   ├── HttpService.php      # HTTP请求服务
│   ├── MysqlBackupService.php # 数据库备份服务
│   ├── SpreadsheetExcelService.php # Excel处理服务
│   ├── SystemConfigService.php # 系统配置服务
│   ├── UpgradeService.php   # 系统更新服务
│   └── UploadService.php    # 文件上传服务
├── traits/                  # Trait扩展
│   ├── ErrorTrait.php       # 错误处理扩展
│   ├── JwtAuthModelTrait.php # JWT鉴权扩展
│   ├── ModelTrait.php       # Model扩展
│   ├── QueueTrait.php       # 队列扩展
│   ├── SearchDaoTrait.php   # DAO扩展
│   └── ServicesTrait.php    # Services扩展
└── utils/                   # 工具类
    ├── Arr.php              # 数组操作工具
    ├── Canvas.php           # 图片处理工具
    ├── Captcha.php          # 验证码工具
    ├── DownloadImage.php    # 远程图片下载工具
    ├── ErrorCode.php        # 错误码处理工具
    ├── Hook.php             # 钩子工具
    ├── Json.php             # JSON处理工具
    ├── JwtAuth.php          # JWT鉴权工具
    ├── QRcode.php           # 二维码工具
    ├── Queue.php            # 队列工具
    └── Str.php              # 字符串处理工具
```

## 目录说明

- **basic/** - 基础类库，提供各种基类供业务模块继承
- **command/** - 命令行工具，用于生成代码和系统维护
- **exceptions/** - 自定义异常类，提供统一的异常处理
- **interfaces/** - 接口定义，规范各模块的实现
- **services/** - 核心服务类，封装各种第三方服务和业务逻辑
- **traits/** - Trait扩展，提供可复用的代码片段
- **utils/** - 工具类，提供各种实用功能
