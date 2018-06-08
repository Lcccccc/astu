<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\FileModel;
use app\index\model\IssueModel;
use app\index\model\MeetingModel;
use app\index\model\MessageModel;
use app\index\model\UserModel;
use app\index\model\VoteModel;
use think\Db;
use think\Loader;
use think\Request;
use think\Session;
use think\Validate;

class Meeting extends Common
{
    /**
     * 会议列表
     * @return \think\response\Json
     */
    public function index()
    {
        $params = Request::instance()->param();

        if (isset($params['status'])) {
            $status = array_keys(MeetingModel::getMeetingStatusLabels());
            if (!in_array($params['status'], $status)) {
                return $this->errorCode('5001', "状态值必须在{$status}之间");
            }
        }

        $mQuery = Db::name('meeting')->alias('m')
            ->join('__ADDRESS__ a', 'a.id=m.address_id', 'LEFT')
            ->field('m.id,m.title,a.name,m.date,m.start_time,m.status');
        if (isset($params['status'])) {
            $mQuery->where('m.status', $params['status']);
        }
        if (!empty($params['date'])) {
            $mQuery->where('m.date', $params['date']);
        }
        if (isset($params['data_type'])) {
            $user = Session::get('ext_user');
            if ($params['data_type'] == 'own') {
                $mQuery->where('m.create_uid', $user['id']);
            } elseif ($params['data_type'] == 'join') {
                $mQuery->where(function ($query) use ($user) {
                    $query->where('FIND_IN_SET(' . $user['id'] . ', m.join_id) > 0')->whereOr('m.create_uid', $user['id']);
                });
            }
        }
        $meeting = $mQuery->order('m.id', 'desc')->select();
        foreach ($meeting as &$item) {
            $item['start_time'] = date('H:i', $item['start_time']);
        }
        return $this->successCode($meeting);
    }

    /**
     * 会议详细信息
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议ID必须');
        }
        //获取当前会议
        $meeting = MeetingModel::get(['id' => $id]);

        //参会人员
        $joins = UserModel::buildMemberList($meeting['join_id']);

        //获取所有议题
        $issue = Db::name('issue')->alias('i')
            ->join('user u', 'u.id=i.create_uid')
            ->field('i.id,i.name,u.realname,i.deptname')
            ->where('i.mid', $id)
            ->select();
        $meeting['addr'] = $meeting->address['name'];
        $meeting['date_time'] = date('Y年m月d日', strtotime($meeting['date']));
        $meeting['start_time'] = date('H:i', $meeting['start_time']);
        $meeting['end_time'] = date('H:i', $meeting['end_time']);
        if ($issue) {
            $meeting['issue'] = implode('<br>', array_column($issue, 'name'));
            $meeting['issue_id'] = implode(',', array_column($issue, 'id'));
        }

        $user = Db::table($this->prefix . 'user')
            ->whereIn('id', explode(',', $meeting['join_id']))
            ->field('id,realname')->select();
        if ($user) {
            $meeting['join'] = implode(',', array_column($user, 'realname'));
        }
        $createUser = UserModel::get($meeting['create_uid']);
        if ($createUser) {
            $meeting['create_name'] = $createUser['realname'];
            $meeting['phone'] = $createUser['phone'];
        }

        return $this->successCode([
            'meeting' => $meeting,
            'joins' => empty($joins) ? array() : $joins,
            'issue' => $issue,
        ]);
    }

    /**
     * 统计该日期是否存在会议
     * @return \think\response\Json
     */
    public function dateCount()
    {
        $date = input('request.date');
        if (empty($date)) {
            return $this->errorCode(5001, '会议日期必须');
        }
        $list = [];
        $user = Session::get('ext_user');
        $meeting = Db::name('meeting')->whereIn('date', explode(',', $date))
            ->where('FIND_IN_SET(' . $user['id'] . ', join_id) > 0')
            ->field('date,COUNT(1) AS count')
            ->group('date')
            ->select();
        foreach ($meeting as $item) {
            $list[$item['date']] = $item['count'];
        }
        return $this->successCode($list);
    }

    /**
     * 添加会议
     * @return \think\response\Json
     */
    public function store()
    {
        $param = Request::instance()->param();
        $validate = Loader::validate('Meeting');
        if (!$validate->check($param)) {
            return $this->errorCode('5001', $validate->getError());
        }
        if (!empty($param['id'])) {
            $meeting = MeetingModel::get($param['id']);
            if ($meeting->status != MeetingModel::STATUS_NOT_BEGIN) {
                return $this->errorCode(5005);
            }
        }
        MeetingModel::store($param);

        return $this->successCode();
    }

