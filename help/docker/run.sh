#!/bin/bash

# CRMEB Docker 开发环境管理脚本

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# 配置文件（可自定义，默认 docker-compose.yml）
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml}"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ==================== 从 docker-compose.yml 读取配置 ====================

# 提取服务配置的通用函数
get_config() {
    local pattern="$1"
    local section_start="$2"
    local section_end="$3"
    sed -n "/^[[:space:]]*${section_start}:/,/^[[:space:]]*${section_end}:/p" "$COMPOSE_FILE" | grep "$pattern" | head -1
}

# MySQL 配置
MYSQL_CONTAINER=$(get_config 'container_name:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_PORT_OUT=$(get_config ':3306"' 'mysql' 'redis' | sed 's/.*"\([0-9]*\):.*/\1/')
MYSQL_PORT_IN=$(get_config ':3306"' 'mysql' 'redis' | sed 's/.*:\([0-9]*\)".*/\1/')
MYSQL_ROOT_PWD=$(get_config 'MYSQL_ROOT_PASSWORD:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_USER=$(get_config 'MYSQL_USER:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_PWD=$(get_config 'MYSQL_PASSWORD:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_DB=$(get_config 'MYSQL_DATABASE:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_IP=$(get_config 'ipv4_address:' 'mysql' 'redis' | awk '{print $2}')
MYSQL_DATA_LOCAL=$(get_config '/var/lib/mysql' 'mysql' 'redis' | sed 's/.*- *\([^:]*\):.*/\1/')
MYSQL_LOG_LOCAL=$(get_config '/var/log/mysql' 'mysql' 'redis' | sed 's/.*- *\([^:]*\):.*/\1/')

# Redis 配置
REDIS_CONTAINER=$(get_config 'container_name:' 'redis' 'phpfpm' | awk '{print $2}')
REDIS_PORT_OUT=$(get_config ':6379"' 'redis' 'phpfpm' | sed 's/.*"\([0-9]*\):.*/\1/')
REDIS_PORT_IN=$(get_config ':6379"' 'redis' 'phpfpm' | sed 's/.*:\([0-9]*\)".*/\1/')
REDIS_PWD=$(get_config 'REDIS_PASSWORD:' 'redis' 'phpfpm' | awk '{print $2}')
REDIS_IP=$(get_config 'ipv4_address:' 'redis' 'phpfpm' | awk '{print $2}')

# PHP 配置
PHP_CONTAINER=$(get_config 'container_name:' 'phpfpm' 'nginx' | awk '{print $2}')
PHP_IP=$(get_config 'ipv4_address:' 'phpfpm' 'nginx' | awk '{print $2}')
PHP_PORT_9000=$(get_config ':9000"' 'phpfpm' 'nginx' | sed 's/.*"\([0-9]*\):.*/\1/')
PHP_PORT_9000_IN=$(get_config ':9000"' 'phpfpm' 'nginx' | sed 's/.*:\([0-9]*\)".*/\1/')
PHP_APP_LOCAL=$(get_config ':/var/www$' 'phpfpm' 'nginx' | sed 's/.*- *\([^:]*\):.*/\1/')
PHP_RUNTIME_LOCAL=$(get_config ':/var/www/runtime' 'phpfpm' 'nginx' | sed 's/.*- *\([^:]*\):.*/\1/')

# Nginx 配置
NGINX_CONTAINER=$(get_config 'container_name:' 'nginx' '^networks' | awk '{print $2}')
NGINX_PORT_OUT=$(get_config ':80"' 'nginx' '^networks' | sed 's/.*"\([0-9]*\):.*/\1/')
NGINX_PORT_IN=$(get_config ':80"' 'nginx' '^networks' | sed 's/.*:\([0-9]*\)".*/\1/')
NGINX_IP=$(get_config 'ipv4_address:' 'nginx' '^networks' | awk '{print $2}')
NGINX_VHOST_LOCAL=$(get_config '/etc/nginx/conf.d' 'nginx' '^networks' | sed 's/.*- *\([^:]*\):.*/\1/')
NGINX_LOG_LOCAL=$(get_config '/etc/nginx/log' 'nginx' '^networks' | sed 's/.*- *\([^:]*\):.*/\1/')

# 网络配置
NETWORK_SUBNET=$(grep -A 3 'ipam:' "$COMPOSE_FILE" | grep 'subnet:' | sed 's/.*subnet: *//')

# ==================== 帮助信息 ====================

show_help() {
    echo -e "${GREEN}CRMEB Docker 管理脚本${NC}"
    echo ""
    echo "用法: $0 [选项]"
    echo ""
    echo -e "${YELLOW}容器管理:${NC}"
    echo "  install   安装并启动（清理数据，首次部署）"
    echo "  start     启动容器"
    echo "  restart   重启容器"
    echo "  stop      停止容器"
    echo "  delete    删除容器和数据"
    echo "  logs      查看日志"
    echo ""
    echo -e "${YELLOW}信息查看:${NC}"
    echo "  info      查看所有容器信息"
    echo "  info-db   查看MySQL连接信息"
    echo "  info-redis查看Redis连接信息"
    echo "  ps        查看容器状态"
    echo ""
    echo -e "${YELLOW}其他:${NC}"
    echo "  -h, --help 显示帮助"
    echo ""
    echo "环境变量:"
    echo "  COMPOSE_FILE  指定 compose 文件 (默认: docker-compose.yml)"
    echo ""
    echo "示例:"
    echo "  $0 install                    # 使用默认配置"
    echo "  COMPOSE_FILE=docker-compose.build.yml $0 install   # 使用其他配置"
    echo "  $0 start                      # 启动服务"
    echo "  $0 info                       # 查看容器信息"
}

# ==================== 检查函数 ====================

check_docker() {
    if ! command -v docker-compose &> /dev/null; then
        echo -e "${RED}错误: docker-compose 未安装${NC}"
        exit 1
    fi
    if [ ! -f "$COMPOSE_FILE" ]; then
        echo -e "${RED}错误: 配置文件 $COMPOSE_FILE 不存在${NC}"
        exit 1
    fi
}

# ==================== 清理函数 ====================

# 清理数据
cleanup() {
    echo -e "${YELLOW}=== 清理旧数据 ===${NC}"

    # 删除 install.lock 文件
    if [ -n "$PHP_APP_LOCAL" ] && [ -f "$PHP_APP_LOCAL/public/install.lock" ]; then
        echo "删除 install.lock..."
        rm -f "$PHP_APP_LOCAL/public/install.lock"
    fi

    # 删除 MySQL 数据目录内容
    if [ -n "$MYSQL_DATA_LOCAL" ] && [ -d "$MYSQL_DATA_LOCAL" ] && [ -n "$(ls -A "$MYSQL_DATA_LOCAL" 2>/dev/null)" ]; then
        echo "删除 MySQL 数据..."
        rm -rf "$MYSQL_DATA_LOCAL"/*
    fi

    # 删除 runtime 目录内容
    if [ -n "$PHP_RUNTIME_LOCAL" ] && [ -d "$PHP_RUNTIME_LOCAL" ] && [ -n "$(ls -A "$PHP_RUNTIME_LOCAL" 2>/dev/null)" ]; then
        echo "删除 runtime 缓存..."
        rm -rf "$PHP_RUNTIME_LOCAL"/*
    fi

    # 设置目录权限为 777
    echo "设置目录权限..."
    [ -n "$PHP_RUNTIME_LOCAL" ] && chmod -R 777 "$PHP_RUNTIME_LOCAL" 2>/dev/null
    # 只设置需要写入的 public 子目录权限
    [ -n "$PHP_APP_LOCAL" ] && chmod -R 777 "$PHP_APP_LOCAL/public/uploads" 2>/dev/null
    # 配置文件设置为可写（不改变可执行位）
    [ -n "$PHP_APP_LOCAL" ] && chmod 666 "$PHP_APP_LOCAL/.env" 2>/dev/null
    [ -n "$PHP_APP_LOCAL" ] && chmod 666 "$PHP_APP_LOCAL/.version" 2>/dev/null
    [ -n "$PHP_APP_LOCAL" ] && chmod 666 "$PHP_APP_LOCAL/.constant" 2>/dev/null
}

# 清理网络
cleanup_network() {
    echo "清理可能存在的冲突网络..."
    
    # 删除可能冲突的网络（docker-compose 默认使用目录名_app_net）
    for net in $(docker network ls --format "{{.Name}}" | grep -E "(app_net|docker_app_net|crmeb_app_net)"); do
        echo "删除网络: $net"
        docker network rm "$net" 2>/dev/null
    done
    
    # 清理未使用的网络
    docker network prune -f 2>/dev/null
}

# ==================== 操作函数 ====================

# 安装（清理数据并启动）
do_install() {
    check_docker
    
    echo -e "${YELLOW}=== 停止旧容器 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" down 2>/dev/null
    
    cleanup
    
    # 清理网络
    cleanup_network
    
    echo -e "${YELLOW}=== 安装并启动 Docker 环境 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" up -d
    
    echo ""
    echo -e "${GREEN}=== 安装完成 ===${NC}"
    echo "配置文件: $COMPOSE_FILE"
    echo "访问地址: http://localhost:${NGINX_PORT_OUT}"
    echo "管理端:   http://localhost:${NGINX_PORT_OUT}/admin"
    echo "查看日志: $0 logs"
}

# 启动
do_start() {
    check_docker
    cleanup_network
    echo -e "${YELLOW}=== 启动容器 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" up -d
    echo ""
    echo -e "${GREEN}=== 启动完成 ===${NC}"
    echo "配置文件: $COMPOSE_FILE"
    echo "访问地址: http://localhost:${NGINX_PORT_OUT}"
}

# 重启
do_restart() {
    check_docker
    echo -e "${YELLOW}=== 重启容器 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" restart
    echo ""
    echo -e "${GREEN}=== 重启完成 ===${NC}"
}

# 停止
do_stop() {
    check_docker
    echo -e "${YELLOW}=== 停止容器 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" down
    echo -e "${GREEN}=== 已停止 ===${NC}"
}

# 删除
do_delete() {
    check_docker
    echo -e "${YELLOW}=== 删除容器和数据 ===${NC}"
    docker-compose -f "$COMPOSE_FILE" down -v
    [ -n "$MYSQL_DATA_LOCAL" ] && rm -rf "$MYSQL_DATA_LOCAL"/* 2>/dev/null
    [ -n "$MYSQL_LOG_LOCAL" ] && rm -rf "$MYSQL_LOG_LOCAL"/* 2>/dev/null
    [ -n "$PHP_RUNTIME_LOCAL" ] && rm -rf "$PHP_RUNTIME_LOCAL"/* 2>/dev/null
    [ -n "$PHP_APP_LOCAL" ] && rm -f "$PHP_APP_LOCAL/public/install.lock" 2>/dev/null
    # 还原文件权限
    echo "还原文件权限..."
    if [ -d "$PHP_APP_LOCAL/.git" ] || [ -d "$PHP_APP_LOCAL/../.git" ]; then
        git -C "$PHP_APP_LOCAL" restore -SW .
    else
        [ -n "$PHP_APP_LOCAL" ] && chmod 644 "$PHP_APP_LOCAL/.env" 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && chmod 644 "$PHP_APP_LOCAL/.version" 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && chmod 644 "$PHP_APP_LOCAL/.constant" 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && find "$PHP_APP_LOCAL/public/uploads" -type d -exec chmod 755 {} \; 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && find "$PHP_APP_LOCAL/public/uploads" -type f -exec chmod 644 {} \; 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && find "$PHP_APP_LOCAL/public/static" -type d -exec chmod 755 {} \; 2>/dev/null
        [ -n "$PHP_APP_LOCAL" ] && find "$PHP_APP_LOCAL/public/static" -type f -exec chmod 644 {} \; 2>/dev/null
    fi
    echo -e "${GREEN}=== 已删除 ===${NC}"
}

# 查看日志
do_logs() {
    check_docker
    docker-compose -f "$COMPOSE_FILE" logs -f
}

# 查看容器状态
do_ps() {
    check_docker
    docker-compose -f "$COMPOSE_FILE" ps
}

# ==================== 信息查看函数 ====================

# 查看所有容器信息
do_info() {
    check_docker
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║               CRMEB Docker 容器信息总览                      ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}配置文件: $COMPOSE_FILE${NC}"
    echo ""
    echo -e "${YELLOW}【访问地址】${NC}"
    echo "  前端地址:     http://localhost:${NGINX_PORT_OUT}/"
    echo "  后台管理:     http://localhost:${NGINX_PORT_OUT}/admin"
    echo "  安装向导:     http://localhost:${NGINX_PORT_OUT}/install (首次访问)"
    echo ""
    echo -e "${YELLOW}【MySQL 数据库】${NC}"
    echo "  容器名称:     ${MYSQL_CONTAINER}"
    echo "  内部地址:     ${MYSQL_CONTAINER}:${MYSQL_PORT_IN}"
    echo "  外部地址:     localhost:${MYSQL_PORT_OUT}"
    echo "  Root密码:     ${MYSQL_ROOT_PWD}"
    echo "  业务用户:     ${MYSQL_USER}"
    echo "  业务密码:     ${MYSQL_PWD}"
    echo "  数据库名:     ${MYSQL_DB}"
    echo "  内部IP:       ${MYSQL_IP}"
    echo "  数据目录:     ${MYSQL_DATA_LOCAL}"
    echo ""
    echo -e "${YELLOW}【Redis 缓存】${NC}"
    echo "  容器名称:     ${REDIS_CONTAINER}"
    echo "  内部地址:     ${REDIS_CONTAINER}:${REDIS_PORT_IN}"
    echo "  外部地址:     localhost:${REDIS_PORT_OUT}"
    echo "  密码:         ${REDIS_PWD}"
    echo "  内部IP:       ${REDIS_IP}"
    echo ""
    echo -e "${YELLOW}【PHP 应用】${NC}"
    echo "  容器名称:     ${PHP_CONTAINER}"
    echo "  应用目录:     ${PHP_APP_LOCAL}"
    echo "  内部IP:       ${PHP_IP}"
    echo ""
    echo -e "${YELLOW}【Nginx 服务】${NC}"
    echo "  容器名称:     ${NGINX_CONTAINER}"
    echo "  外部端口:     ${NGINX_PORT_OUT}"
    echo "  内部IP:       ${NGINX_IP}"
    echo "  配置文件:     ${NGINX_VHOST_LOCAL}"
    echo "  日志目录:     ${NGINX_LOG_LOCAL}"
    echo ""
    echo -e "${YELLOW}【网络配置】${NC}"
    echo "  网络名称:     app_net"
    echo "  网段:         ${NETWORK_SUBNET}"
    echo ""
    echo -e "${YELLOW}【常用命令】${NC}"
    echo "  查看容器状态: $0 ps"
    echo "  查看日志:     $0 logs"
    echo "  进入PHP容器:  docker exec -it ${PHP_CONTAINER} /bin/bash"
    echo "  进入MySQL:    docker exec -it ${MYSQL_CONTAINER} mysql -uroot -p${MYSQL_ROOT_PWD}"
    echo ""
}

# 查看MySQL连接信息
do_info_db() {
    check_docker
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║         MySQL 数据库详细信息            ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}配置文件: $COMPOSE_FILE${NC}"
    echo ""
    echo -e "${YELLOW}【连接信息】${NC}"
    echo "  容器名称:     ${MYSQL_CONTAINER}"
    echo "  内部端口:     ${MYSQL_PORT_IN}"
    echo "  外部端口:     ${MYSQL_PORT_OUT}"
    echo "  内部IP:       ${MYSQL_IP}"
    echo ""
    echo -e "${YELLOW}【账号信息】${NC}"
    echo "  Root用户:     root"
    echo "  Root密码:     ${MYSQL_ROOT_PWD}"
    echo "  业务用户:     ${MYSQL_USER}"
    echo "  业务密码:     ${MYSQL_PWD}"
    echo "  数据库名:     ${MYSQL_DB}"
    echo ""
    echo -e "${YELLOW}【数据存储】${NC}"
    echo "  本地目录:     ${MYSQL_DATA_LOCAL}"
    echo "  日志目录:     ${MYSQL_LOG_LOCAL}"
    echo ""
    echo -e "${YELLOW}【连接命令】${NC}"
    echo "  本地连接:     mysql -h 127.0.0.1 -P ${MYSQL_PORT_OUT} -u root -p${MYSQL_ROOT_PWD}"
    echo "  容器内连接:   docker exec -it ${MYSQL_CONTAINER} mysql -uroot -p${MYSQL_ROOT_PWD}"
    echo ""
}

# 查看Redis连接信息
do_info_redis() {
    check_docker
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║           Redis 缓存详细信息            ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}配置文件: $COMPOSE_FILE${NC}"
    echo ""
    echo -e "${YELLOW}【连接信息】${NC}"
    echo "  容器名称:     ${REDIS_CONTAINER}"
    echo "  内部端口:     ${REDIS_PORT_IN}"
    echo "  外部端口:     ${REDIS_PORT_OUT}"
    echo "  内部IP:       ${REDIS_IP}"
    echo ""
    echo -e "${YELLOW}【认证信息】${NC}"
    echo "  密码:         ${REDIS_PWD}"
    echo ""
    echo -e "${YELLOW}【连接命令】${NC}"
    echo "  本地连接:     redis-cli -h 127.0.0.1 -p ${REDIS_PORT_OUT} -a ${REDIS_PWD}"
    echo "  容器内连接:   docker exec -it ${REDIS_CONTAINER} redis-cli -a ${REDIS_PWD}"
    echo ""
}

# ==================== 交互式菜单 ====================

show_menu() {
    echo ""
    echo "  1、安装并启动"
    echo "  2、启动容器"
    echo "  3、重启容器"
    echo "  4、停止容器"
    echo "  5、删除容器和数据"
    echo "  6、查看日志"
    echo "  7、查看容器信息"
    echo "  8、查看帮助"
    echo "  9、退出"
    echo ""
    read -p "请选择操作 (1-9): " choice
    echo ""
    
    case $choice in
        1) do_install ;;
        2) do_start ;;
        3) do_restart ;;
        4) do_stop ;;
        5) do_delete ;;
        6) do_logs ;;
        7) do_info ;;
        8) show_help; show_menu ;;
        9) echo "已退出"; exit 0 ;;
        *) echo "无效选择，请重试"; show_menu ;;
    esac
}

# ==================== 主逻辑 ====================

if [ $# -eq 0 ]; then
    # 无参数时显示交互式菜单
    show_menu
else
    case "$1" in
        install)
            do_install
            ;;
        start)
            do_start
            ;;
        restart)
            do_restart
            ;;
        stop)
            do_stop
            ;;
        delete)
            do_delete
            ;;
        logs)
            do_logs
            ;;
        ps)
            do_ps
            ;;
        info)
            do_info
            ;;
        info-db)
            do_info_db
            ;;
        info-redis)
            do_info_redis
            ;;
        -h|--help)
            show_help
            ;;
        *)
            show_help
            exit 1
            ;;
    esac
fi
