<?php
namespace app\model\agent;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

class SpreadCode extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'spread_code';
}
