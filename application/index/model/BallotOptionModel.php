<?php
namespace app\index\model;
use think\Config;
use think\Model;

class BallotOptionModel extends Model
{
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'ballot_option';
        parent::__construct($data);
    }
}