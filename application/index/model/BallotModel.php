<?php
namespace app\index\model;

use think\Config;
use think\Model;

class BallotModel extends Model
{
    const TYPE_NO_BALLOT = 0;//未投票
    const TYPE_BALLOT = 1;//已投票
    const TYPE_ALL = 2;//所有
    const TYPE_OWN = 3;//发起的投票

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'ballot';
        parent::__construct($data);
    }
}