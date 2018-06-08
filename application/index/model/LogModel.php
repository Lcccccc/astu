<?php
namespace app\index\model;

use think\Config;
use think\Model;
use think\Session;

class LogModel extends Model
{
    const ACTION_VIEW = '查看';
    const ACTION_EDIT = '编辑';
    const ACTION_ADD = '新增';
    const ACTION_PASS = '通过';
    const ACTION_APPROVE = '审核';
    const ACTION_REJECT = '拒绝';
    const ACTION_VOTE = '投票';

    const ISSUE = 'issue';
    const MEETING = 'meeting';
    const MESSAGE = 'message';
    const VOTE = 'vote';
    const BALLOT = 'ballot';
    const BALLOT_OPTION = 'ballot_option';

    const STATUS_ENABLE = 1;//有效
    const STATUS_DISABLE = 2;//失效

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'log';
        parent::__construct($data);
    }

    /**
     * 记录操作日志
     * @param int $id
     * @param string $table 表名
     * @param string $action 动作名
     * @param string $remark 备注
     */
    public static function store($id, $table, $action = self::ACTION_ADD, $remark = '')
    {
        $user = Session::get('ext_user');
        $log = new static();
        $log->tablename = $table;
        $log->mid = $id;
        $log->actionname = $action;
        $log->remark = $remark;
        $log->create_uid = $user['id'];
        $log->create_uname = $user['realname'];
        $log->create_time = time();
        $log->save();
    }
}