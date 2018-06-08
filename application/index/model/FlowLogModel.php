<?php
namespace app\index\model;
use think\Config;
use think\Model;
use think\Session;

class FlowLogModel extends Model
{
    const STATUS_PENDING = 0;//待执行
    const STATUS_PASS = 1;//通过
    const STATUS_NO_PASS = 2;//不通过

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'flow_log';
        parent::__construct($data);
    }

    public static function store($params)
    {
        $user = Session::get('ext_user');
        self::create([
            'opt_user_id' => $user['id'],
            'opt_username' => $user['realname'],
            'create_time' => time(),
            'status' => self::STATUS_PASS,
            'flow_id' => $params['flow_id'],
            'step' => $params['step'],
            'model' => $params['model'],
            'code' => $params['code'],
            'opposite_id' => $params['opposite_id'],
        ]);
    }
}