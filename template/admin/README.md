# CRMEB Admin

## 项目简介

CRMEB Admin 是 CRMEB 开源商城系统的管理端前端项目，基于 Vue.js + Element UI 开发，提供了完整的后台管理功能，包括商品管理、订单管理、用户管理、营销活动等。

## 目录结构

```
admin/
├── public/        # 静态资源目录
├── src/           # 源代码目录
│   ├── api/       # API 接口定义（按模块组织）
│   ├── assets/    # 静态资源文件
│   ├── components/ # 公共组件
│   ├── i18n/       # 多语言配置
│   ├── layouts/    # 布局组件
│   ├── libs/       # 工具库
│   ├── pages/      # 页面组件（按模块组织）
│   ├── router/     # 路由配置
│   ├── store/      # 状态管理
│   ├── styles/     # 样式文件
│   ├── utils/      # 工具函数
│   ├── App.vue     # 根组件
│   └── main.js     # 入口文件
├── .env.dev       # 开发环境配置
├── .env.production # 生产环境配置
├── package.json   # 项目依赖
├── vue.config.js  # Vue 配置
└── README.md      # 项目说明
```

## 核心模块

- **product**: 商品管理（商品列表、分类、规格等）
- **order**: 订单管理（订单列表、详情、状态管理等）
- **user**: 用户管理（会员列表、分组、标签等）
- **marketing**: 营销管理（优惠券、拼团、砍价、秒杀等）
- **finance**: 财务管理（资金记录、提现申请等）
- **setting**: 系统设置（权限管理、菜单管理、系统配置等）
- **cms**: 内容管理（文章、分类等）
- **app**: 应用管理（小程序、公众号等）

## 开发规范

### 命名规范
- **文件/目录**: 小驼峰命名法（如：userList）
- **组件/类名**: 大驼峰命名法（如：AddUser）
- **变量**: 小驼峰命名法（如：userInfo）
- **常量**: 全大写下划线命名法（如：VUE_APP_API_URL）

### 代码规范
- 使用 ES6+ 语法
- 方法和关键代码添加注释
- 页面模块必须用文件夹区分
- API 接口一个模块一个文件
- 组件一个组件一个文件夹
- 路由和状态管理按模块组织

## 开发与部署

### 开发环境

```bash
# 进入项目目录
cd admin

# 安装依赖
npm install

# 启动开发服务器
npm run dev
```

### 生产环境构建

```bash
# 构建生产版本
npm run build

# 构建产物位于 dist 目录
```

## 配置说明

### API 地址配置

#### 开发环境
修改 `.env.dev` 文件：

```env
VUE_APP_API_URL='http://localhost:8080/adminapi'
```

#### 生产环境
修改 `.env.production` 文件：

```env
VUE_APP_API_URL='https://your-domain.com/adminapi'
```

## 技术栈

- **框架**: Vue.js 2.x
- **UI 组件库**: Element UI
- **状态管理**: Vuex
- **路由**: Vue Router
- **网络请求**: Axios
- **构建工具**: Vue CLI 4.x

## 注意事项

1. **API 接口**: 确保前端 API 调用与后端接口保持一致
2. **权限管理**: 遵循系统权限控制规则，不要绕过权限验证
3. **性能优化**: 合理使用组件懒加载、图片优化等技术
4. **代码质量**: 保持代码整洁，添加必要的注释
5. **安全性**: 注意数据加密、输入验证等安全问题

## 联系与支持

- **官方文档**: https://doc.crmeb.com
- **技术社区**: https://www.crmeb.com/ask
- **GitHub**: https://github.com/crmeb/CRMEB
- **Gitee**: https://gitee.com/ZhongBangKeJi/CRMEB
