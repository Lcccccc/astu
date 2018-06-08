<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\VoteModel;
use think\Db;

class Vote extends Common
{
    /**
     * 投票记录信息
     */
    public function index()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '议题ID必须');
        }

        $vote = Db::name('vote')->alias('v')
            ->join('__USER__ u', 'u.id=v.create_uid', 'LEFT')
            ->where('v.mid', $id)
            ->field('v.id,v.action,v.create_time,v.create_uid,u.username create_uname,u.face')
            ->select();
        $total = count($vote);

        $pass = $noPass = $giveUp = 0;
        foreach ($vote as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
            if ($item['action'] == VoteModel::ACTION_PASS) {
                $pass += 1;
            } elseif ($item['action'] == VoteModel::ACTION_NO_PASS) {
                $noPass += 1;
            } elseif ($item['action'] == VoteModel::ACTION_GIVE_UP) {
                $giveUp += 1;
            }
        }
        $return_data['vote'] = $vote;
        $return_data['pass'] = $pass;
        $return_data['no_pass'] = $noPass;
        $return_data['give_up'] = $giveUp;
        $return_data['total'] = $total;

        return $this->successCode($return_data);
    }
}