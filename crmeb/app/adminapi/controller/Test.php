<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/9/12
 */

namespace app\adminapi\controller;

class Test
{
    public function index()
    {
        json(['code' => 0, 'msg' => '支付成功']);
        app('json')->success('支付成功');
    }
}


