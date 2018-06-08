<?php
namespace app\index\model;

use think\Config;
use think\Db;
use think\Model;
use think\Session;

class VoteModel extends Model
{
    const ACTION_PASS = 1;//通过
    const ACTION_NO_PASS = 2;//不通过
    const ACTION_GIVE_UP = 3;//弃权
    const ACTION_TIMEOUT = 4;//暂缓
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'vote';
        parent::__construct($data);
    }

    /**
     * 投票
     * @param $mid
     * @param $action
     * @return bool|int
     */
    public static function store($mid, $action)
    {
        $user = Session::get('ext_user');
        //检测是否有投票权限

        //检测是否投票
        $count = self::where('mid', $mid)
            ->where('create_uid', $user['id'])
            ->count();
        if ($count > 0) {
            return 6001;
        }
        $vote = new static();
        $vote->mid = $mid;
        $vote->action = $action;
        $vote->create_uid = $user['id'];
        $vote->create_time = time();
        $vote->save();
        //如果投票者是会议决策人，则修改议题状态
        $where['a.id'] = $mid;
        $issue = Db::name('issue a')->join('meeting b','a.mid = b.id','left')->field('a.id,b.decision_uid')->where($where)->find();
        if($issue['decision_uid'] == $user['id']){
            $issue_save['id'] = $issue['id'];
            if($action == 1){
                $issue_save['status'] = 2;
            }elseif($action == 2){
                $issue_save['status'] = 3;
            }elseif($action == 4){
                $issue_save['status'] = 1;
                $issue_save['mstatus'] = 0;
            }
            Db::name('issue')->update($issue_save);
        }

        return true;
    }

    /**
     * 查看当前议题会员是否已经投票
     * @param $mid
     * @return bool
     */
    public static function isShowVote($mid)
    {
        $user = Session::get('ext_user');
        $group = Db::name('user_group')->alias('ug')
            ->join('__USER_RULE__ ur', 'FIND_IN_SET(ur.id,ug.app_rule)>0', 'LEFT')
            ->where('ur.code', 'vote')
            ->field('ug.id')
            ->select();
        $group = array_column($group, 'id');
        if (!in_array($user['groupid'], $group)) {
            return false;
        }

        $count = self::where('mid', $mid)
            ->where('create_uid', $user['id'])
            ->count();
        return $count > 0 ? false : true;
    }

//    /**
//     * 统计投票情况
//     * @param $issue
//     * @return bool
//     */
//    public static function statisticVote($issue)
//    {
//        $isPass = false;
//        //获取投票数
//        $vote = self::where('mid', $issue['id'])->where('action', self::ACTION_PASS)->select();
//        $vote_count = count($vote);
//        if ($vote_count <= 0) {
//            return $isPass;
//        }
//        $logVote = LogModel::get(['mid', $issue['id'], 'tablename' => LogModel::VOTE, 'actionname' => LogModel::ACTION_VOTE]);
//        $total_vote = count(explode(',', $logVote['remark']));
//
//        return (round($vote_count / $total_vote, 2) * 100) >= floatval($issue['target_per']) ? true : false;
//    }
}