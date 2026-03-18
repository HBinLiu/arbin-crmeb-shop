# CRMEB Docker Makefile
# 提供便捷的 Docker 环境管理命令

SHELL := /bin/bash
COMPOSE_FILE ?= docker-compose.yml
COMPOSE_COMMAND := docker-compose -f $(COMPOSE_FILE)

# 颜色定义
RED := \033[0;31m
GREEN := \033[0;32m
YELLOW := \033[1;33m
CYAN := \033[0;36m
NC := \033[0m

# ==================== 从 docker-compose.yml 自动读取配置 ====================

# 提取服务块的函数（只匹配到下一个服务或网络定义）
# MySQL 配置
MYSQL_CONTAINER := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'container_name:' | awk '{print $$2}')
MYSQL_IMAGE := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'image:' | awk '{print $$2}')
MYSQL_PORT_OUT := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep ':3306"' | sed 's/.*"\([0-9]*\):.*/\1/')
MYSQL_ROOT_PWD := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'MYSQL_ROOT_PASSWORD:' | awk '{print $$2}')
MYSQL_USER := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'MYSQL_USER:' | awk '{print $$2}')
MYSQL_PWD := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'MYSQL_PASSWORD:' | awk '{print $$2}')
MYSQL_DB := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'MYSQL_DATABASE:' | awk '{print $$2}')
MYSQL_IP := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep 'ipv4_address:' | awk '{print $$2}')
# MySQL内部端口（从端口映射提取）
MYSQL_PORT_IN := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep -E ':[0-9]+"' | head -1 | sed 's/.*:\([0-9]*\)".*/\1/')
# MySQL数据目录映射
MYSQL_DATA_LOCAL := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep '/var/lib/mysql' | sed 's/.*- *\([^:]*\):.*/\1/')
MYSQL_DATA_CONTAINER := /var/lib/mysql
# MySQL日志目录映射
MYSQL_LOG_LOCAL := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep '/var/log/mysql' | sed 's/.*- *\([^:]*\):.*/\1/')
MYSQL_LOG_CONTAINER := /var/log/mysql
# MySQL配置文件目录（可选，如果未映射则显示默认路径）
MYSQL_CONF_LOCAL := $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep '/etc/mysql' | sed 's/.*- *\([^:]*\):.*/\1/')
MYSQL_CONF_CONTAINER := $(or $(shell sed -n '/^[[:space:]]*mysql:/,/^[[:space:]]*redis:/p' $(COMPOSE_FILE) | grep '/etc/mysql' | sed 's/.*:\([^ ]*\).*/\1/'),/etc/mysql/conf.d/my.cnf)

# Redis 配置
REDIS_CONTAINER := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'container_name:' | awk '{print $$2}')
REDIS_IMAGE := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'image:' | awk '{print $$2}')
REDIS_PORT_OUT := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep ':6379"' | sed 's/.*"\([0-9]*\):.*/\1/')
REDIS_PWD := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'REDIS_PASSWORD:' | awk '{print $$2}')
REDIS_IP := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'ipv4_address:' | awk '{print $$2}')
# Redis内部端口
REDIS_PORT_IN := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep -E ':[0-9]+"' | head -1 | sed 's/.*:\([0-9]*\)".*/\1/')
# Redis配置文件（可选）
REDIS_CONF_LOCAL := $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'redis.conf' | sed 's/.*- *\([^:]*\):.*/\1/')
REDIS_CONF_CONTAINER := $(or $(shell sed -n '/^[[:space:]]*redis:/,/^[[:space:]]*phpfpm:/p' $(COMPOSE_FILE) | grep 'redis.conf' | sed 's/.*:\([^ ]*\).*/\1/'),/usr/local/etc/redis/redis.conf)

