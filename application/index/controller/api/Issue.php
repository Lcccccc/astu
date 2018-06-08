<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\FileHandleModel;
use app\index\model\FileModel;
use app\index\model\FlowModel;
use app\index\model\FlowStepModel;
use app\index\model\IssueModel;
use app\index\model\LogModel;
use app\index\model\MeetingModel;
use app\index\model\UserModel;
use app\index\model\VoteModel;
use think\Db;
use think\Exception;
use think\Request;
use think\Session;
use think\Validate;

class Issue extends Common
{
    /**
     * 议题列表
     * @return \think\response\Json
     */
    public function index()
    {
        $params = Request::instance()->param();
        $status = get_const_key(IssueModel::getIssueStatusLabels());
        $validate = new Validate(array(
            'status' => "require|in:{$status}",
        ), array(
            'status.require' => '状态必须',
            'status.in' => "状态值必须在{$status}之间"
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }

        $iQuery = Db::name('issue')->alias('i')
            ->join($this->prefix . 'user u', 'u.id=i.create_uid')
            ->field('i.id,i.name,i.content,i.deptname,i.status,u.realname,u.face,i.create_time,i.cur_step,i.on_start_date')
            ->where('i.status', $params['status'])
            ->order('i.id', 'desc');
        if (isset($params['type'])) {
            if ($params['type'] == 'add') {
                $iQuery->where('i.mid', 0);
            } elseif ($params['type'] == 'own') {
                $user = Session::get('ext_user');
                $iQuery->where('i.create_uid', $user['id']);
            } elseif ($params['type'] == 'edit') {
                $iQuery->where('i.mid', 0)->whereOr('mid', $params['mid']);
            }
        }
        $issue = $iQuery->order('on_start_date', 'DESC')->select();
        foreach ($issue as &$item) {
            if (date('Y-m-d') == date('Y-m-d', strtotime($item['create_time']))) {
                $item['create_time'] = date('m月d日', strtotime($item['create_time']));
            } else {
                $item['create_time'] = date('H:i', strtotime($item['create_time']));
            }
        }

        return $this->successCode($issue);
    }

    /**
     * 获取会议议题
     * @return \think\response\Json
     */
    public function getIssueListByMeetingId()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议ID必须');
        }
        $status = input('request.status', MeetingModel::STATUS_NOT_BEGIN);

        $user = Session::get('ext_user');
        $meeting = MeetingModel::get($id);
        $query = Db::name('issue')->alias('i')
            ->join('__USER__ u', 'u.id=i.create_uid', 'LEFT')
            ->join('__MEETING__ m', 'm.id=i.mid')
            ->field('i.id,i.name,i.content,i.deptname,u.face,u.realname,i.status,i.create_time')
            ->where('mid', $id)->where('m.status', $status);
        if ($status == MeetingModel::STATUS_NOT_BEGIN) {
            $query->whereNotIn('i.id', function ($query) use ($user) {
                $query->name('log')->where('tablename', LogModel::ISSUE)->where('actionname', LogModel::ACTION_APPROVE)->where('create_uid', $user['id'])->field('mid');
            });
        }

