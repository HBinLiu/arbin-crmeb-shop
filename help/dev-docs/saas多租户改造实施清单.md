# SaaS 多租户改造 — 实施详细清单

> 依据：[saas多租户改造方案.md](./saas多租户改造方案.md)  
> 用法：按阶段打勾推进；子项尽量可验收  
> 状态：待开工

---

## 总览（阶段依赖）

```text
P0 基建（表/Scope/平台登录）
 → P1 平台壳（租户CRUD/切商户/进入租户）
 → P2 微信 Token + 人工绑 appid
 → P3 配置归属迁移（全局 / 切商户 / 租户裁剪）
 → P4 业务数据隔离（主链路 + 附件）
 → P5 支付服务商按租户 + 分账全局化
 → P6 跨租户报表
 → P7 砍掉事业部 + 菜单清理 + 联调验收
```

并行提示：`wxcomponent` 部署与 P2 可并行；P3 前端与 P4 后端隔离可部分并行。

---

## P0 — 基建（必须先做）

### P0.1 数据库

- [ ] 新建表 `eb_platform_admin`（账号、密码哈希、状态、登录时间等）
- [ ] 新建表 `eb_tenant`（`code`/`name`/`status`/`expire_at`/`remark` 等）
- [ ] 新建表 `eb_tenant_wechat_authorizer`（`tenant_id`, `authorizer_appid`, `app_type` mp|mini, `nickname`, `status`, `bound_at`…；唯一约束 `authorizer_appid`）
- [ ] 编写增量 SQL：`crmeb/update/saas_tenant_init.sql`（含默认平台超管初始化策略）
- [ ] 明确配置存储策略草案并落文档注释：
  - 全局配置：沿用或迁到「无 tenant」的 `system_config` / 独立 `platform_config`
  - 按租户配置：`system_config` 加 `tenant_id`，或 `tenant_config(tenant_id, menu_name, value)`
- [ ] 业务核心表加 `tenant_id` 的迁移脚本框架（可按模块分批，见 P4；P0 至少定字段类型/默认值/索引规范）

**验收**：SQL 可在空库/现库执行；表结构与方案一致。

### P0.2 租户上下文（后端核心）

- [ ] 实现 `TenantContext`（或 Request 宏）：`getTenantId()` / `setTenantId()` / `isPlatform()` / `clear()`
- [ ] `adminapi`：登录成功后写入管理员所属 `tenant_id`
- [ ] `adminapi` 鉴权中间件：强制存在 `tenant_id`，注入上下文
- [ ] `api`（C 端）：从请求解析 appid（header / 小程序配置）→ 查 `tenant_wechat_authorizer` → 注入 `tenant_id`；未绑定时明确错误码
- [ ] `platformapi`：默认**不**注入业务 `tenant_id`；仅「切商户」接口/中间件临时设置
- [ ] BaseModel / 全局查询 Scope：业务模型默认 `where tenant_id = ?`（平台上下文可跳过或显式指定）
- [ ] 写入事件：自动填充 `tenant_id`（禁止客户端随意传其他租户）

**验收**：单测或手工：租户 A Token 读不到租户 B 数据；无 tenant 上下文访问业务接口失败。

### P0.3 平台 API 应用骨架

- [ ] 新建应用 `crmeb/app/platformapi/`（参照 `adminapi`：route、middleware、controller、validate）
- [ ] 注册路由前缀 `/platformapi`
- [ ] Nginx / 站点伪静态增加 `/platformapi` 转发（与现有 `adminapi` 同级）
- [ ] `PlatformAuthTokenMiddleware` + 登录签发 JWT（存储键与 admin 分离，如 `platform_token`）
- [ ] `Login`：登录 / 登出 / 信息 / 改密
- [ ] CORS、权限异常处理与 `adminapi` 对齐

**验收**：`POST /platformapi/login` 可登录；错误账号锁定策略可后置。

### P0.4 平台前端骨架

- [ ] 新建 `template/platform/`（可从 `template/admin` 精简拷贝：登录页 + 布局 + 请求封装）
- [ ] `vue`/`vite` 或沿用现 admin 构建链；`publicPath` / `base` 设为 `/platform/`
- [ ] 构建产物输出到 `crmeb/public/platform/`
- [ ] Nginx：`/platform` → `public/platform`；history/hash 路由按选定方案配置
- [ ] axios baseURL：`/platformapi`
- [ ] Token 存独立 key，避免与 `/admin` 串登录态

**验收**：浏览器打开 `/platform` 可见登录页并调通登录。

---

## P1 — 平台壳：租户与切商户

