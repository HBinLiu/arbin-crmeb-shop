# 数据库设计文档

## 1. 概述

本文档描述了 CRMEB 项目的数据库设计，包括数据库架构、表结构、索引设计、关系设计等，旨在规范数据库设计，提高数据库性能和可维护性。

## 2. 数据库架构

### 2.1 整体架构

- **数据库系统**: MySQL 5.7~8.0
- **存储引擎**: InnoDB (默认)
- **字符集**: utf8mb4
- **排序规则**: utf8mb4_general_ci
- **连接池**: 建议使用

### 2.2 技术栈

- **MySQL**: 5.7+
- **Redis**: 用于缓存
- **ThinkPHP ORM**: 用于模型操作
- **数据库迁移**: 用于版本控制
- **数据库备份**: 用于数据安全

### 2.3 配置说明

#### 2.3.1 数据库配置

```php
// config/database.php
return [
    'default' => env('database.driver', 'mysql'),
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'hostname' => env('database.hostname', '127.0.0.1'),
            'database' => env('database.database', ''),
            'username' => env('database.username', ''),
            'password' => env('database.password', ''),
            'hostport' => env('database.hostport', '3306'),
            'charset' => 'utf8mb4',
            'prefix' => env('database.prefix', ''),
            'debug' => env('app_debug', true),
        ],
    ],
];
```

## 3. 数据库设计规范

### 3.1 命名规范

- **数据库名**: 小写字母，下划线分隔
- **表名**: 小写字母，下划线分隔，前缀统一
- **字段名**: 小写字母，下划线分隔
- **索引名**: 小写字母，下划线分隔，类型前缀
  - 主键: `PRIMARY`
  - 唯一索引: `uk_字段名`
  - 普通索引: `idx_字段名`

### 3.2 表结构规范

- **主键**: 统一命名为 `id`，自增整数
- **外键**: 格式 `表名_id`，如 `user_id`
- **时间字段**: `create_time`/`update_time`
- **状态字段**: `status`，默认值 0
- **软删除字段**: `delete_time`，默认值 NULL

### 3.3 字段类型规范

- **整数类型**: 根据实际范围选择
  - `TINYINT`: 1字节，范围 -128~127
  - `SMALLINT`: 2字节，范围 -32768~32767
  - `INT`: 4字节，范围 -2147483648~2147483647
  - `BIGINT`: 8字节，范围更大

- **字符串类型**: 
  - 固定长度: `CHAR`
  - 可变长度: `VARCHAR`
  - 长文本: `TEXT`
  - 大文本: `LONGTEXT`

- **日期时间类型**: 
  - 日期: `DATE`
  - 时间: `TIME`
  - 日期时间: `DATETIME`
  - 时间戳: `TIMESTAMP`

- **数值类型**: 
  - 小数: `DECIMAL`
  - 浮点数: `FLOAT`, `DOUBLE`

- **布尔类型**: 使用 `TINYINT(1)`，0 表示 false，1 表示 true

### 3.4 索引规范

- **主键索引**: 每个表必须有主键
- **唯一索引**: 用于唯一标识的字段
- **普通索引**: 用于经常查询的字段
- **复合索引**: 用于多字段查询
- **外键索引**: 用于关联查询
- **索引数量**: 每个表索引数量不宜过多，一般不超过 5 个

## 4. 核心表结构

### 4.1 用户表 (`user`)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| `id` | `INT` | 11 | `PRIMARY KEY AUTO_INCREMENT` | 用户ID |
| `username