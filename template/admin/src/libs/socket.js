// +---------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +---------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +---------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +---------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +---------------------------------------------------------------------

import { wss, getCookies, setCookies } from '@/libs/util';
import Setting from '@/setting';
import { getWorkermanUrl } from '@/api/kefu';
import Vue from 'vue';
const vm = new Vue();
let wsAdminSocketUrl = getCookies('WS_ADMIN_URL') || '';
let wsKefuSocketUrl = getCookies('WS_CHAT_URL') || '';

class wsSocket {
  constructor(opt) {
    this.ws = null;
    this.opt = opt || {};
    this.reconnectAttempts = 0;
    this.reconnectMaxAttempts = 10; // 最大重连次数
    this.reconnectInterval = 3000; // 重连间隔时间(ms)
    this.init(opt.key);
  }

  onOpen(key = false) {
    this.opt.open && this.opt.open();
    let that = this;
    // this.send({
    //     type: 'login',
    //     data: util.cookies.get('token')
    // }).then(() => {
    //     that.ping();
    // });
    that.ping();
    this.socketStatus = true;
  }

  init(key) {
    let wsUrl = '';
    if (key == 1) {
      wsUrl = wsAdminSocketUrl;
    }
    if (key == 2) {
      wsUrl = wsKefuSocketUrl;
    }
    if (wsUrl) {
      this.wsUrl = wsUrl;
      this.key = key;
      this.connect();
    }
  }

  connect() {
    this.ws = new WebSocket(this.wsUrl);
    this.ws.onopen = this.onOpen.bind(this);
    this.ws.onerror = this.onError.bind(this);
    this.ws.onmessage = this.onMessage.bind(this);
    this.ws.onclose = this.onClose.bind(this);
  }

  ping() {
    var that = this;
    this.timer = setInterval(function () {
      that.send({ type: 'ping' });
    }, 10000);
  }

  send(data) {
    return new Promise((resolve, reject) => {
      try {
        this.ws.send(JSON.stringify(data));
        resolve({ status: true });
      } catch (e) {
        reject({ status: false });
      }
    });
  }

  onMessage(res) {
    this.opt.message && this.opt.message(res);
  }

  onClose() {
    this.timer && clearInterval(this.timer);
    this.opt.close && this.opt.close();
    this.reconnect();
  }

  onError(e) {
    this.opt.error && this.opt.error(e);
    // 错误发生时不立即重连，等待onClose触发重连
  }

  reconnect() {
    if (this.reconnectAttempts < this.reconnectMaxAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => {
        console.log(`WebSocket 尝试第 ${this.reconnectAttempts} 次重连...`);
        this.connect();
      }, this.reconnectInterval);
    } else {
      console.log('WebSocket 重连次数已达上限，停止重连');
    }
  }

  $on(...args) {
    vm.$on(...args);
  }
}

function createSocket(key) {
  getWorkermanUrl().then((res) => {
    wsAdminSocketUrl = res.data.admin;
    wsKefuSocketUrl = res.data.chat;
    setCookies('WS_ADMIN_URL', res.data.admin);
    setCookies('WS_CHAT_URL', res.data.chat);
  });
  return new Promise((resolve, reject) => {
    const ws = new wsSocket({
      key,
      open() {
        resolve(ws);
      },
      error(e) {
        reject(e);
      },
      message(res) {
        const { type, data = {} } = JSON.parse(res.data);
        vm.$emit(type, data);
      },
      close(e) {
        vm.$emit('close', e);
      },
    });
  });
}

export const adminSocket = createSocket(1);
export const Socket = createSocket(2);
