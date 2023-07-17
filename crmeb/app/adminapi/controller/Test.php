<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/9/12
 */

namespace app\adminapi\controller;

use app\services\user\OutUserServices;

class Test
{
    public function index()
    {
        $data = app()->make(OutUserServices::class)->userInfo(1);
        return app('json')->success(compact('data'));
    }
}

