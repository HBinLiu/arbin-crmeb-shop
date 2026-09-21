(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/kefuIcon/index"],{"060b":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var u=n("efe3"),o=(getApp(),{name:"kefuIcon",props:{ids:{type:Number,default:0},routineContact:{type:Number,default:0},storeInfo:{type:Object,default:function(){}},goodsCon:{type:Number,default:0}},data:function(){return{top:"480"}},mounted:function(){},methods:{goCustomer:function(){(0,u.getCustomer)("/pages/extension/customer_list/chat?productId=".concat(this.ids))},setTouchMove:function(t){t.touches[0].clientY<480&&t.touches[0].clientY>66&&(this.top=t.touches[0].clientY)}},created:function(){}});e.default=o},2525:function(t,e,n){"use strict";var u=n("7825"),o=n.n(u);o.a},7825:function(t,e,n){},b0f3:function(t,e,n){"use strict";n.r(e);var u=n("060b"),o=n.n(u);for(var c in u)["default"].indexOf(c)<0&&function(t){n.d(e,t,(function(){return u[t]}))}(c);e["default"]=o.a},c33e2:function(t,e,n){"use strict";n.d(e,"b",(function(){return u})),n.d(e,"c",(function(){return o})),n.d(e,"a",(function(){}));var u=function(){var t=this.$createElement;this._self._c},o=[]},d97b:function(t,e,n){"use strict";n.r(e);var u=n("c33e2"),o=n("b0f3");for(var c in o)["default"].indexOf(c)<0&&function(t){n.d(e,t,(function(){return o[t]}))}(c);n("2525");var i=n("828b"),f=Object(i["a"])(o["default"],u["b"],u["c"],!1,null,null,null,!1,u["a"],void 0);e["default"]=f.exports}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/kefuIcon/index-create-component',
    {
        'components/kefuIcon/index-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("d97b"))
        })
    },
    [['components/kefuIcon/index-create-component']]
]);