    /**
     * 开始会议
     * @return \think\response\Json
     */
    public function startMeeting()
    {
        $id = input('request.id');

        if (empty($id)) {
            return $this->errorCode(5001, '会议ID必须');
        }
        $decision_uid = input('request.decision_uid');
        Db::transaction(function () use ($id, $decision_uid) {
            $meeting = MeetingModel::get($id);
            $meeting->save(['decision_uid' => $decision_uid, 'status' => MeetingModel::STATUS_ON]);

            $issue = IssueModel::get(['mid' => $id]);
            if (!empty($issue)) {
                $issue->save(['mstatus' => IssueModel::MSTATUS_ON]);
            }

            MessageModel::send_socket(json_encode(array(
                'uid' => $meeting['join_id'],
                'create_uid' => $meeting['create_uid'],
                'id' => $meeting['id'],
                'type' => MessageModel::TYPE_START_MEET,
            )), null);
        });
        return $this->successCode();
    }

    public function meeting()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议ID不能为空');
        }
        //获取会议信息
        $meeting = MeetingModel::get($id);

        $issue = Db::name('issue')
            ->field('id,name,content,attachment,create_uid,attach_img,mstatus')
            ->where('mid', $id)
            ->select();
        foreach ($issue as &$item) {
            $item['file'] = FileModel::getFile(explode(',', $item['attach_img']));
            $item['show_vote'] = VoteModel::isShowVote($item['id']);
        }

        return $this->successCode([
            'meeting' => $meeting,
            'issue' => $issue,
        ]);
    }

    /**
     * 会议议题列表
     * @return \think\response\Json
     */
    public function meetingIssueList()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议ID不能为空');
        }
        $meeting = MeetingModel::get($id);

        $issue = Db::name('issue')->where('mid', $id)
            ->field('id,name,create_uname,deptname,status,mstatus')
            ->order('id')
            ->order('mstatus', 'desc')
            ->select();

        return $this->successCode([
            'meeting' => [
                'id' => $meeting['id'],
                'title' => $meeting['title'],
                'date' => date('Y年m月d日', strtotime($meeting['date']))
            ],
            'issue' => $issue,
        ]);
    }

    /**
     * 会后统计
     * @return \think\response\Json
     */
    public function meetingAfterList()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'start_date' => 'require|date',
            'end_date' => 'require|date',
        ), array(
            'start_date.require' => '开始日期不能为空',
            'end_date.require' => '结束日期不能为空',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        $user = Session::get('ext_user');
        $meeting = Db::name('meeting')->alias('m')
            ->join('__ADDRESS__ a', 'm.address_id=a.id', 'LEFT')
            ->field('m.id,m.title,m.join_id,m.date,m.start_time,m.status,a.name')
            ->where(function ($query) use ($user) {
                $query->whereIn('m.id', function ($query) use ($user) {
                    $uids = array_map(function ($item) {
                        return $item['id'];
                    }, UserModel::getDownUser($user['id']));
                    array_push($uids, $user['id']);
                    $query->name('issue')->whereIn('create_uid', $uids)->field('mid');
                })->whereOr('m.create_uid', $user['id']);
            })
            ->where('INSTR(m.join_id,' . $user['id'] . ') > 0')
            ->whereTime('m.date', 'between', [$params['start_date'], date('Y-m-d', strtotime($params['end_date'] . "+1 days"))])
            ->where('m.status', MeetingModel::STATUS_END)
            ->order('m.start_time desc')
            ->select();
        foreach ($meeting as &$item) {
            $item['start_time'] = date('H:i', $item['start_time']);
        }
        return $this->successCode($meeting);
    }

    /**
     * 会前会议列表
     * @return \think\response\Json
     */
    public function beforeMeetingList()
    {
        $meeting = MeetingModel::beforeMeeting();
        foreach ($meeting as &$item) {
            $item['join_id'] = count(explode(',', $item['join_id']));
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }
        return $this->successCode($meeting);
    }

    /**
     * 会议审核
     * @return \think\response\Json
     */
    public function approveMeeting()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'id' => 'require',
            'ids' => "require",
        ), array(
            'id.require' => '会议ID必须',
            'ids.require' => '议题ID必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }

        $ret = MeetingModel::approveMeeting($params['id'], $params['ids']);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }

        return $this->successCode();
    }
}