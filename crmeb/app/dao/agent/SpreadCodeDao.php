<?php
namespace app\dao\agent;

use app\dao\BaseDao;
use app\model\agent\SpreadCode;

class SpreadCodeDao extends BaseDao
{
    protected function setModel(): string
    {
        return SpreadCode::class;
    }

    public function getList(array $where, int $page, int $limit)
    {
        return $this->searchModel($where)->page($page, $limit)->order('id desc')->select()->toArray();
    }

    public function getCount(array $where)
    {
        return $this->searchModel($where)->count();
    }

    protected function searchModel(array $where)
    {
        return $this->getModel()->where('is_del', 0)
            ->when(($where['keyword'] ?? '') !== '', function ($query) use ($where) {
                $query->whereLike('title|code', '%' . $where['keyword'] . '%');
            });
    }
}
