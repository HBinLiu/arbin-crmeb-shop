import { getSpreadClaimCoupons } from "@/api/api.js";

let opening = false;

export function openSpreadCouponPopup(list) {
  if (!list || !list.length || opening) return;
  const key = "spread_coupon_prompt_" + list.map((item) => item.id).sort().join("_");
  if (uni.getStorageSync(key)) return;
  opening = true;
  uni.setStorageSync("spread_claim_coupons", list);
  uni.setStorageSync(key, 1);
  uni.navigateTo({
    url: "/pages/annex/spread_coupon/index",
    complete() {
      setTimeout(() => {
        opening = false;
      }, 1500);
    },
  });
}

export function promptSpreadCoupon() {
  getSpreadClaimCoupons()
    .then((res) => {
      openSpreadCouponPopup((res.data && res.data.list) || []);
    })
    .catch(() => {});
}
