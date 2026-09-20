<?php
namespace app\dao\agent;

use app\dao\BaseDao;
use app\model\agent\SpreadCodeLog;

class SpreadCodeLogDao extends BaseDao
{
    protected function setModel(): string
    {
        return SpreadCodeLog::class;
    }

    public function getRecordList(int $codeId, int $page, int $limit)
    {
        return $this->getModel()->alias('l')
            ->join('user u', 'u.uid = l.uid', 'LEFT')
            ->where('l.code_id', $codeId)
            ->field('l.id,l.uid,l.add_time,u.nickname,u.phone')
            ->order('l.id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    public function getRecordCount(int $codeId)
    {
        return $this->getModel()->where('code_id', $codeId)->count();
    }
}
