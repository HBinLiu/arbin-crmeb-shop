<template>
  <view class="spread-coupon">
    <view class="mask" @click="close"></view>
    <view class="panel">
      <view class="title">绑定成功，可领取优惠券</view>
      <view class="close iconfont icon-guanbi" @click="close"></view>
      <scroll-view scroll-y class="list">
        <view class="item" v-for="item in list" :key="item.id">
          <view class="money">
            <template v-if="item.coupon_type == 2">
              <text class="num">{{ item.coupon_price / 10 }}</text>
              <text class="symbol">折</text>
            </template>
            <template v-else>
              <text class="symbol">¥</text>
              <text class="num">{{ item.coupon_price }}</text>
            </template>
            <view class="limit">{{ item.use_min_price > 0 ? "满" + item.use_min_price + "元可用" : "无门槛" }}</view>
          </view>
          <view class="info">
            <view class="name">{{ item.title }}</view>
            <view class="time" v-if="item.coupon_time">领取后{{ item.coupon_time }}天内可用</view>
          </view>
          <view class="btn" :class="{ done: item.received }" @click="receive(item)">
            {{ item.received ? "已领取" : "领取" }}
          </view>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script>
import { setCouponReceive } from "@/api/api.js";

export default {
  data() {
    return {
      list: [],
    };
  },
  onLoad() {
    const list = uni.getStorageSync("spread_claim_coupons") || [];
    this.list = list.map((item) => Object.assign({}, item, { received: false }));
    if (!this.list.length) {
      this.close();
    }
  },
  methods: {
    receive(item) {
      if (item.received) return;
      setCouponReceive(item.id)
        .then(() => {
          item.received = true;
          uni.showToast({ title: "领取成功", icon: "none" });
          if (this.list.every((coupon) => coupon.received)) {
            setTimeout(() => this.close(), 600);
          }
        })
        .catch((err) => {
          uni.showToast({ title: err || "领取失败", icon: "none" });
        });
    },
    close() {
      uni.removeStorageSync("spread_claim_coupons");
      const pages = getCurrentPages();
      if (pages.length > 1) {
        uni.navigateBack();
      } else {
        uni.switchTab({ url: "/pages/index/index" });
      }
    },
  },
};
</script>

<style scoped>
.spread-coupon {
  min-height: 100vh;
}
.mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
}
.panel {
  position: fixed;
  left: 40rpx;
  right: 40rpx;
  top: 22vh;
  background: #fff;
  border-radius: 24rpx;
  padding: 40rpx 32rpx 32rpx;
  z-index: 2;
}
.title {
  text-align: center;
  font-size: 32rpx;
  font-weight: 600;
  margin-bottom: 24rpx;
}
.close {
  position: absolute;
  right: 24rpx;
  top: 24rpx;
  font-size: 36rpx;
  color: #999;
}
.list {
  max-height: 640rpx;
}
.item {
  display: flex;
  align-items: center;
  background: #fff7f5;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 20rpx;
}
.money {
  width: 160rpx;
  color: #e93323;
  text-align: center;
}
.num {
  font-size: 40rpx;
  font-weight: 600;
}
.limit,
.time {
  font-size: 22rpx;
  color: #999;
}
.info {
  flex: 1;
  padding: 0 16rpx;
}
.name {
  font-size: 28rpx;
  color: #333;
}
.btn {
  width: 120rpx;
  height: 56rpx;
  line-height: 56rpx;
  text-align: center;
  background: #e93323;
  color: #fff;
  border-radius: 28rpx;
  font-size: 24rpx;
}
.btn.done {
  background: #ccc;
}
</style>
