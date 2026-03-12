# config 目录结构说明

## 目录结构

```
:.
├── ajcaptcha.php            # 滑块验证码配置
├── app.php                 # 应用基础配置
├── cache.php               # 缓存配置
├── captcha.php             # 验证码配置
├── console.php             # 命令行配置
├── cookie.php              # Cookie配置
├── database.php            # 数据库配置
├── filesystem.php          # 文件系统配置
├── lang.php                # 多语言配置
├── log.php                 # 日志配置
├── pay.php                 # 支付配置
├── plat.php                # 对接平台配置
├── printer.php             # 小票打印机配置
├── qrcode.php              # 二维码配置
├── queue.php               # 队列配置
├── route.php               # 路由配置
├── session.php             # Session配置
├── sms.php                 # 短信配置
├── trace.php               # 调试配置
├── upload.php              # 上传配置
├── view.php                # 模板配置
├── workerman.php           # Workerman配置
└── README.md              # 目录说明文件
```

## 文件说明

- **ajcaptcha.php** - 滑块验证码配置，用于行为验证
- **app.php** - 应用基础配置，包括时区、字符集、应用模式等
- **cache.php** - 缓存驱动配置，支持Redis、Memcached、文件等
- **captcha.php** - 验证码生成和验证配置
- **console.php** - 命令行应用配置
- **cookie.php** - Cookie配置，包括域名、路径、有效期等
- **database.php** - 数据库连接和查询配置，支持MySQL、SQLite等
- **filesystem.php** - 本地存储和云存储配置
- **lang.php** - 多语言配置，默认语言、语言包路径等
- **log.php** - 日志配置，记录方式、级别、路径等
- **pay.php** - 支付接口配置，包括微信支付、支付宝等
- **plat.php** - 对接平台配置，如小程序、公众号等
- **printer.php** - 小票打印机配置，连接方式、打印模板等
- **qrcode.php** - 二维码生成配置
- **queue.php** - 消息队列配置，支持Redis、数据库、Sync驱动
- **route.php** - 路由配置，URL路由规则、域名部署等
- **session.php** - Session配置，驱动、过期时间等
- **sms.php** - 短信服务配置，支持腾讯云、阿里云等
- **trace.php** - 调试配置，页面Trace显示
- **upload.php** - 文件上传配置，大小限制、保存路径等
- **view.php** - 模板引擎配置，模板路径、缓存配置等
- **workerman.php** - Workerman WebSocket服务配置

## 功能说明

config目录包含系统所有的配置文件：

- **应用配置** - 定义应用的基础运行参数
- **数据存储** - 数据库、缓存、文件存储配置
- **第三方服务** - 支付、短信、验证码配置
- **路由与安全** - 路由规则、跨域、认证配置
- **开发调试** - 日志、调试、Trace配置

修改配置文件后需要清除缓存或重启服务生效。
