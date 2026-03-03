# docker-compose 快速运行项目

## 1、安装docker
docker 官网下载
https://www.docker.com/products/docker-desktop
或命令安装
```
curl -sSL https://get.daocloud.io/docker | sh
```
## 2、安装docker-compose
https://www.runoob.com/docker/docker-compose.html

## 3、下载CRMEB程序
建议去下载最新开源代码 https://gitee.com/ZhongBangKeJi/CRMEB
程序放到docker 同级目录下

## 4、启动项目
```
进入docker-compose目录 cd /docker

运行命令：
```
docker-compose up -d

```
## 5、访问CRMEB 系统
移动端访问地址：http://localhost:8011/
PC端访问地址：http://localhost:8011/admin


## 6、安装CRMEB
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


