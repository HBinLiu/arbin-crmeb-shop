<?php
namespace app\model\agent;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

class SpreadCodeLog extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'spread_code_log';
}
