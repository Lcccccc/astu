<?php
namespace app\index\model;

use think\Config;
use think\Model;
use think\Session;

class MeetingGroupModel extends Model
{
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'meeting_group';
        parent::__construct($data);
    }

    /**
     * 新增会议类型
     * @param string $name 名称
     * @param string $join_id 参会人员
     */
    public static function store($name,$join_id)
    {
        $user = Session::get('ext_user');
        self::create([
            'name' => $name,
            'join_id' => $join_id,
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);
    }
}