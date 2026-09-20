<?php
namespace app\api\controller\v1\user;

use app\Request;
use app\services\agent\SpreadCodeServices;

class SpreadCodeController
{
    public function claim(Request $request, SpreadCodeServices $services)
    {
        [$code] = $request->postMore([
            ['code', ''],
        ], true);
        $msg = $services->claim((int)$request->uid(), (string)$code);
        return app('json')->success($msg);
    }
}
