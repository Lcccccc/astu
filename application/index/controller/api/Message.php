<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\FlowStepModel;
use app\index\model\IssueModel;
use app\index\model\LogModel;
use app\index\model\MeetingModel;
use app\index\model\MessageModel;
use app\index\model\UserModel;
use think\Db;
use think\Request;
use think\Session;

class Message extends Common
{
    /**
     * 首页通知消息
     * @return \think\response\Json
     */
    public function main()
    {
        $user = Session::get('ext_user');
        $list = [];
        $issueNoAgree = $this->noAgreeIssueCount($user);
        $count = count($issueNoAgree);
        if ($count > 0) {
            $list['issue_no_agree'] = [
                'count' => $count,
                'title' => $issueNoAgree[0]['name'],
                'time' => date('H:i', $issueNoAgree[0]['create_time']),
            ];
        }
        $ballot = Db::name('ballot')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', receid) > 0')
                    ->whereOr('receid', '');
            })->field('id,title,create_time')->order('id', 'DESC')->find();
        if ($ballot) {
            $count = $this->ballotCount($user);

            $list['ballot'] = [
                'count' => $count,
                'title' => $ballot['title'],
                'time' => date('H:i', $ballot['create_time']),
            ];
        }

        $message = Db::name('message')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', receid) > 0')
                    ->whereOr('receid', '');
            })->field('id,title,create_time')->order('id', 'desc')->find();
        if ($message) {
            $count = $this->messageCount($user);
            $list['message'] = [
                'count' => $count,
                'title' => $message['title'],
                'time' => date('H:i', $message['create_time']),
            ];
        }
        $meeting = $this->meetingCount($user);

        $count = count($meeting);
        if ($count > 0) {
            $list['meeting'] = [
                'count' => $count,
                'title' => $meeting[0]['title'],
                'time' => date('H:i', $meeting[0]['create_time']),
            ];
        }
        return $this->successCode($list);
    }

    /**
     * 发送socket消息
     * @return mixed
     */
    public function send_socket(){
        $to = input('request.to_uid');
        $content = input('request.content');
        $result =  MessageModel::send_socket($content,$to);
        return $this->successCode($result);
    }

    /**
     * 添加通知
     * @return \think\response\Json
     */
    public function store()
    {
        $param = Request::instance()->param();
        if (empty($param['title'])) {
            return $this->errorCode(5001, '标题必须');
        }
        MessageModel::store($param['title'], $param['content'], $param['receid']);
        return $this->successCode();
    }

    /**
     * 查看单条消息
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '消息ID必须');
        }
        $message = MessageModel::get($id);
        $viewLog = Db::name('log')->alias('l')->where('mid', $id)
            ->join('__USER__ u', 'u.id=l.create_uid')
            ->where('actionname', LogModel::ACTION_VIEW)
            ->where('tablename', LogModel::MESSAGE)
            ->field('l.id,u.realname,u.face,l.create_time')
            ->order('l.id', 'desc')
            ->select();
        foreach ($viewLog as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }
        //记录查看日志
        LogModel::store($id, LogModel::MESSAGE, LogModel::ACTION_VIEW);

        $message['views'] = $viewLog;
        return $this->successCode($message);
    }

    public function index()
    {
        $user = Session::get('ext_user');
        //构建子查询
        $subQuery = Db::name('log')
            ->field(' COUNT(mid) AS `count`,mid,create_uid')
            ->where('tablename', LogModel::MESSAGE)
            ->where('actionname', LogModel::ACTION_VIEW)
            ->where('create_uid', $user['id'])
            ->group('mid,create_uid')
            ->buildSql();

        $message = Db::name('message')->alias('m')
            ->join($subQuery . ' l', 'l.mid=m.id', 'LEFT')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', m.receid)>0')
                    ->whereOr('m.receid', '');
            })->field('m.id,m.title,m.content,m.create_uid,m.create_uname,m.create_time,l.count')
            ->order('m.id', 'desc')
            ->select();

        foreach ($message as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        return $this->successCode($message);
    }

    /**
     * 不同意议题
     * @param $user
     * @return false|\PDOStatement|string|\think\Collection
     */
    private function noAgreeIssueCount($user)
    {
        $issueNoAgree = Db::name('issue')
            ->where(['status' => IssueModel::STATUS_NO_APPROVE, 'create_uid' => $user['id']])
            ->field('id,name,create_time')
            ->order('id', 'desc')
            ->select();
        return $issueNoAgree;
    }

    /**
     * 投票
     * @param $user
     * @return int|string
     */
    private function ballotCount($user)
    {
        $count_ballot = Db::name('ballot')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', receid) > 0')
                    ->whereOr('receid', '');
            })->whereNotIn('id', function ($query) use ($user) {
                $query->name('log')->where('tablename', LogModel::BALLOT)
                    ->where('actionname', LogModel::ACTION_VOTE)
                    ->where('create_uid', $user['id'])
                    ->field('mid');
            })->field('id,title,create_time')
            ->count();
        return $count_ballot;
    }

    /**
     * 消息总数
     * @param $user
     * @return int|string
     */
    private function messageCount($user)
    {
        $count_message = Db::name('message')->alias('m')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', m.receid) > 0')
                    ->whereOr('m.receid', '');
            })->whereNotIn('id', function ($query) use ($user) {
                $query->name('log')->where('tablename', LogModel::MESSAGE)
                    ->where('actionname', LogModel::ACTION_VIEW)
                    ->where('create_uid', $user['id'])
                    ->field('mid');
            })->field('m.id,m.title,m.content,m.create_time')
            ->count();
        return $count_message;
    }

    /**
     * 会议统计
     * @param $user
     * @return false|\PDOStatement|string|\think\Collection
     */
    private function meetingCount($user)
    {
        $meeting = Db::name('meeting')->field('id,title,create_time')
            ->where('FIND_IN_SET(' . $user['id'] . ', join_id) > 0')
            ->where('status', MeetingModel::STATUS_ON)
            ->order('id', 'desc')
            ->select();
        return $meeting;
    }
    /**
     * 获取主页消息总数
     * @return \think\response\Json
     */
    public function main_num()
    {
        $user = Session::get('ext_user');
        //获取消息总数
        $issueNoAgree = $this->noAgreeIssueCount($user);
        $count_no_agree = count($issueNoAgree);
        //获取投票总数
        $count_ballot = $this->ballotCount($user);
        //获取消息总数
        $count_message = $this->messageCount($user);
        //获取进行中会议总数
        $meeting = $this->meetingCount($user);
        $count_meeting = count($meeting);

        $main_message_total = $count_no_agree + $count_ballot + $count_meeting + $count_message;

        //获取会前列表总数
        $before_meeting = MeetingModel::beforeMeeting();

        $before_issue = IssueModel::beforeIssue();

        return $this->successCode(array('main_message_total' => $main_message_total, 'before_meeting_total' => count($before_meeting) + count($before_issue)));
    }
}