# PHP 配置
PHP_CONTAINER := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep 'container_name:' | awk '{print $$2}')
PHP_IMAGE := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep 'image:' | awk '{print $$2}')
PHP_IP := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep 'ipv4_address:' | awk '{print $$2}')
PHP_PORT_9000 := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':9000"' | sed 's/.*"\([0-9]*\):.*/\1/')
PHP_PORT_40001 := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':40001"' | sed 's/.*"\([0-9]*\):.*/\1/')
PHP_PORT_40002 := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':40002"' | sed 's/.*"\([0-9]*\):.*/\1/')
# PHP内部端口
PHP_PORT_9000_IN := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':9000"' | sed 's/.*:\([0-9]*\)".*/\1/')
PHP_PORT_40001_IN := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':40001"' | sed 's/.*:\([0-9]*\)".*/\1/')
PHP_PORT_40002_IN := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':40002"' | sed 's/.*:\([0-9]*\)".*/\1/')
# PHP目录映射
PHP_APP_LOCAL := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':/var/www$$' | sed 's/.*- *\([^:]*\):.*/\1/' | head -1)
PHP_APP_CONTAINER := /var/www
PHP_RUNTIME_LOCAL := $(shell sed -n '/^[[:space:]]*phpfpm:/,/^[[:space:]]*nginx:/p' $(COMPOSE_FILE) | grep ':/var/www/runtime' | sed 's/.*- *\([^:]*\):.*/\1/')
PHP_RUNTIME_CONTAINER := /var/www/runtime

# Nginx 配置
NGINX_CONTAINER := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep 'container_name:' | awk '{print $$2}')
NGINX_IMAGE := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep 'image:' | awk '{print $$2}')
NGINX_IP := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep 'ipv4_address:' | awk '{print $$2}')
NGINX_PORT_OUT := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep ':80"' | sed 's/.*"\([0-9]*\):.*/\1/')
# Nginx内部端口
NGINX_PORT_IN := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep ':80"' | sed 's/.*:\([0-9]*\)".*/\1/')
# Nginx目录映射
NGINX_VHOST_LOCAL := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep '/etc/nginx/conf.d' | sed 's/.*- *\([^:]*\):.*/\1/')
NGINX_VHOST_CONTAINER := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep '/etc/nginx/conf.d' | sed 's/.*:\([^ ]*\).*/\1/')
NGINX_LOG_LOCAL := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep '/etc/nginx/log' | sed 's/.*- *\([^:]*\):.*/\1/')
NGINX_LOG_CONTAINER := $(shell sed -n '/^[[:space:]]*nginx:/,/^networks:/p' $(COMPOSE_FILE) | grep '/etc/nginx/log' | sed 's/.*:\([^ ]*\).*/\1/')

# 网络配置
NETWORK_SUBNET := $(shell grep -A 3 'ipam:' $(COMPOSE_FILE) | grep 'subnet:' | sed 's/.*subnet: *//')

# 帮助信息
.PHONY: help
help:
	@echo ""
	@echo -e "$(GREEN)CRMEB Docker 管理脚本$(NC)"
	@echo ""
	@echo "用法: make [目标]"
	@echo ""
	@echo -e "$(YELLOW)容器管理:$(NC)"
	@echo "  make install      安装并启动（清理数据，首次部署）"
	@echo "  make start        启动容器"
	@echo "  make restart      重启容器"
	@echo "  make stop         停止容器"
	@echo "  make delete       删除容器和数据"
	@echo "  make rebuild      重新构建并启动"
	@echo "  make pull         拉取最新镜像"
	@echo ""
	@echo -e "$(YELLOW)信息查看:$(NC)"
	@echo "  make info         查看所有容器信息总览"
	@echo "  make info-mysql   查看 MySQL 详细信息"
	@echo "  make info-redis   查看 Redis 详细信息"
	@echo "  make info-php     查看 PHP 详细信息"
	@echo "  make info-nginx   查看 Nginx 详细信息"
	@echo "  make info-network 查看网络信息"
	@echo "  make info-quick   快速参考卡片"
	@echo "  make ps           查看容器状态"
	@echo ""
	@echo -e "$(YELLOW)日志查看:$(NC)"
	@echo "  make logs         查看所有日志"
	@echo "  make logs-php     查看 PHP 日志"
	@echo "  make logs-mysql   查看 MySQL 日志"
	@echo "  make logs-redis   查看 Redis 日志"
	@echo "  make logs-nginx   查看 Nginx 日志"
	@echo ""
	@echo -e "$(YELLOW)容器进入:$(NC)"
	@echo "  make exec-php     进入 PHP 容器"
	@echo "  make exec-mysql   进入 MySQL 容器"
	@echo "  make exec-redis   进入 Redis 容器"
	@echo ""
	@echo -e "$(YELLOW)服务管理:$(NC)"
	@echo "  make timer        启动定时任务"
	@echo "  make workerman    启动长连接服务"
	@echo "  make queue        启动队列监听"
	@echo "  make services     启动所有服务"
	@echo ""
	@echo -e "$(YELLOW)其他:$(NC)"
	@echo "  make clean        清理缓存和数据"
	@echo "  make init         完整初始化（首次使用）"
	@echo "  make help         显示帮助信息"
	@echo ""
	@echo "环境变量:"
	@echo "  COMPOSE_FILE      指定 compose 文件 (默认: docker-compose.yml)"
	@echo "  示例: COMPOSE_FILE=docker-compose.build.yml make install"
	@echo ""

