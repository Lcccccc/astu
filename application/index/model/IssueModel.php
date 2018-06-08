<?php
namespace app\index\model;

use app\index\controller\api\Meeting;
use think\Config;
use think\Db;
use think\Exception;
use think\Model;
use think\Session;

class IssueModel extends Model
{
    const STATUS_NO_APPROVE = -1;//审核不通过
    const STATUS_PENDING = 0;//未审核
    const STATUS_APPROVE = 1;//审核通过
    const STATUS_ACCEPT = 2;//通过
    const STATUS_NO_ACCEPT = 3;//不通过
    const STATUS_TIMEOUT = 4;//延期

    const MSTATUS_NO_BEGIN = 0;//未开始
    const MSTATUS_ON = 1;//进行中
    const MSTATUS_END = 2;//已完成

    const ACTION_PASS = 1;//通过
    const ACTION_REJECT = 2;//不通过
    const ACTION_GIVE_UP = 3;//弃权
    const ACTION_TIMEOUT = 4;//暂缓

    const ISSUE_CODE = 'issue';

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'issue';
        parent::__construct($data);
    }

    /**
     * 审核议题
     * @param $id
     * @param $status
     * @return bool|int
     */
    public static function approveIssue($id, $status)
    {
        $user = Session::get('ext_user');
        if (!in_array($status, array(IssueModel::STATUS_APPROVE, IssueModel::STATUS_NO_APPROVE))) {
            return 5005;
        }
        //审核议题
        $issue = IssueModel::get($id);
        if ($issue['status'] != IssueModel::STATUS_PENDING) {
            return 5005;
        }
        $curStep = $issue['cur_step'];
        $curAppUser = FlowStepModel::getCurApproveUser(IssueModel::ISSUE_CODE, $curStep);
        if (!in_array($user['id'], $curAppUser)) {
            return 5006;
        }
        $downUser = UserModel::getDownUser($user['id']);
        $uids = array_map(function ($item) {
            return $item['id'];
        }, $downUser);
        if (!in_array('view_all_issue', explode(',', $user['app_rule']))) {
            if (!in_array($issue['create_uid'], $uids)) {
                return 5006;
            }
        }
        $flow = FlowModel::get(['code' => IssueModel::ISSUE_CODE]);
        //获取最大审核级数
        $maxStep = $flow->flowSteps()->max('step');
        if ($curStep > $maxStep) {
            return 5007;
        }

        Db::startTrans();
        try {
            //写入审核记录
            LogModel::store($id, LogModel::ISSUE, ($status == IssueModel::STATUS_APPROVE ? LogModel::ACTION_APPROVE : LogModel::ACTION_REJECT), $curStep);
            //更新当前审核级别
            if (($curStep + 1) <= $maxStep && $status == IssueModel::STATUS_APPROVE) {
                $issue->save(['cur_step' => $curStep + 1]);
            }
            //不通过议题时候处理审核记录
            if ($status == IssueModel::STATUS_NO_APPROVE) {
                $issue->save(['cur_step' => 1]);
                Db::name('log')->where(['tablename' => LogModel::ISSUE, 'actionname' => LogModel::ACTION_APPROVE, 'mid' => $issue['id']])->update(['status' => LogModel::STATUS_DISABLE]);
            }
            //更改议题状态
            if (($status == IssueModel::STATUS_NO_APPROVE) ||
                ($curStep == $maxStep && $status == IssueModel::STATUS_APPROVE)
            ) {
                $issue->save(['status' => $status]);
            }

            //发送审核通知消息给会议创建人及议题创建人
            $title = '议题处理通知';
            $content = '议题' . $issue['name'] . '已经' . ($status == IssueModel::STATUS_APPROVE ? '通过' : '不通过') . ',操作人' . $user['realname'];
            MessageModel::store($title, $content, $issue['create_uid']);

            MessageModel::send_socket(json_encode(array(
                'title' => $title,
                'content' => $content,
                'uid' => $issue['create_uid'],
                'type' => MessageModel::TYPE_PUSH,
            )), null);

            //发送审核通知消息给上级审核级别人员
            $appUser = FlowStepModel::getCurApproveUser(IssueModel::ISSUE_CODE, $curStep + 1);
            if (count($appUser)) {
                $content = '请您处理议题' . $issue['name'] . '。';
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
     * 获取议题状态标签数组或单个标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getIssueStatusLabels($key = null)
    {
        $data = [
            self::STATUS_NO_APPROVE=>'审核不通过',
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVE => '已审核',
            self::STATUS_ACCEPT => '通过',
            self::STATUS_NO_ACCEPT => '不通过',
            self::STATUS_TIMEOUT => '延期',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取议题操作标签数组或单个标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getIssueActionLabels($key = null)
    {
        $data = [
            self::ACTION_PASS => '通过',
            self::ACTION_REJECT => '不通过',
            self::ACTION_GIVE_UP => '弃权',
            self::ACTION_TIMEOUT => '延期',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 会前议题
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function beforeIssue()
    {
        $user = Session::get('ext_user');

        //获取用户所在审核级别
        $flowStep = Db::name('flow')->alias('f')
            ->join('__FLOW_STEP__ fs', 'fs.flow_id=f.id', 'LEFT')
            ->where('f.code', IssueModel::ISSUE_CODE)
            ->where('fs.user_group_id', $user['groupid'])
            ->field('fs.id,fs.step')->find();

        $query = Db::name('issue')->alias('i')->join('__USER__ u', 'u.id=i.create_uid', 'LEFT');
        if ($flowStep) {
            //在审核级别中，则查找下级及本身所有创建议题
            if (in_array('view_all_issue', explode(',', $user['app_rule']))) {
                $query->whereNotIn('i.id', function ($query) use ($user) {
                    $query->name('log')->where('tablename', LogModel::ISSUE)->where('status', LogModel::STATUS_ENABLE)
                        ->where('actionname', LogModel::ACTION_APPROVE)->where('create_uid', $user['id'])->field('mid');
                })->where('i.cur_step', $flowStep['step']);
            } else {
                $query->where(function ($query) use ($user) {
                    $query->whereIn('i.id', function ($query) use ($user) {
                        $uids = array_map(function ($item) {
                            return $item['id'];
                        }, UserModel::getDownUser($user['id']));
                        array_push($uids, $user['id']);
                        $query->name('issue')->whereIn('create_uid', $uids)
                            ->whereNotIn('id', function ($query) use ($user) {
                                $query->name('log')->where('tablename', LogModel::ISSUE)->where('status', LogModel::STATUS_ENABLE)
                                    ->where('actionname', LogModel::ACTION_APPROVE)->where('create_uid', $user['id'])->field('mid');
                            })
                            ->field('id');
                    })->whereOr('i.create_uid', $user['id']);
                })->where('i.cur_step', $flowStep['step']);
            }
            $query->where('i.status', IssueModel::STATUS_PENDING);
        } else {
            //非审核级别中人员只能看到自己议题
            $query->where(function ($query) use ($user) {
                $query->whereIn('i.id', function ($query) use ($user) {
                    $query->name('issue')->whereIn('create_uid', $user['id'])->field('mid');
                })->whereOr('i.create_uid', $user['id']);
            })->whereIn('i.status', [IssueModel::STATUS_PENDING, IssueModel::STATUS_NO_APPROVE]);
        }
        $issue = $query->field('i.id,i.name,i.content,i.deptname,i.create_uname,i.create_time,i.status,u.face')->order('create_time', 'DESC')->select();
        return $issue;
    }
}