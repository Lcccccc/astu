<?php

namespace app\index\model;

use think\Config;
use think\Db;
use think\Exception;
use think\Model;
use think\Session;

class MeetingModel extends Model
{
    const STATUS_NOT_BEGIN = 0;//未开始
    const STATUS_PENGDING = 1;//上会
    const STATUS_ON = 2;//进行中
    const STATUS_END = 3;//已结束

    const TYPE_DISCUSS = 1;//讨论制
    const TYPE_VOTE = 2;//投票制

    const MEETING_CODE = 'meeting';

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'meeting';
        parent::__construct($data);
    }

    /**
     * 地址
     * @return \think\model\relation\HasOne
     */
    public function address()
    {
        return $this->hasOne('AddressModel', 'id', 'address_id');
    }

    /**
     * 获取会议状态标签数组或单个标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getMeetingStatusLabels($key = null)
    {
        $data = [
            self::STATUS_NOT_BEGIN => '未开始',
            self::STATUS_PENGDING => '上会',
            self::STATUS_ON => '进行中',
            self::STATUS_END => '已结束',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 会议审核
     * @param $id
     * @param $ids
     * @return bool|int
     */
    public static function approveMeeting($id, $ids)
    {
        $meeting = MeetingModel::get($id);
        if (empty($meeting)) {
            return 5000;
        }
        if ($meeting['status'] != MeetingModel::STATUS_NOT_BEGIN) {
            return 5005;
        }
        $curStep = $meeting['cur_step'];
        $user = Session::get('ext_user');
        $curAppUser = FlowStepModel::getCurApproveUser(MeetingModel::MEETING_CODE, $curStep);
        if (!in_array($user['id'], $curAppUser)) {
            return 5006;
        }
        $flow = FlowModel::get(['code' => MeetingModel::MEETING_CODE]);
        //获取最大审核级数
        $maxStep = $flow->flowSteps()->max('step');
        if ($curStep > $maxStep) {
            return 5007;
        }

        Db::startTrans();
        try {

            //写入审核记录
            LogModel::store($id, LogModel::MEETING, LogModel::ACTION_APPROVE, $curStep);

            //更新当前审核级别
            if (($curStep + 1) <= $maxStep) {
                $meeting->save(['cur_step' => $curStep + 1]);
            }
            //更改议题状态
            if ($curStep == $maxStep) {
                $meeting->save(['status' => MeetingModel::STATUS_PENGDING]);
            }
            //处理议题
            $issue = Db::name('issue')->where('mid', $meeting['id'])->field('id')->select();
            $issue = array_column($issue, 'id');
            $noExistIds = array_diff($issue, explode(',', $ids));
            if (count($noExistIds)) {
                //踢出未选中的议题
                Db::name('issue')->whereIn('id', $noExistIds)->update(['mid' => 0]);
            }

            //发送审核通知消息给会议创建人
            $title = '会议处理通知';
            $content = '会议' . $meeting['title'] . '已经通过,操作人' . $user['realname'];
            MessageModel::store($title, $content, $meeting['create_uid']);

            MessageModel::send_socket(json_encode(array(
                'title' => $title,
                'content' => $content,
                'uid' => $meeting['create_uid'],
                'type' => MessageModel::TYPE_PUSH,
            )), null);

            //发送审核通知消息给上级审核级别人员
            $appUser = FlowStepModel::getCurApproveUser(MeetingModel::MEETING_CODE, $curStep + 1);
            if (count($appUser)) {
                $content = '请您处理会议' . $meeting['name'] . '。';
                MessageModel::store($title, $content, implode(',', $appUser));

                MessageModel::send_socket(json_encode(array(
                    'title' => $title,
                    'content' => $content,
                    'uid' => $appUser,
                    'type' => MessageModel::TYPE_PUSH,
                )), null);
            }
        } catch (Exception $e) {
            Db::rollback();
        }
        Db::commit();
        return true;
    }

    /**
     * 会前
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function beforeMeeting()
    {
        $user = Session::get('ext_user');
        //获取用户所在审核级别
        $flowStep = Db::name('flow')->alias('f')
            ->join('__FLOW_STEP__ fs', 'fs.flow_id=f.id', 'LEFT')
            ->where('f.code', MeetingModel::MEETING_CODE)
            ->where('fs.user_group_id', $user['groupid'])
            ->field('fs.id,fs.step')->find();

        $query = Db::name('meeting')->alias('m')
            ->join('__ISSUE__ i', 'i.mid=m.id', 'LEFT')
            ->join('__ADDRESS__ a', 'a.id=m.address_id', 'LEFT');
        if ($flowStep) {
            //在审核级别中，则查找下级及本身所有创建议题
            $query->where(function ($query) use ($user) {
                $query->whereIn('m.id', function ($query) use ($user) {
                    $uids = array_map(function ($item) {
                        return $item['id'];
                    }, UserModel::getDownUser($user['id']));
                    array_push($uids, $user['id']);
                    $query->name('issue')->whereIn('create_uid', $uids)
                        ->whereNotIn('id', function ($query) use ($user) {
                            $query->name('log')->where('tablename', LogModel::ISSUE)->where('status', LogModel::STATUS_ENABLE)
                                ->where('actionname', LogModel::ACTION_APPROVE)->where('create_uid', $user['id'])->field('mid');
                        })
                        ->field('mid');
                })->whereOr('m.create_uid', $user['id']);
            })->where('m.cur_step', $flowStep['step']);
        } else {
            //非审核级别中人员只能看到自己议题
            $query->where(function ($query) use ($user) {
                $query->whereIn('m.id', function ($query) use ($user) {
                    $query->name('issue')->whereIn('create_uid', $user['id'])->field('mid');
                })->whereOr('m.create_uid', $user['id']);
            });
        }

        $meeting = $query->where('m.status', MeetingModel::STATUS_NOT_BEGIN)
            ->field('m.id,m.title,m.join_id,a.name AS address,m.create_uid,m.create_uname,count(i.id) AS count,m.create_time')
            ->group('m.id')
            ->order('m.create_time', 'DESC')
            ->select();

        return $meeting;
    }

    /**
     * 添加会议
     * @param $param
     */
    public static function store($param)
    {
        Db::transaction(function () use ($param) {
            $user = Session::get('ext_user');
            $data = array(
                'title' => $param['title'],
                'address_id' => $param['address_id'],
                'join_id' => $param['join_id'],
                'date' => date('Y-m-d', strtotime($param['date'])),
                'create_uid' => $user['id'],
                'create_uname' => $user['realname'],
                'create_time' => time(),
                'start_time' => strtotime($param['start_time']),
                'end_time' => strtotime($param['end_time']),
                'type' => $param['type'],
            );

            $issueIds = explode(',', $param['issue_id']);
            if (empty($param['id'])) {
                $decision_user = Db::name('user')->where(array('is_decision' => 1))->find();
                $data['decision_uid'] = $decision_user['id'];
                $meeting = MeetingModel::create($data);

                Db::name('issue')->whereIn('id', $issueIds)->update(['mid' => $meeting['id']]);

                //获取第一级审核分组
                $gname = Db::name('flow')->alias('f')
                    ->join('__FLOW_STEP__ fs', 'fs.flow_id=f.id', 'LEFT')
                    ->join('__USER_GROUP__ ug', 'fs.user_group_id=ug.id', 'LEFT')
                    ->where('f.code', MeetingModel::MEETING_CODE)
                    ->where('fs.step', 1)
                    ->field('ug.name')->find();

                //获取所有创建议题的用户
                $issue = Db::name('issue')->where('mid', $meeting['id'])->field('create_uid')->select();
                $createUids = array_column($issue, 'create_uid');
                $title = '会议通知';
                $content = '您的议题已经加入会议' . $meeting['title'] . '，正等待' . $gname['name'] . '处理。';
                MessageModel::store($title, $content, implode(',', $createUids));
                //发送议题通知
                MessageModel::send_socket(json_encode(array(
                    'title' => $title,
                    'content' => $content,
                    'uid' => $createUids,
                    'type' => MessageModel::TYPE_PUSH,
                )), null);

                $gUser = Db::name('user')->whereIn('id', $createUids)->field('superid')->select();
                $gUser = array_column($gUser, 'superid');
                $content = $user['realname'] . '创建了会议' . $meeting['title'] . '，您需要处理该会议议题。';
                MessageModel::store($title, $content, implode(',', $gUser));

                //发送会议创建通知
                MessageModel::send_socket(json_encode(array(
                    'title' => $title,
                    'content' => $content,
                    'uid' => $gUser,
                    'type' => MessageModel::TYPE_PUSH,
                )), null);
            } else {
                MeetingModel::update($data, ['id' => $param['id']]);
                if (count($issueIds) > 0) {
                    //将清除的会议议题还原
                    Db::name('issue')->where('mid', $param['id'])->whereNotIn('id', $issueIds)->update(['mid' => 0]);
                    Db::name('issue')->whereIn('id', $issueIds)->update(['mid' => $param['id']]);
                } else {
                    //没有议题时候还原所有议题
                    Db::name('issue')->where('mid', $param['id'])->update(['mid' => 0]);
                }
            }
        });
    }
}