# 检查 docker-compose 是否可用
check-docker:
	@if ! command -v docker-compose &> /dev/null; then \
		echo -e "$(RED)错误: docker-compose 未安装$(NC)"; \
		exit 1; \
	fi
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		echo -e "$(RED)错误: 配置文件 $(COMPOSE_FILE) 不存在$(NC)"; \
		exit 1; \
	fi

# 清理 install.lock
clean-lock:
	@echo -e "$(YELLOW)清理 install.lock...$(NC)"
	@if [ -f "$(PHP_APP_LOCAL)/public/install.lock" ]; then \
		rm -f $(PHP_APP_LOCAL)/public/install.lock; \
		echo "已删除 install.lock"; \
	fi

# 清理 MySQL 数据
clean-mysql:
	@echo -e "$(YELLOW)清理 MySQL 数据...$(NC)"
	@if [ -d "mysql/data" ] && [ -n "$$(ls -A mysql/data 2>/dev/null)" ]; then \
		rm -rf mysql/data/*; \
		echo "已清空 MySQL 数据目录"; \
	fi
	@if [ -d "mysql/log" ] && [ -n "$$(ls -A mysql/log 2>/dev/null)" ]; then \
		rm -rf mysql/log/*; \
		echo "已清空 MySQL 日志目录"; \
	fi

# 清理 runtime 缓存
clean-runtime:
	@echo -e "$(YELLOW)清理 runtime 缓存...$(NC)"
	@if [ -d "$(PHP_RUNTIME_LOCAL)" ] && [ -n "$(ls -A $(PHP_RUNTIME_LOCAL) 2>/dev/null)" ]; then \
		rm -rf $(PHP_RUNTIME_LOCAL)/*; \
		echo "已清空 runtime 目录"; \
	fi

# 设置目录权限
set-permission:
	@echo -e "$(YELLOW)设置目录权限...$(NC)"
	@chmod -R 777 $(PHP_RUNTIME_LOCAL) 2>/dev/null || true
	@chmod -R 777 $(PHP_APP_LOCAL)/public/uploads 2>/dev/null || true
	@chmod 666 $(PHP_APP_LOCAL)/.env 2>/dev/null || true
	@chmod 666 $(PHP_APP_LOCAL)/.version 2>/dev/null || true
	@chmod 666 $(PHP_APP_LOCAL)/.constant 2>/dev/null || true

# 清理网络
clean-network:
	@echo -e "$(YELLOW)清理可能存在的冲突网络...$(NC)"
	@for net in $$(docker network ls --format "{{.Name}}" | grep -E "(app_net|docker_app_net|crmeb_app_net)"); do \
		echo "删除网络: $$net"; \
		docker network rm "$$net" 2>/dev/null || true; \
	done
	@docker network prune -f 2>/dev/null || true

# 停止旧容器
down:
	@echo -e "$(YELLOW)停止旧容器...$(NC)"
	@$(COMPOSE_COMMAND) down 2>/dev/null || true

# ==================== 目标定义 ====================

# 安装并启动（清理数据，首次部署）
.PHONY: install
install: check-docker down clean-lock clean-mysql clean-runtime set-permission clean-network
	@echo -e "$(YELLOW)安装并启动 Docker 环境...$(NC)"
	@$(COMPOSE_COMMAND) up -d
	@echo ""
	@echo -e "$(GREEN)=== 安装完成 ===$(NC)"
	@echo "配置文件: $(COMPOSE_FILE)"
	@echo "访问地址: http://localhost:$(NGINX_PORT_OUT)"
	@echo "管理端地址: http://localhost:$(NGINX_PORT_OUT)/admin"
	@echo "查看日志: make logs"

# 启动容器
.PHONY: start
start: check-docker clean-network
	@echo -e "$(YELLOW)启动容器...$(NC)"
	@$(COMPOSE_COMMAND) up -d
	@echo ""
	@echo -e "$(GREEN)=== 启动完成 ===$(NC)"
	@echo "配置文件: $(COMPOSE_FILE)"
	@echo "访问地址: http://localhost:$(NGINX_PORT_OUT)"

# 重启容器
.PHONY: restart
restart:
	@echo -e "$(YELLOW)重启容器...$(NC)"
	@$(COMPOSE_COMMAND) restart
	@echo ""
	@echo -e "$(GREEN)=== 重启完成 ===$(NC)"

# 停止容器
.PHONY: stop
stop:
	@echo -e "$(YELLOW)停止容器...$(NC)"
	@$(COMPOSE_COMMAND) down
	@echo -e "$(GREEN)=== 已停止 ===$(NC)"

# 删除容器和数据
.PHONY: delete
delete:
	@echo -e "$(YELLOW)删除容器和数据...$(NC)"
	@$(COMPOSE_COMMAND) down -v
	@rm -rf $(MYSQL_DATA_LOCAL)/* 2>/dev/null || true
	@rm -rf $(MYSQL_LOG_LOCAL)/* 2>/dev/null || true
	@rm -rf $(PHP_RUNTIME_LOCAL)/* 2>/dev/null || true
	@rm -f $(PHP_APP_LOCAL)/public/install.lock 2>/dev/null || true
	@echo -e "$(YELLOW)还原文件权限...$(NC)"
	@if [ -d "$(PHP_APP_LOCAL)/.git" ] || [ -d "$(PHP_APP_LOCAL)/../.git" ]; then \
		git -C $(PHP_APP_LOCAL) restore -SW . 2>/dev/null || true; \
	else \
		chmod 644 $(PHP_APP_LOCAL)/.env 2>/dev/null || true; \
		chmod 644 $(PHP_APP_LOCAL)/.version 2>/dev/null || true; \
		chmod 644 $(PHP_APP_LOCAL)/.constant 2>/dev/null || true; \
		find $(PHP_APP_LOCAL)/public/uploads -type d -exec chmod 755 {} \; 2>/dev/null || true; \
		find $(PHP_APP_LOCAL)/public/uploads -type f -exec chmod 644 {} \; 2>/dev/null || true; \
		find $(PHP_APP_LOCAL)/public/static -type d -exec chmod 755 {} \; 2>/dev/null || true; \
		find $(PHP_APP_LOCAL)/public/static -type f -exec chmod 644 {} \; 2>/dev/null || true; \
	fi
	@echo -e "$(GREEN)=== 已删除 ===$(NC)"

# 查看日志
.PHONY: logs
logs:
	@$(COMPOSE_COMMAND) logs -f

# 查看容器状态
.PHONY: ps
ps:
	@$(COMPOSE_COMMAND) ps

# 清理缓存（保留容器）
.PHONY: clean
clean: clean-lock clean-mysql clean-runtime set-permission
	@echo -e "$(GREEN)=== 缓存清理完成 ===$(NC)"
	@echo "注意: 容器仍在运行，如需完全删除使用 make delete"

# 重新构建并启动
.PHONY: rebuild
rebuild: check-docker
	@echo -e "$(YELLOW)重新构建镜像...$(NC)"
	@$(COMPOSE_COMMAND) build --no-cache
	@$(COMPOSE_COMMAND) up -d
	@echo ""
	@echo -e "$(GREEN)=== 重建完成 ===$(NC)"

# 拉取最新镜像
.PHONY: pull
pull:
	@echo -e "$(YELLOW)拉取最新镜像...$(NC)"
	@$(COMPOSE_COMMAND) pull
	@echo -e "$(GREEN)=== 拉取完成 ===$(NC)"

# 进入 PHP 容器
.PHONY: exec-php
exec-php:
	@docker exec -it $(PHP_CONTAINER) /bin/bash

# 进入 MySQL 容器
.PHONY: exec-mysql
exec-mysql:
	@docker exec -it $(MYSQL_CONTAINER) mysql -uroot -p$(MYSQL_ROOT_PWD)

# 进入 Redis 容器
.PHONY: exec-redis
exec-redis:
	@docker exec -it $(REDIS_CONTAINER) redis-cli -a $(REDIS_PWD)

# 启动定时任务
.PHONY: timer
timer:
	@docker exec -it $(PHP_CONTAINER) cd $(PHP_APP_CONTAINER) && php think timer start --d

# 启动 Workerman 长连接
.PHONY: workerman
workerman:
	@docker exec -it $(PHP_CONTAINER) cd $(PHP_APP_CONTAINER) && php think workerman start --d

# 启动队列监听
.PHONY: queue
queue:
	@docker exec -it $(PHP_CONTAINER) cd $(PHP_APP_CONTAINER) && php think queue:listen --queue

# 启动所有服务（定时任务+Workerman+队列）
.PHONY: services
services: timer workerman queue
	@echo -e "$(GREEN)=== 所有服务已启动 ===$(NC)"

# 查看 PHP 容器日志
.PHONY: logs-php
logs-php:
	@$(COMPOSE_COMMAND) logs -f $(PHP_CONTAINER)

# 查看 MySQL 容器日志
.PHONY: logs-mysql
logs-mysql:
	@$(COMPOSE_COMMAND) logs -f $(MYSQL_CONTAINER)

# 查看 Redis 容器日志
.PHONY: logs-redis
logs-redis:
	@$(COMPOSE_COMMAND) logs -f $(REDIS_CONTAINER)

# 查看 Nginx 容器日志
.PHONY: logs-nginx
logs-nginx:
	@$(COMPOSE_COMMAND) logs -f $(NGINX_CONTAINER)

# 完整初始化（首次使用）
.PHONY: init
init: install
	@echo ""
	@echo -e "$(GREEN)=== 初始化完成 ===$(NC)"
	@echo "请访问 http://localhost:$(NGINX_PORT_OUT) 进行系统安装"

# ==================== 信息查看命令 ====================

# 查看所有容器信息
.PHONY: info
info:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║               CRMEB Docker 容器信息总览                      ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【访问地址】$(NC)"
	@echo "  前端地址:     http://localhost:$(NGINX_PORT_OUT)/"
	@echo "  后台管理:     http://localhost:$(NGINX_PORT_OUT)/admin"
	@echo "  安装向导:     http://localhost:$(NGINX_PORT_OUT)/install (首次访问)"
	@echo ""
	@echo -e "$(YELLOW)【MySQL 数据库】$(NC)"
	@echo "  容器名称:     $(MYSQL_CONTAINER)"
	@echo "  镜像:         $(MYSQL_IMAGE)"
	@echo "  内部地址:     $(MYSQL_CONTAINER):$(MYSQL_PORT_IN)"
	@echo "  外部地址:     localhost:$(MYSQL_PORT_OUT)"
	@echo "  Root密码:     $(MYSQL_ROOT_PWD)"
	@echo "  业务用户:     $(MYSQL_USER)"
	@echo "  业务密码:     $(MYSQL_PWD)"
	@echo "  数据库名:     $(MYSQL_DB)"
	@echo "  内部IP:       $(MYSQL_IP)"
	@echo "  数据目录:     $(MYSQL_DATA_LOCAL)"
	@echo ""
	@echo -e "$(YELLOW)【Redis 缓存】$(NC)"
	@echo "  容器名称:     $(REDIS_CONTAINER)"
	@echo "  镜像:         $(REDIS_IMAGE)"
	@echo "  内部地址:     $(REDIS_CONTAINER):$(REDIS_PORT_IN)"
	@echo "  外部地址:     localhost:$(REDIS_PORT_OUT)"
	@echo "  密码:         $(REDIS_PWD)"
	@echo "  内部IP:       $(REDIS_IP)"
	@echo "  数据库:       0"
	@echo ""
	@echo -e "$(YELLOW)【PHP 应用】$(NC)"
	@echo "  容器名称:     $(PHP_CONTAINER)"
	@echo "  镜像:         $(PHP_IMAGE)"
	@echo "  内部IP:       $(PHP_IP)"
	@echo "  应用目录:     $(PHP_APP_CONTAINER)"
	@echo "  端口映射:     $(PHP_PORT_9000):$(PHP_PORT_9000_IN), $(PHP_PORT_40001):$(PHP_PORT_40001_IN), $(PHP_PORT_40002):$(PHP_PORT_40002_IN)"
	@echo ""
	@echo -e "$(YELLOW)【Nginx 服务】$(NC)"
	@echo "  容器名称:     $(NGINX_CONTAINER)"
	@echo "  镜像:         $(NGINX_IMAGE)"
	@echo "  外部端口:     $(NGINX_PORT_OUT)"
	@echo "  内部端口:     $(NGINX_PORT_IN)"
	@echo "  内部IP:       $(NGINX_IP)"
	@echo "  配置文件:     $(NGINX_VHOST_LOCAL)"
	@echo "  日志目录:     $(NGINX_LOG_LOCAL)"
	@echo ""
	@echo -e "$(YELLOW)【网络配置】$(NC)"
	@echo "  网络名称:     app_net"
	@echo "  网段:         $(NETWORK_SUBNET)"
	@echo ""
	@echo -e "$(YELLOW)【常用命令】$(NC)"
	@echo "  查看容器状态: make ps"
	@echo "  查看日志:     make logs"
	@echo "  进入PHP容器:  make exec-php"
	@echo "  进入MySQL:    make exec-mysql"
	@echo "  启动服务:     make services"
	@echo ""

# 查看 MySQL 详细信息
.PHONY: info-mysql
info-mysql:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║         MySQL 数据库详细信息            ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【连接信息】$(NC)"
	@echo "  容器名称:     $(MYSQL_CONTAINER)"
	@echo "  镜像版本:     $(MYSQL_IMAGE)"
	@echo "  内部地址:     $(MYSQL_CONTAINER)"
	@echo "  内部端口:     $(MYSQL_PORT_IN)"
	@echo "  外部端口:     $(MYSQL_PORT_OUT)"
	@echo "  内部IP:       $(MYSQL_IP)"
	@echo ""
	@echo -e "$(YELLOW)【账号信息】$(NC)"
	@echo "  Root用户:     root"
	@echo "  Root密码:     $(MYSQL_ROOT_PWD)"
	@echo "  业务用户:     $(MYSQL_USER)"
	@echo "  业务密码:     $(MYSQL_PWD)"
	@echo "  数据库名:     $(MYSQL_DB)"
	@echo ""
	@echo -e "$(YELLOW)【数据存储】$(NC)"
	@echo "  本地目录:     $(MYSQL_DATA_LOCAL)"
	@echo "  容器目录:     $(MYSQL_DATA_CONTAINER)"
	@echo ""
	@echo -e "$(YELLOW)【日志目录】$(NC)"
	@echo "  本地目录:     $(MYSQL_LOG_LOCAL)"
	@echo "  容器目录:     $(MYSQL_LOG_CONTAINER)"
	@echo ""
	@echo -e "$(YELLOW)【连接命令】$(NC)"
	@echo "  本地连接:     mysql -h 127.0.0.1 -P $(MYSQL_PORT_OUT) -u root -p$(MYSQL_ROOT_PWD)"
	@echo "  容器内连接:   docker exec -it $(MYSQL_CONTAINER) mysql -uroot -p$(MYSQL_ROOT_PWD)"
	@echo "  或使用make:   make exec-mysql"
	@echo ""
	@echo -e "$(YELLOW)【配置文件】$(NC)"
	@echo "  本地配置:     $(or $(MYSQL_CONF_LOCAL),未映射)"
	@echo "  容器配置:     $(MYSQL_CONF_CONTAINER)"
	@echo ""

# 查看 Redis 详细信息
.PHONY: info-redis
info-redis:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║           Redis 缓存详细信息            ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【连接信息】$(NC)"
	@echo "  容器名称:     $(REDIS_CONTAINER)"
	@echo "  镜像版本:     $(REDIS_IMAGE)"
	@echo "  内部地址:     $(REDIS_CONTAINER)"
	@echo "  内部端口:     $(REDIS_PORT_IN)"
	@echo "  外部端口:     $(REDIS_PORT_OUT)"
	@echo "  内部IP:       $(REDIS_IP)"
	@echo ""
	@echo -e "$(YELLOW)【认证信息】$(NC)"
	@echo "  密码:         $(REDIS_PWD)"
	@echo "  默认数据库:   0"
	@echo "  最大数据库:   15"
	@echo ""
	@echo -e "$(YELLOW)【连接命令】$(NC)"
	@echo "  本地连接:     redis-cli -h 127.0.0.1 -p $(REDIS_PORT_OUT) -a $(REDIS_PWD)"
	@echo "  容器内连接:   docker exec -it $(REDIS_CONTAINER) redis-cli -a $(REDIS_PWD)"
	@echo "  或使用make:   make exec-redis"
	@echo ""
	@echo -e "$(YELLOW)【配置文件】$(NC)"
	@echo "  本地配置:     $(or $(REDIS_CONF_LOCAL),未映射)"
	@echo "  容器配置:     $(REDIS_CONF_CONTAINER)"
	@echo ""

# 查看 PHP 详细信息
.PHONY: info-php
info-php:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║            PHP 应用详细信息             ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【容器信息】$(NC)"
	@echo "  容器名称:     $(PHP_CONTAINER)"
	@echo "  镜像版本:     $(PHP_IMAGE)"
	@echo "  内部IP:       $(PHP_IP)"
	@echo ""
	@echo -e "$(YELLOW)【端口映射】$(NC)"
	@echo "  PHP-FPM:      $(PHP_PORT_9000):$(PHP_PORT_9000_IN)"
	@echo "  长连接1:      $(PHP_PORT_40001):$(PHP_PORT_40001_IN)"
	@echo "  长连接2:      $(PHP_PORT_40002):$(PHP_PORT_40002_IN)"
	@echo ""
	@echo -e "$(YELLOW)【目录映射】$(NC)"
	@echo "  应用目录:     $(PHP_APP_LOCAL) -> $(PHP_APP_CONTAINER)"
	@echo "  运行时目录:   $(PHP_RUNTIME_LOCAL) -> $(PHP_RUNTIME_CONTAINER)"
	@echo ""
	@echo -e "$(YELLOW)【常用命令】$(NC)"
	@echo "  进入容器:     make exec-php"
	@echo "  查看日志:     make logs-php"
	@echo "  启动定时任务: make timer"
	@echo "  启动长连接:   make workerman"
	@echo "  启动队列:     make queue"
	@echo ""
	@echo -e "$(YELLOW)【ThinkPHP 命令】$(NC)"
	@echo "  清除缓存:     docker exec $(PHP_CONTAINER) php $(PHP_APP_CONTAINER)/think clear"
	@echo "  查看路由:     docker exec $(PHP_CONTAINER) php $(PHP_APP_CONTAINER)/think route:list"
	@echo "  数据库迁移:   docker exec $(PHP_CONTAINER) php $(PHP_APP_CONTAINER)/think migrate:run"
	@echo ""

# 查看 Nginx 详细信息
.PHONY: info-nginx
info-nginx:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║           Nginx 服务详细信息            ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【容器信息】$(NC)"
	@echo "  容器名称:     $(NGINX_CONTAINER)"
	@echo "  镜像版本:     $(NGINX_IMAGE)"
	@echo "  内部IP:       $(NGINX_IP)"
	@echo ""
	@echo -e "$(YELLOW)【端口映射】$(NC)"
	@echo "  HTTP:         $(NGINX_PORT_OUT):$(NGINX_PORT_IN)"
	@echo ""
	@echo -e "$(YELLOW)【访问地址】$(NC)"
	@echo "  前端首页:     http://localhost:$(NGINX_PORT_OUT)/"
	@echo "  后台管理:     http://localhost:$(NGINX_PORT_OUT)/admin"
	@echo "  API接口:      http://localhost:$(NGINX_PORT_OUT)/api/"
	@echo "  安装页面:     http://localhost:$(NGINX_PORT_OUT)/install"
	@echo ""
	@echo -e "$(YELLOW)【配置文件】$(NC)"
	@echo "  本地配置:     $(NGINX_VHOST_LOCAL)"
	@echo "  容器配置:     $(NGINX_VHOST_CONTAINER)"
	@echo ""
	@echo -e "$(YELLOW)【日志文件】$(NC)"
	@echo "  本地目录:     $(NGINX_LOG_LOCAL)"
	@echo "  容器目录:     $(NGINX_LOG_CONTAINER)"
	@echo "  访问日志:     $(NGINX_LOG_LOCAL)/access.log"
	@echo "  错误日志:     $(NGINX_LOG_LOCAL)/error.log"
	@echo ""
	@echo -e "$(YELLOW)【常用命令】$(NC)"
	@echo "  查看日志:     make logs-nginx"
	@echo "  重载配置:     docker exec $(NGINX_CONTAINER) nginx -s reload"
	@echo "  测试配置:     docker exec $(NGINX_CONTAINER) nginx -t"
	@echo ""

# 查看网络信息
.PHONY: info-network
info-network:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║           Docker 网络信息               ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【网络配置】$(NC)"
	@echo "  网络名称:     app_net"
	@echo "  网段:         $(NETWORK_SUBNET)"
	@echo "  网关:         $(shell echo $(NETWORK_SUBNET) | sed 's/0\/24/1/')"
	@echo ""
	@echo -e "$(YELLOW)【容器IP地址】$(NC)"
	@echo "  MySQL:        $(MYSQL_IP)"
	@echo "  Redis:        $(REDIS_IP)"
	@echo "  PHP:          $(PHP_IP)"
	@echo "  Nginx:        $(NGINX_IP)"
	@echo ""
	@echo -e "$(YELLOW)【容器间通信】$(NC)"
	@echo "  PHP -> MySQL: $(MYSQL_CONTAINER):$(MYSQL_PORT_IN)"
	@echo "  PHP -> Redis: $(REDIS_CONTAINER):$(REDIS_PORT_IN)"
	@echo "  Nginx -> PHP: $(PHP_CONTAINER):$(PHP_PORT_9000_IN)"
	@echo ""
	@echo -e "$(YELLOW)【常用命令】$(NC)"
	@echo "  查看网络:     docker network ls"
	@echo "  网络详情:     docker network inspect app_net"
	@echo "  清理网络:     make clean-network"
	@echo ""

# 查看快速参考
.PHONY: info-quick
info-quick:
	@echo ""
	@echo -e "$(GREEN)╔══════════════════════════════════════════╗$(NC)"
	@echo -e "$(GREEN)║           快速参考卡片                  ║$(NC)"
	@echo -e "$(GREEN)╚══════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo -e "$(CYAN)配置文件: $(COMPOSE_FILE)$(NC)"
	@echo ""
	@echo -e "$(YELLOW)【一键安装】$(NC)"
	@echo "  make install              # 首次部署，自动清理数据"
	@echo ""
	@echo -e "$(YELLOW)【日常管理】$(NC)"
	@echo "  make start                # 启动"
	@echo "  make stop                 # 停止"
	@echo "  make restart              # 重启"
	@echo "  make logs                 # 查看日志"
	@echo ""
	@echo -e "$(YELLOW)【数据库连接】$(NC)"
	@echo "  Host:   $(MYSQL_CONTAINER) (容器内) / localhost:$(MYSQL_PORT_OUT) (外部)"
	@echo "  User:   root / Password: $(MYSQL_ROOT_PWD) / DB: $(MYSQL_DB)"
	@echo ""
	@echo -e "$(YELLOW)【Redis连接】$(NC)"
	@echo "  Host:   $(REDIS_CONTAINER) (容器内) / localhost:$(REDIS_PORT_OUT) (外部)"
	@echo "  Password: $(REDIS_PWD)"
	@echo ""
	@echo -e "$(YELLOW)【访问地址】$(NC)"
	@echo "  前端:   http://localhost:$(NGINX_PORT_OUT)/"
	@echo "  后台:   http://localhost:$(NGINX_PORT_OUT)/admin"
	@echo ""
	@echo -e "$(YELLOW)【故障排查】$(NC)"
	@echo "  make ps                   # 查看容器状态"
	@echo "  make logs-php             # PHP日志"
	@echo "  make logs-nginx           # Nginx日志"
	@echo "  make info                 # 完整信息"
	@echo ""