        if ($user['id'] == $meeting['create_uid']) {
            $issue = $query->select();
        } else {
            $uids = array_map(function ($item) {
                return $item['id'];
            }, UserModel::getDownUser($user['id']));
            array_push($uids, $user['id']);
            $issue = $query->whereIn('i.create_uid', $uids)->select();
        }
        foreach ($issue as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }
        return $this->successCode($issue);
    }

    /**
     * 办公室查看议题列表
     * @return \think\response\Json
     */
    public function getIssueList()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议ID必须');
        }
        $meeting = Db::name('meeting')->where('id', $id)->field('id,title,date')->find();

        $issue = Db::name('issue')->where('mid', $id)->field('id,name,deptname,create_uname')->select();

        foreach ($issue as &$item) {
            $item['fenguan'] = $this->getIssue($item['id'], 2);
            $item['shuji'] = $this->getIssue($item['id'], 1);
        }
        return $this->successCode([
            'meeting' => $meeting,
            'issue' => $issue,
        ]);
    }

    /**
     * 编辑议题
     * @param $issue
     * @param $groupId
     * @return int|string
     */
    private function getIssue($id, $groupId)
    {
        $user = Db::name('user')->where('groupid', $groupId)->field('id')->select();

        return Db::name('log')->where('actionname', LogModel::ACTION_APPROVE)
            ->whereIn('create_uid', array_column($user, 'id'))
            ->where('tablename', LogModel::ISSUE)->where('mid', $id)->count();
    }

    /**
     * 议题详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '议题ID不能为空');
        }
        $issue = IssueModel::get($id);
        $issue['file'] = FileModel::getFile(explode(',', $issue['attach_img']));
        //记录查看日志
        LogModel::store($id, LogModel::ISSUE, LogModel::ACTION_VIEW);
        $issue['app_user'] = FlowStepModel::getCurApproveUser(IssueModel::ISSUE_CODE, $issue['cur_step']);

        //审核记录
        $flowLog = Db::name('log')->alias('l')
            ->join('__USER__ u', 'l.create_uid=u.id', 'LEFT')
            ->where([
                'l.mid' => $id,
                'l.actionname' => LogModel::ACTION_APPROVE,
                'l.tablename' => LogModel::ISSUE,
                'l.status' => LogModel::STATUS_ENABLE
            ])->field('l.id,u.realname,u.face,l.remark AS step,l.create_time')
            ->select();
        //获取最大审核级别
        $maxStep = FlowModel::get(['code' => IssueModel::ISSUE_CODE])->flowSteps()->max('step');
        foreach ($flowLog as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
            $item['max_step'] = $maxStep;
        }
        $issue['flow_log'] = $flowLog;

        return $this->successCode($issue);
    }

    /**
     * 用户投票
     * @return \think\response\Json
     */
    public function vote()
    {
        $param = Request::instance()->param();
        $action = get_const_key(IssueModel::getIssueActionLabels());
        $validate = new Validate(array(
            'id' => 'require',
            'action' => "require|in:{$action}",
        ), array(
            'id.require' => '议题ID不能为空',
            'action.require' => '操作不能为空',
            'action.in' => "操作值必须在{$action}之间"
        ));
        if (!$validate->check($param)) {
            return $this->errorCode(5001, $validate->getError());
        }
        //创建投票
        $ret = VoteModel::store($param['id'], $param['action']);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }

        return $this->successCode();
    }


    public function deleteVote()
    {
        $param = Request::instance()->param();
        if (!$param['id']) {
            return $this->errorCode(5001, '缺少议题ID');
        }
        //删除投票
        $where['mid'] = $param['id'];
        $ret = Db::name('vote')->where($where)->delete();
        if (!$ret) {
            return $this->errorCode($ret);
        }

        return $this->successCode();
    }

    /**
     * 获取当前投票数及总可投票人数
     * @return \think\response\Json
     */
    public function getVoteCount()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '当前议题ID必须');
        }
        $meeting = Db::name('meeting')->whereIn('id', function ($query) use ($id) {
            $query->name('issue')->where('id', $id)->field('mid');
        })->field('join_id,decision_uid')->find();
        if (empty($meeting)) {
            return $this->errorCode(5013);
        }
        //获取会议决策者投票状态
        $where['create_uid'] = $meeting['decision_uid'];
        $where['mid'] = $id;
        $issue_vote = Db::name('vote')->where($where)->find();

        //已投票数
        $votes = Db::name('vote')->where('mid', $id)->field('COUNT(1) as count,action')
            ->group('action')->select();
        $pass = $noPass = $giveUp = $voteCount = 0;

        foreach ($votes as $item) {
            if ($item['action'] == VoteModel::ACTION_PASS) {
                $pass = $item['count'];
            } elseif ($item['action'] == VoteModel::ACTION_NO_PASS) {
                $noPass = $item['count'];
            } elseif ($item['action'] == VoteModel::ACTION_GIVE_UP) {
                $giveUp = $item['count'];
            }
            $voteCount += $item['count'];
        }
        //总投票数
        $total_count = Db::name('user_group')->alias('ug')
            ->join('__USER__ u', 'ug.id=u.groupid', 'LEFT')
            ->join('__USER_RULE__ ur', 'FIND_IN_SET(ur.id,ug.app_rule)>0', 'LEFT')
            ->where('ur.code', 'vote')
            ->whereIn('u.id', explode(',', $meeting['join_id']))
            ->field('u.id')
            ->count();
        return $this->successCode([
            'vote_give_up' => $giveUp,
            'vote_no_pass' => $noPass,
            'vote_pass' => $pass,
            'vote_count' => $voteCount,
            'total_vote_count' => $total_count,
            'pass_progress' => ($pass + $noPass) > 0 ? (round($pass / ($pass + $noPass), 4) * 100 . '%') : '0%',
            'not_pass_progress' => ($pass + $noPass) > 0 ? ((1 - round($pass / ($pass + $noPass), 4)) * 100 . '%') : '100%',
            'decision_status' => $issue_vote['action'] ? $issue_vote['action'] : 0
        ]);
    }

    /**
     * 获取当前投票状态
     * @return \think\response\Json
     */
    public function getIssueVote()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '议题ID必须');
        }
        $vote = Db::name('issue a')
            ->join('__MEETING__ b', 'a.mid=b.id')
            ->join('__USER__ c', 'FIND_IN_SET(c.id,b.join_id)', 'LEFT')
            ->join('__VOTE__ v', 'c.id=v.create_uid AND v.mid=a.id', 'LEFT')
            ->field('c.realname AS create_uname,c.face,c.id AS create_uid,v.action,v.create_time')
            ->where('a.id', $id)
            ->whereIn('c.groupid', function ($query) {
                $query->name('user_group')->alias('ug')->join('__USER_RULE__ ur', 'FIND_IN_SET(ur.id,ug.app_rule)>0', 'LEFT')->where('ur.code', 'vote')->field('ug.id');
            })
            ->select();
        $total = $pass = $noPass = $giveUp = $timeout = 0;
        foreach ($vote as &$item) {
            if ($item['create_time']) {
                $item['create_time'] = date('Y-m-d', $item['create_time']);
            } else {
                $item['create_time'] = ' -- ';
            }

            if ($item['action'] == VoteModel::ACTION_PASS) {
                $pass += 1;
            } elseif ($item['action'] == VoteModel::ACTION_NO_PASS) {
                $noPass += 1;
            } elseif ($item['action'] == VoteModel::ACTION_GIVE_UP) {
                $giveUp += 1;
            } elseif ($item['action'] == VoteModel::ACTION_TIMEOUT) {
                $timeout += 1;
            }
            $total += 1;
        }
        $return_data['vote'] = $vote;
        $return_data['pass'] = $pass;
        $return_data['no_pass'] = $noPass;
        $return_data['give_up'] = $giveUp;
        $return_data['total'] = $total;
        $return_data['timeout'] = $timeout;

        return $this->successCode($return_data);
    }


    public function getVoteStatus()
    {
        $param = Request::instance()->param();
        if (!$param['issue_id'] || !$param['uid']) {
            $vote_status = 0;
        } else {
            $where['mid'] = $param['issue_id'];
            $where['create_uid'] = $param['uid'];
            $flag = Db::name('vote')->where($where)->find();
            $vote_status = $flag ? 1 : 0;
        }
        return $this->successCode([
            'vote_status' => $vote_status
        ]);
    }

    /**
     * 切换到下一个议题
     * @return \think\response\Json
     */
    public function nextIssue()
    {
        $fid = input('request.fid');
        $nid = input('request.nid');
        if (empty($fid)) {
            return $this->errorCode(5001, '当前议题ID必须');
        }
        Db::transaction(function () use ($fid, $nid) {
            //更改当前议题状态，统计投票信息
            $fissue = IssueModel::get($fid);
//            $isPass = VoteModel::statisticVote($fissue);
//            $status['status'] = $isPass === true ? IssueModel::STATUS_ACCEPT : IssueModel::STATUS_NO_ACCEPT;
            $status['mstatus'] = IssueModel::MSTATUS_END;//当前议题过会
            $fissue->save($status);

            //变更下一个议题状态为进行中
            if (!empty($nid)) {
                $nissue = IssueModel::get($nid);
                $nissue->mstatus = IssueModel::MSTATUS_ON;
                $nissue->save();
            } else {
                //最后一个议题
                $nissue = IssueModel::get($fid);
                $nissue->mstatus = IssueModel::MSTATUS_END;
                $nissue->save();
            }

            //最后一个议题时结束会议
            $count = Db::name('issue')
                ->where('mid', $fissue['mid'])
                ->whereIn('mstatus', [IssueModel::MSTATUS_ON, IssueModel::MSTATUS_NO_BEGIN])
                ->count();
            if ($count == 0) {
                MeetingModel::get($fissue['mid'])->save(['status' => MeetingModel::STATUS_END]);
            }
        });

        return $this->successCode();
    }

    /**
     * 审核议题
     * @return \think\response\Json
     */
    public function approveIssue()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'id' => 'require',
            'action' => "require",
        ), array(
            'id.require' => '议题ID必须',
            'action.require' => '操作方式必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        $ids = explode(',', $params['id']);
        foreach ($ids as $id) {
            $ret = IssueModel::approveIssue($id, $params['action'] == 'pass' ? IssueModel::STATUS_APPROVE : IssueModel::STATUS_NO_APPROVE);
            if ($ret !== true) {
                return $this->errorCode($ret);
            }
        }
        return $this->successCode();
    }


    /**
     * 添加议题
     * @return \think\response\Json
     */
    public function store()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'name' => 'require',
        ), array(
            'name.require' => '议题标题必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        $issue_data = array(
            'name' => htmlspecialchars($params['name']),
            'content' => htmlspecialchars($params['content']),
            'on_start_date' => $params['on_start_date'],
        );
        // 获取表单上传文件
        $files = request()->file('file');
        $files_str = '';
        $user = Session::get('ext_user');
        Db::startTrans();
        try {
            if (count($files) > 0) {
                foreach ($files as $file) {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $info = $file->move(ROOT_PATH . '/public/uploads/issuefile');
                    if ($info) {
                        $file_data['filename'] = $info->getFilename();
                        $file_data['fileext'] = $info->getExtension();
                        $file_data['filepath'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/issuefile/' . $info->getSaveName();
                        $file_data['create_uid'] = $user['id'];
                        $file_data['create_uname'] = $user['realname'];
                        $file_data['create_time'] = time();
                        $fid = Db::name('files')->insertGetId($file_data);
                        $file_id[] = $fid;
                        $ext = pathinfo($file_data['filepath'], PATHINFO_EXTENSION);
                        if ($ext == 'jpg') {
                            $attach_id[] = $fid;
                        } elseif (in_array($ext, array('pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'doc', 'docx'))) {
                            $files_str .= $_SERVER['HTTP_HOST'] . '/uploads/issuefile/' . $info->getSaveName() . ',';
                        }
                    } else {
                        // 上传失败获取错误信息
                        Db::rollback();
                        return $this->errorCode(5012, $file->getError());
                    }
                }
            }
            if (!empty($file_id)) {
                $issue_data['attachment'] = implode(',', $file_id);
            }
            if (!empty($attach_id)) {
                $issue_data['attach_img'] = implode(',', $attach_id);
            }
            if (empty($params['id'])) {
                $issue_data['create_uid'] = $user['id'];
                $issue_data['create_uname'] = $user['realname'];
                $issue_data['create_time'] = time();
                $issue_id = Db::name('issue')->insertGetId($issue_data);
            } else {
                $issue_id = $params['id'];
                $issue_data['status'] = IssueModel::STATUS_PENDING;
                Db::name('issue')->where('id', $params['id'])->update($issue_data);

                //更改审核日志记录
                Db::name('log')->where([
                    'tablename' => LogModel::ISSUE,
                    'actionname' => LogModel::ACTION_APPROVE,
                    'status' => LogModel::STATUS_ENABLE,
                ])->update(['status' => LogModel::STATUS_ENABLE]);
            }

            if ($files_str) {
                $url = 'http://' . $_SERVER['HTTP_HOST'] . '/index/system.filehandle/insert_issue_file';
                $post_data['files'] = $files_str;
                $post_data['issue_id'] = $issue_id;
                $post_data['create_uid'] = $user['id'];
                FileHandleModel::curl_post($url, $post_data, 1);
            }
        } catch (Exception $e) {
            Db::rollback();
            return $this->errorCode(5000, $e->getMessage());
        }
        Db::commit();
        return $this->successCode($issue_id);
    }

    /**
     * 我的议题列表
     * @return \think\response\Json
     */
    public function ownIssueList()
    {
        $user = Session::get('ext_user');
        $issue = Db::name('issue')->alias('i')
            ->join('__MEETING__ m', 'i.mid=m.id', 'LEFT')
            ->join('__USER__ u', 'u.id=i.create_uid', 'LEFT')
            ->where('i.create_uid', $user['id'])
            ->field('i.id,i.name,i.content,i.deptname,i.status,u.realname,u.face,i.create_time')
            ->order('i.id', 'DESC')->select();
        foreach ($issue as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }

        return $this->successCode($issue);
    }

    /**
     * 会前议题列表
     * @return \think\response\Json
     */
    public function beforeIssueList()
    {
        $issue = IssueModel::beforeIssue();
        foreach ($issue as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }
        return $this->successCode($issue);
    }
}