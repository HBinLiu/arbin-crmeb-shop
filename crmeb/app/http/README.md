# http 目录结构说明

## 目录结构

```
:.
├── middleware/              # 全局中间件目录
│   ├── AllowOriginMiddleware.php # 跨域请求中间件
│   └── BaseMiddleware.php   # 中间件基类
└── README.md               # 目录说明文件
```

## 目录说明

- **middleware/** - 全局HTTP中间件目录，包含跨域请求处理和中间件基类

## 功能说明

http目录包含应用全局的HTTP中间件：

- **跨域中间件** - 处理跨域请求，允许指定域名访问API
- **中间件基类** - 定义中间件的基础抽象类

这些中间件对所有应用模块生效，提供统一的请求处理和响应过滤功能。
