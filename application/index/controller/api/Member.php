<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\UserModel;
use think\Db;
use think\Request;

class Member extends Common
{
    /**
     * 会员列表
     * @return \think\response\Json
     */
    public function index()
    {
        $list = UserModel::buildMemberList();
        return $this->successCode($list);
    }

    public function getUser(){
        $where['id'] = input('request.id');
        $user = Db::name('user u')
            ->where($where)
            ->field('u.id,u.username,u.password,u.face,u.realname,u.gender,u.age,u.phone,u.ranking,u.status,u.deptid,u.create_time')
            ->find();
        return $this->successCode($user);
    }

    public function decisionList(){
        $where['id'] = input('request.id');
        $meeting = Db::name('meeting')->where($where)->find();
        $user = Db::name('user')->field('id,username')->select();
        for($i=0;$i<count($user);$i++){
            $user[$i]['is_meeting_decision'] = ($user[$i]['id'] == $meeting['decision_uid'])?1:0;
        }
        return $this->successCode($user);
    }

    public function getbyconversition(){
        $data = input('request.result');
        $data = json_decode($data,true);
        for($i=0;$i<count($data);$i++){
            $where['id'] = $data[$i]['targetId'];
            $user = Db::name('user')->where($where)->find();
            $sent_time = $this->getDateDiff($data[$i]['sentTime']);
            if($data[$i]['objectName']=='RC:TxtMsg'){
                $content = $data[$i]['latestMessage']['text'];
            }elseif($data[$i]['objectName']=='RC:VcMsg'){
                $content = '【语音】';
            }elseif($data[$i]['objectName']=='RC:ImgMsg'){
                $content = '【图片】';
            }elseif($data[$i]['objectName']=='RC:LBSMsg'){
                $content = '【定位】';
            }else{
                $content = '【图文】';
            }
            $return_data[] = array(
                'id'=>$user['id'],
                'face'=>$user['face'],
                'name'=>$user['realname'],
                'content'=>$content,
                'sent_time'=>$sent_time,
                'num'=>$data[$i]['unreadMessageCount']
            );
        }
        return $this->successCode($return_data);
    }

    /**
     * 根据ID获取用户
     * @return \think\response\Json
     */
    public function getUserByIds()
    {
        $ids = input('request.ids');
        if (empty($ids)) {
            return $this->errorCode(5001, '用户ID必须');
        }

        $user = Db::name('user')->whereIn('id', explode(',', $ids))->field('id,realname,face,phone')->select();
        return $this->successCode($user);
    }

    function getDateDiff($dateTimeStamp){
        $minute = 1000 * 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $month = $day * 30;
        $now = time();
        $diffValue = $now - $dateTimeStamp;
        if($diffValue < 0){
            //若日期不符则弹出窗口告之
            //alert("结束日期不能小于开始日期！");
        }
        $monthC =$diffValue/$month;
        $weekC =$diffValue/(7*$day);
        $dayC =$diffValue/$day;
        $hourC =$diffValue/$hour;
        $minC =$diffValue/$minute;
        if($monthC>=1){
            $result= intval($monthC)."个月前";
        }else if($weekC>=1){
            $result=intval($weekC)."周前";
        }else if($dayC>=1){
            $result=intval($dayC)."天前";
        }else if($hourC>=1){
            $result= intval($hourC)."个小时前";
        }else if($minC>=1){
            $result=intval($minC)."分钟前";
        }else{
            $result = "刚刚";
        }
        return $result;
    }

}