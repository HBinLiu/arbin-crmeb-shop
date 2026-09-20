<template>
  <view class="page">
    <view class="card">
      <view class="title">{{ title }}</view>
      <view class="desc">{{ message }}</view>
      <button v-if="!isLogin && !done" class="btn" @click="goLogin">去登录</button>
      <button v-if="failed" class="btn" @click="submit">重试</button>
      <button v-if="done" class="btn" @click="goSpread">推广中心</button>
      <button v-if="done" class="btn plain" @click="goHome">去首页</button>
    </view>
  </view>
</template>

<script>
import { mapGetters } from 'vuex';
import { toLogin } from '@/libs/login.js';
import { spreadCodeClaim } from '@/api/user.js';

export default {
  data() {
    return {
      code: '',
      title: '开通分销员',
      message: '正在确认',
      loading: false,
      done: false,
      failed: false,
      askedLogin: false,
    };
  },
  computed: mapGetters(['isLogin']),
  onLoad(options) {
    this.code = this.readCode(options);
    if (this.code) uni.setStorageSync('spread_code_token', this.code);
  },
  onShow() {
    this.boot();
  },
  methods: {
    readCode(options) {
      if (options.code) return options.code;
      if (!options.scene) return uni.getStorageSync('spread_code_token') || '';
      const scene = decodeURIComponent(options.scene);
      const params = {};
      scene.split('&').forEach((pair) => {
        const index = pair.indexOf('=');
        if (index > -1) params[pair.slice(0, index)] = pair.slice(index + 1);
      });
      return params.c || params.code || '';
    },
    boot() {
      if (!this.code || this.loading || this.done) return;
      if (!this.isLogin) {
        if (this.askedLogin) return;
        this.askedLogin = true;
        this.message = '请先登录';
        this.$Cache.set('login_back_url', '/pages/annex/spread_code/index?code=' + this.code);
        toLogin();
        return;
      }
      this.submit();
    },
    submit() {
      if (!this.code || this.loading) return;
      this.loading = true;
      this.failed = false;
      this.message = '正在开通';
      spreadCodeClaim({ code: this.code })
        .then((res) => {
          this.loading = false;
          this.done = true;
          this.title = '开通成功';
          this.message = res.msg || '已成为分销员';
          uni.removeStorageSync('spread_code_token');
        })
        .catch((err) => {
          this.loading = false;
          this.failed = true;
          this.title = '无法开通';
          this.message = typeof err === 'string' ? err : err.msg || '开通失败';
        });
    },
    goLogin() {
      this.askedLogin = false;
      this.boot();
    },
    goSpread() {
      uni.navigateTo({ url: '/pages/users/user_spread_user/index' });
    },
    goHome() {
      uni.switchTab({ url: '/pages/index/index' });
    },
  },
};
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 80rpx 40rpx;
}
.card {
  background: #fff;
  border-radius: 16rpx;
  padding: 60rpx 40rpx;
  text-align: center;
}
.title {
  font-size: 36rpx;
  font-weight: 600;
}
.desc {
  margin-top: 24rpx;
  color: #666;
  font-size: 28rpx;
  line-height: 1.6;
}
.btn {
  margin-top: 40rpx;
  background: #e93323;
  color: #fff;
  border-radius: 40rpx;
}
.btn.plain {
  margin-top: 24rpx;
  background: #fff;
  color: #e93323;
  border: 1px solid #e93323;
}
</style>
