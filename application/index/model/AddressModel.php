<?php
namespace app\index\model;
use think\Config;
use think\Db;
use think\Model;
use think\Session;

class AddressModel extends Model
{
    const STATUS_ENABLE = 1;//启用
    const STATUS_DISABLE = 0;//停用

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'address';
        parent::__construct($data);
    }

    /**
     * 新增会议室
     * @param $name
     */
    public static function store($name)
    {
        $user = Session::get('ext_user');
        self::create([
            'name' => $name,
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);
    }
}