### P1.1 租户管理 API + 页

- [ ] `platformapi`：租户列表（分页/搜索/状态）
- [ ] 创建租户（生成 `code`、初始状态）
- [ ] 编辑 / 启用 / 停用 / 到期时间
- [ ] （可选二期）套餐、配额字段预留
- [ ] 前端：租户列表、创建/编辑抽屉或页

**验收**：可创建 2 个租户并启停。

### P1.2 切换商户（平台配置上下文）

- [ ] 平台前端全局「当前商户」选择器（localStorage + store）
- [ ] 请求头携带 `X-Tenant-Id`（或等价）；`platformapi` 校验租户存在且启用后写入 `TenantContext`
- [ ] 未选商户时：按租户配置类接口返回明确提示
- [ ] 选商户后：后续应用/装修/支付子商户等接口均带该上下文

**验收**：切换商户后，读到的店招/绑定信息随商户变化。

### P1.3 进入租户后台（运维）

- [ ] `platformapi`：签发短时 `impersonate_ticket`（含 `tenant_id`、`platform_admin_id`、过期时间，签名）
- [ ] 跳转：`/admin/.../impersonate?ticket=...`（路径自定）
- [ ] `adminapi`：兑票接口 → 颁发租户 JWT，标记 `is_platform_ops=1`
- [ ] 租户后台顶栏展示「平台运维进入」提示与退出运维
- [ ] 审计日志：谁在何时进入何租户

**验收**：平台一键进入租户后台，可操作该租户商品且无法看到其他租户。

### P1.4 平台管理员

- [ ] 平台管理员 CRUD（至少改密；多管理员可二期）
- [ ] 首个超管初始化方式（SQL 或安装命令）

---

## P2 — 微信：人工绑定 + Token 方案 3

### P2.1 wxcomponent 侧（运维）

- [ ] 部署 `wxcomponent`（云托管或自建），配置 MySQL、`WX_APPID`、Secret 等
- [ ] 开放平台填授权事件 URL / 消息 URL
- [ ] 确认 inner 接口可达：`GET /inner/authorizer-access-token?appid=`（内网鉴权按现 middleware）
- [ ] 文档记录：CRMEB 调用地址、超时、错误码映射
- [ ] 上传/审核/发布仍只用微管家；CRMEB 不做双轨

### P2.2 绑定（方案 A）

- [ ] `platformapi`：绑定 authorizer（校验 appid 格式；可选调微管家列表校验是否已授权）
- [ ] 解绑 / 更换绑定
- [ ] 同一 `authorizer_appid` 不可绑多个租户（DB 唯一 + 业务校验）
- [ ] 前端：切商户 →「微信绑定」页；外链微管家授权链接
- [ ] 展示昵称等：手工填或调微管家 authorizer 列表接口同步展示字段（不存 refresh_token）

**验收**：租户 A 绑小程序 appid₁，C 端用该小程序请求解析到 A。

### P2.3 Token 客户端

- [ ] 实现 `WechatTokenClient`（HTTP 调 wxcomponent inner；缓存 access_token 至过期前刷新；失败重试）
- [ ] 改造 `MiniProgramService`：按当前 `tenant_id` 取 appid → 要 token（废弃依赖 `routine_appsecret` 直连作为主路径）
- [ ] 改造 `WechatService`：同上（公众号 authorizer）
- [ ] 配置项：`wxcomponent_inner_base_url` 等放**平台全局**
- [ ] 兼容开关（可选）：迁移期允许旧 secret 模式，稳定后关闭
- [ ] 明确 `Open3rd/` 不再接入业务；代码注释或 README 标明废弃

**验收**：登录、发模板消息、生成小程序码等关键调用在无 secret 配置下成功。

### P2.4 C 端租户解析

- [ ] 统一 appid 传递约定（推荐 header，如 `Authorizer-Appid` / 现有字段梳理）
- [ ] 中间件未解析到租户 → 统一错误
- [ ] 缓存 appid→tenant_id 映射（失效：解绑/换绑时清理）

---

## P3 — 配置与菜单归属迁移

> 原则：按方案「平台·全局 / 平台·切商户 / 租户端」搬入口；数据层与 P0 配置策略一致。

### P3.1 平台 · 全局配置页

- [ ] 系统配置（非店招）：地图、WAF、远程登录等迁到平台「全局设置」
- [ ] 城市数据：平台维护，去掉租户可写（若原在设置下）
- [ ] 接口全局：存储、物流查询、采集商品 Key 等
- [ ] 支付服务商主体 + 分账接收方（从原微信支付页拆出）
- [ ] 维护整模块入口迁到平台（清缓存、日志、备份、定时任务、升级等）
- [ ] 研发元数据（配置分类/列表、代码生成等）挂平台或归维护

