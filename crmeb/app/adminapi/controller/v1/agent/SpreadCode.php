<?php
namespace app\adminapi\controller\v1\agent;

use app\adminapi\controller\AuthController;
use app\services\agent\SpreadCodeServices;
use think\facade\App;

class SpreadCode extends AuthController
{
    /**
     * @var SpreadCodeServices
     */
    protected $services;

    public function __construct(App $app, SpreadCodeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    public function index()
    {
        $where = $this->request->getMore([
            ['keyword', ''],
        ]);
        return app('json')->success($this->services->getList($where));
    }

    public function save()
    {
        $data = $this->request->postMore([
            ['title', ''],
            ['limit_num', 0],
            ['expire_time', ''],
        ]);
        $this->services->saveCode($data);
        return app('json')->success('添加成功');
    }

    public function delete($id)
    {
        $this->services->deleteCode((int)$id);
        return app('json')->success('删除成功');
    }

    public function setStatus($id, $status)
    {
        $this->services->setStatus((int)$id, (int)$status);
        return app('json')->success((int)$status === 1 ? '已启用' : '已停用');
    }

    public function record($id)
    {
        return app('json')->success($this->services->recordList((int)$id));
    }

    public function qrcode($id)
    {
        return app('json')->success($this->services->qrcode((int)$id));
    }
}
