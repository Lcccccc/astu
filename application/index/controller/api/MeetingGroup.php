<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\MeetingGroupModel;
use think\Db;
use think\Request;
use think\Validate;

class MeetingGroup extends Common
{
    /**
     * 会议分组
     * @return \think\response\Json
     */
    public function index()
    {
        $meeting_group = Db::name('meeting_group')->field('id,name,join_id')->select();
        return $this->successCode($meeting_group);
    }

    /**
     * 获取分类信息
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议类型ID必须');
        }

        $meetingGroup = MeetingGroupModel::get($id);
        if (!empty($meetingGroup['join_id'])) {
            $user = Db::name('user')->whereIn('id', explode(',', $meetingGroup['join_id']))->field('id,realname,face,phone')->select();
            $meetingGroup['users'] = $user;
        }
        return $this->successCode($meetingGroup);
    }

    /**
     * 新增,修改会议类型
     * @return \think\response\Json
     */
    public function store()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'name' => 'require',
            'join_id' => 'require',
        ), array(
            'name.require' => '会议类型名称必须',
            'join_id.require' => '参会人员必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        if (empty($params['id'])) {
            MeetingGroupModel::store($params['name'], $params['join_id']);
        } else {
            Db::name('meeting_group')->where('id', $params['id'])->update(['name' => $params['name'], 'join_id' => $params['join_id']]);
        }
        return $this->successCode();
    }
}