### P3.2 平台 · 切商户配置页

- [ ] **店招**：名称、LOGO、分享（从系统配置拆出）
- [ ] **应用整模块**：公众号/小程序/PC/APP 配置 + 菜单/图文/回复等运营页（从租户 admin 迁出或平台重挂路由）
- [ ] 应用内「上传/发布」改为跳转 wxcomponent 或隐藏
- [ ] **装修**：主题、微页面、DIY、相关组合数据
- [ ] **支付子商户**：`sub_mchid`、租户侧支付开关等（不含服务商主体）
- [ ] **短信接口、一号通、短信购买/模板、电子发票、电子面单**
- [ ] **附件管理**（平台切商户入口）

### P3.3 租户端保留与裁剪

- [ ] 保留：首页、商品、订单、用户、营销、财务、分销、内容、设置（消息/协议/小票/权限/发货/分销配置/客服/附件）
- [ ] 移除或隐藏菜单：应用、装修、维护、事业部、全局系统配置、接口敏感配置、城市数据编辑等
- [ ] `eb_system_menus` 数据迁移脚本：按 `tenant` 裁剪可见菜单；平台菜单独立体系（不要混用同一套 role 表除非加类型）
- [ ] 租户管理员 / 角色：数据带 `tenant_id`；不可赋已移除模块权限

### P3.4 配置读写改造

- [ ] `sys_config()` / `SystemConfigService`：按上下文读全局或租户配置
- [ ] 支付读取：服务商字段走全局；`sub_mchid` 走当前租户
- [ ] 短信/一号通/面单：读当前租户配置
- [ ] 存储：全局 AK/SK + 上传路径强制 `tenant/{tenant_id}/...`

**验收**：平台改全局地图 Key 全站生效；切商户改店名只影响该租户 C 端。

---

## P4 — 业务数据隔离

### P4.1 表与模型（分批）

按模块给业务表加 `tenant_id` + 索引，并改 Model/Dao/Service：

- [ ] **用户**：`user`、地址、账单、充值等
- [ ] **商品**：产品、分类、SKU、评价、保障等
- [ ] **订单**：订单、购物车、售后、发货、发票等
- [ ] **营销**：券、拼团、秒杀、砍价、积分商城、抽奖等
- [ ] **财务 / 分销**：提现、佣金流水、分销关系等
- [ ] **内容**：文章、分类
- [ ] **客服**：会话、话术等
- [ ] **设置类租户数据**：协议、消息模板配置、小票、运费模板、门店/核销员等
- [ ] **装修数据**（若存 DIY 表）：带 `tenant_id`
- [ ] **附件表**：`tenant_id`；列表接口双端过滤

> 约 150+ Model，建议按模块列清单表打勾，禁止遗漏「无 Scope 的原生查询」。

### P4.2 查询审计

- [ ] 扫出未走 Model 的 `Db::name` / 原生 SQL，补 `tenant_id`
- [ ] 队列 Job、定时任务、导出任务：传递并校验 `tenant_id`
- [ ] 缓存 key 全部加租户前缀（如 `tenant:{id}:...`）
- [ ] 附件 URL/路径不可跨租户猜访问（鉴权或路径含租户且校验）

### P4.3 历史数据

- [ ] 现网单店数据迁移：指定 `tenant_id=1`（或默认租户）回填脚本
- [ ] 回填后校验订单/用户/商品数量

**验收**：两租户各下单，后台与 C 端互不可见；附件互不可见。

---

## P5 — 支付与分账

- [ ] 平台全局：`sp_mchid` / `sp_appid`、证书、V3 密钥、分账接收方
- [ ] 租户（平台切商户）：`sub_mchid`、该租户是否启用微信/支付宝等
- [ ] 下单、退款、回调：按订单所属租户取子商户 + 全局服务商
- [ ] 分账任务：抽成读全局比例/接收方；订单维度带 `tenant_id`
- [ ] 支付回调 URL 能解析到租户（out_trade_no 映射或附加数据）
- [ ] 租户端不展示可改子商户号的表单（只读「已开通」可选）

**验收**：两租户不同 `sub_mchid` 支付成功；分账进平台接收方。

---

## P6 — 跨租户报表（平台）

- [ ] 平台工作台：租户数、今日全站订单/GMV、异常租户
- [ ] 跨租户订单/用户/交易汇总（按租户下钻）
- [ ] 权限：仅平台管理员
- [ ] 查询走显式 `tenant_id` 聚合，**禁止**误用租户 Scope 导致只看到当前切商户

