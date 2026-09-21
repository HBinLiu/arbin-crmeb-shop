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

    public function lockAliveByCode(string $code): array
    {
        $info = $this->getModel()->where('code', $code)->where('is_del', 0)->lock(true)->find();
        return $info ? $info->toArray() : [];
    }

    public function incUsed(int $id, int $limit): int
    {
        return (int)$this->getModel()->where('id', $id)->where('used_num', '<', $limit)->inc('used_num')->update();
    }

    protected function searchModel(array $where)
    {
        return $this->getModel()->where('is_del', 0)
            ->when(($where['keyword'] ?? '') !== '', function ($query) use ($where) {
                $query->whereLike('title|code', '%' . $where['keyword'] . '%');
            });
    }
}
