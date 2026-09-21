require('../../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/goods/lottery/components/noticeBar"],{"1f7d":function(t,n,a){"use strict";a.r(n);var e=a("d933"),i=a.n(e);for(var r in e)["default"].indexOf(r)<0&&function(t){a.d(n,t,(function(){return e[t]}))}(r);n["default"]=i.a},"7c3f":function(t,n,a){"use strict";a.r(n);var e=a("c5ba"),i=a("1f7d");for(var r in i)["default"].indexOf(r)<0&&function(t){a.d(n,t,(function(){return i[t]}))}(r);a("d166");var s=a("828b"),o=Object(s["a"])(i["default"],e["b"],e["c"],!1,null,"303a5a66",null,!1,e["a"],void 0);n["default"]=o.exports},c5ba:function(t,n,a){"use strict";a.d(n,"b",(function(){return e})),a.d(n,"c",(function(){return i})),a.d(n,"a",(function(){}));var e=function(){var t=this.$createElement,n=(this._self._c,this.$t("恭喜您")),a=this.$t("获得");this.$mp.data=Object.assign({},{$root:{m0:n,m1:a}})},i=[]},d166:function(t,n,a){"use strict";var e=a("f0c1"),i=a.n(e);i.a},d933:function(t,n,a){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={name:"noticeBar",data:function(){return{animateUp:!1,listData:JSON.parse(JSON.stringify(this.showMsg)),timer:null}},props:{showMsg:{type:Array}},mounted:function(){this.timer=setInterval(this.scrollAnimate,2500)},methods:{scrollAnimate:function(){var t=this;this.animateUp=!0,setTimeout((function(){t.listData.push(t.listData[0]),t.listData.shift(),t.animateUp=!1}),500)}},destroyed:function(){clearInterval(this.timer)}};n.default=e},f0c1:function(t,n,a){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/goods/lottery/components/noticeBar-create-component',
    {
        'pages/goods/lottery/components/noticeBar-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("7c3f"))
        })
    },
    [['pages/goods/lottery/components/noticeBar-create-component']]
]);