**验收**：平台未切商户也能看汇总；下钻到租户正确。

---

## P7 — 清理、部署与验收

### P7.1 砍掉与清理

- [ ] 移除事业部/代理商菜单、路由、入口（`division`）；代码可先下线菜单再择机删
- [ ] 租户端去掉已迁模块菜单
- [ ] 文档与 `.gitignore`：`wxcomponent` 敏感项保持忽略
- [ ] 标注废弃：`Open3rd` 业务引用清零

### P7.2 部署清单

- [ ] 同域路由：`/platform`、`/platformapi`、`/admin`、`adminapi`、C 端 API
- [ ] wxcomponent 独立服务网络打通（inner）
- [ ] 队列 / 定时任务多租户安全
- [ ] 监控：token 拉取失败、串租户告警（日志关键字）

### P7.3 验收用例（最低集）

| # | 用例 | 期望 |
|---|------|------|
| 1 | 创建租户 A/B | 成功 |
| 2 | A/B 各绑不同小程序 appid | 成功且唯一 |
| 3 | A 小程序下单 | 仅 A 后台可见 |
| 4 | B 后台看不到 A 用户/商品 | 通过 |
| 5 | 平台切到 A 改店名 | 仅 A C 端变化 |
| 6 | 平台改地图 Key | 全局生效 |
| 7 | 平台进入 A 后台 | 可运维；退出后恢复 |
| 8 | A/B 不同 sub_mchid 支付 | 成功 |
| 9 | 分账 | 按全局规则入账 |
| 10 | 附件 A 上传 | B 列表不可见；平台切 B 不可见 A |
| 11 | 跨租户报表 | 汇总含 A+B |
| 12 | 租户登录无应用/维护菜单 | 通过 |

---

## 按端工作量拆分（便于排期）

### 后端（PHP）

| 包 | 主要内容 |
|----|----------|
| 平台应用 | `platformapi` 全套 |
| 上下文与 Scope | TenantContext、中间件、BaseModel |
| 配置 | SystemConfig 读写分层 |
| 微信 | WechatTokenClient、Mini/WechatService |
| 业务隔离 | 各模块 Dao/Service/Job |
| 支付 | 服务商全局 + 子商户租户 |
| 报表 | 聚合查询 |
| SQL | init + 分批 `tenant_id` + 菜单裁剪 |

### 前端

| 包 | 主要内容 |
|----|----------|
| `template/platform` | 登录、租户、切商户、全局设置、切商户配置、报表、维护 |
| `template/admin` | 裁剪菜单；兑票进入；附件保留；去掉迁出模块 |
| 构建部署 | `/platform` 静态资源 |

### 运维 / 外部

| 包 | 主要内容 |
|----|----------|
| wxcomponent | 部署、授权、inner 网络 |
| Nginx | 路径与反代 |
| 数据迁移 | 单店 → 默认租户回填 |

---

## 建议排期切片（可按人力调整）

| 切片 | 交付物 | 依赖 |
|------|--------|------|
| S1 | P0 完成：能平台登录 + Context 骨架 | 无 |
| S2 | P1：两租户 CRUD + 切商户 + 进入租户 | S1 |
| S3 | P2：绑 appid + Token + C 端解析（可先不改全业务隔离） | S2 + wxcomponent |
| S4 | P4 用户/商品/订单隔离 + 默认租户回填 | S1 |
| S5 | P3 配置与菜单迁移（先店招/应用/支付） | S2 |
| S6 | P5 支付分账 | S4 + S5 |
| S7 | 装修/短信/面单/附件双入口 + 剩余隔离 | S5/S4 |
| S8 | P6 报表 + P7 验收 | 前置主路径完成 |

---

## 风险与约束（实施时盯住）

1. **漏 Scope = 串数据**：每个模块合并前做「跨租户读」测试  
2. **配置双读**：全局 vs 租户字段必须名单化，避免 `sys_config` 读错层  
3. **队列/缓存**：最易漏租户维度  
4. **支付回调**：必须能定位租户  
5. **应用运营在平台**：店长改菜单需平台切商户或「进入租户」——产品上已接受  
6. **Open3rd / 自建授权**：禁止再开新坑  

---

## 文档维护

- 方案变更：先改 `saas多租户改造方案.md`，再同步本清单阶段项  
- 本清单打勾可在 PR / 项目管理工具复制；不必改文件也可  

---

*实施以方案文档为产品准绳，以本清单为工程拆解。*
