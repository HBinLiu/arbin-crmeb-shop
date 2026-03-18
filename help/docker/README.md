# 安装docker
## docker 官网下载
https://www.docker.com/products/docker-desktop
## 命令安装
```
curl -sSL https://get.daocloud.io/docker | sh
```

# 方式一：一键启动CRMEB 系统

```
docker run -d \
  --name crmeb_app \
  -p 8111:80 \
  -v $(pwd)/crmeb/runtime:/var/www/crmeb/runtime \
  -v $(pwd)/crmeb/uploads:/var/www/crmeb/public/uploads \
  -v $(pwd)/crmeb_mysql:/var/lib/mysql \
  -v $(pwd)/crmeb_redis:/var/lib/redis \
  -e TZ=Asia/Shanghai \
  ccr.ccs.tencentyun.com/zbkj/crmebky:latest
 ``` 
 
# 方式二：docker-compose 快速运行项目


## 1、安装docker-compose
https://www.runoob.com/docker/docker-compose.html

## 2、下载CRMEB程序
建议去下载最新开源代码 https://gitee.com/ZhongBangKeJi/CRMEB
程序放到docker 同级目录下

## 3、启动项目（推荐使用 run.sh 脚本）

推荐使用项目提供的 `run.sh` 管理脚本进行启动，它提供了交互式菜单和命令行两种方式：

### 3.1 方式一：交互式菜单（推荐）

```bash
cd help/docker
chmod +x run.sh
./run.sh
```

脚本会显示交互式菜单：
```
  1、安装并启动
  2、启动容器
  3、重启容器
  4、停止容器
  5、删除容器和数据
  6、查看日志
  7、查看帮助
  8、退出
```

### 3.2 方式二：命令行参数

```bash
# 安装并启动（清理旧数据，首次部署）
./run.sh install

# 启动容器
./run.sh start

# 重启容器
./run.sh restart

# 停止容器
./run.sh stop

# 删除容器和数据
./run.sh delete

# 查看日志
./run.sh logs

# 查看帮助
./run.sh -h
```

### 3.3 环境变量

可以通过 `COMPOSE_FILE` 环境变量指定不同的 compose 文件：

```bash
# 使用默认配置
./run.sh install

# 使用其他配置文件
COMPOSE_FILE=docker-compose.build.yml ./run.sh install
```

### 3.4 run.sh 脚本功能说明

| 功能 | 说明 |
|------|------|
| install | 停止旧容器、清理数据（MySQL、runtime）、设置权限、启动新容器 |
| start | 启动容器（保留现有数据） |
| restart | 重启容器 |
| stop | 停止容器 |
| delete | 删除容器和数据（完全清理） |
| logs | 实时查看日志 |

> **提示**：`install` 命令会执行以下操作：
> - 删除 `install.lock` 文件（允许重新安装）
> - 清空 MySQL 数据目录
> - 清空 runtime 缓存目录
> - 设置正确的目录权限

### 3.5 方式三：使用 Makefile（推荐开发者）

项目提供了 `Makefile` 文件，支持更丰富的管理命令：

```bash
cd help/docker

# 查看所有可用命令
make help

# 安装并启动（首次部署）
make install

# 启动容器
make start

# 停止容器
make stop

# 重启容器
make restart

# 查看日志
make logs

# 查看容器状态
make ps
```

#### Makefile 常用命令

| 命令 | 说明 |
|------|------|
| `make install` | 安装并启动（清理数据，首次部署） |
| `make start` | 启动容器 |
| `make stop` | 停止容器 |
| `make restart` | 重启容器 |
| `make delete` | 删除容器和数据 |
| `make logs` | 查看所有日志 |
| `make ps` | 查看容器状态 |
| `make clean` | 清理缓存（保留容器） |
| `make rebuild` | 重新构建镜像并启动 |

#### 信息查看命令

| 命令 | 说明 |
|------|------|
| `make info` | 查看所有容器信息总览（地址、端口、账号等） |
| `make info-mysql` | 查看 MySQL 详细信息（连接地址、账号密码） |
| `make info-redis` | 查看 Redis 详细信息（连接地址、密码） |
| `make info-php` | 查看 PHP 详细信息（目录映射、端口） |
| `make info-nginx` | 查看 Nginx 详细信息（访问地址、配置文件） |
| `make info-network` | 查看网络信息（容器IP、通信方式） |
| `make info-quick` | 快速参考卡片（常用命令速查） |

#### 进阶命令

| 命令 | 说明 |
|------|------|
| `make exec-php` | 进入 PHP 容器 |
| `make exec-mysql` | 进入 MySQL 容器 |
| `make exec-redis` | 进入 Redis 容器 |
| `make timer` | 启动定时任务 |
| `make workerman` | 启动长连接服务 |
| `make queue` | 启动队列监听 |
| `make services` | 启动所有服务（定时任务+长连接+队列） |
| `make logs-php` | 查看 PHP 日志 |
| `make logs-mysql` | 查看 MySQL 日志 |
| `make logs-redis` | 查看 Redis 日志 |
| `make logs-nginx` | 查看 Nginx 日志 |
| `make init` | 完整初始化（首次使用） |

#### 指定配置文件

```bash
# 使用默认配置
make install

# 使用其他配置文件
COMPOSE_FILE=docker-compose.build.yml make install
```

### 3.6 传统方式（直接使用 docker-compose）

如果不使用脚本，也可以直接使用 docker-compose 命令：

```
进入docker-compose目录 cd /docker

运行命令：
```
docker-compose up -d

```
## 4、访问CRMEB 系统
移动端访问地址：http://localhost:8011/
PC端访问地址：http://localhost:8011/admin


## 5、安装CRMEB
### Mysql数据库信息：
```
Host:crmeb_mysql
Post:3306
user:crmeb
pwd:123456
```
### Redis信息：
```
Host:crmeb_redis
Post:6379
db:0
pwd:123456
```

## 6、常见错误及解决方案

### 6.1 MySQL 启动失败
**错误现象**：MySQL 容器启动失败，日志显示 "--initialize specified but the data directory has files in it. Aborting."

**解决方案**：
1. 停止所有容器：`docker-compose down`
2. 清空数据目录：`rm -rf mysql/data/*`
3. 重新启动服务：`docker-compose up -d`

**原因**：MySQL 数据目录不为空，导致初始化失败。

### 6.2 数据目录映射问题
**错误现象**：数据库无法启动或数据无法持久化

**解决方案**：
1. 确保 `mysql/data` 目录存在：`mkdir -p mysql/data`
2. 确保 `mysql/data` 目录为空
3. 确保 docker-compose.yml 中正确配置了数据卷映射：
   ```yaml
   volumes:
     - ./mysql/data:/var/lib/mysql
   ```

**原因**：数据目录未映射或映射不正确，导致数据库无法创建或数据丢失。

## 6.3 常见需要映射的目录说明

### 6.3.1 MySQL 数据目录
- **本地路径**：`mysql/data`
- **容器路径**：`/var/lib/mysql`
- **用途**：存储 MySQL 数据库的数据文件
- **注意事项**：必须为空目录，否则 MySQL 初始化会失败

### 6.2 MySQL 日志目录
- **本地路径**：`mysql/log`
- **容器路径**：`/var/log/mysql`
- **用途**：存储 MySQL 的日志文件
- **注意事项**：确保目录存在且有读写权限

### 6.3.2 PHP 应用目录
- **本地路径**：`../../crmeb`
- **容器路径**：`/var/www`
- **用途**：存储 CRMEB 应用代码
- **注意事项**：确保目录存在且包含完整的 CRMEB 代码

### 6.3.3 PHP 运行时目录
- **本地路径**：`../../crmeb/runtime`
- **容器路径**：`/var/www/runtime`
- **用途**：存储 PHP 应用的运行时文件，如缓存、日志等
- **注意事项**：确保目录存在且有读写权限

### 6.3.4 Nginx 配置目录
- **本地路径**：`./nginx/vhost.conf`
- **容器路径**：`/etc/nginx/conf.d/default.conf`
- **用途**：Nginx 虚拟主机配置文件
- **注意事项**：确保配置文件存在且格式正确

### 6.3.5 Nginx 日志目录
- **本地路径**：`./nginx/log`
- **容器路径**：`/etc/nginx/log`
- **用途**：存储 Nginx 的日志文件
- **注意事项**：确保目录存在且有读写权限

### 6.3.6 目录创建命令
```bash
# 创建所有必要的目录
mkdir -p mysql/data mysql/log nginx/log

# 确保 CRMEB 应用目录存在
mkdir -p ../crmeb ../crmeb/runtime
```


