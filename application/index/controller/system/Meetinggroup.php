<?php
namespace app\index\controller\system;
use app\index\controller\Base;
use app\index\model\MeetingGroupModel;
use think\Db;
use think\Request;
use think\Validate;

class Meetinggroup extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {
        $id = input('request.id');
        if (!empty($id)) {
            $meetinggroup = MeetingGroupModel::get($id);
            $this->assign('meetinggroup', $meetinggroup);
        }
        $user_list = Db::name('user')->field('id,username')->select();
        $this->assign('user_list', $user_list);
        return $this->fetch();
    }
    /**
     * 获取权限列表
     * @return \think\response\Json
     */
    public function getList()
    {
        $post_data = json_decode(input('request.aoData'), true);
        $offset = $post_data['iDisplayStart'];
        $length = $post_data['iDisplayLength'];
        $key = input('request.key');
        //统计总数量
        $select = Db::name('meeting_group');
        if (!empty($key)) {
            $select->where('name', 'like', '%' . $key . '%');
        }
        $total = $select->count();

        //查询结果
        $select = Db::name('meeting_group');
        if (!empty($key)) {
            $select->where('name', 'like', '%' . $key . '%');
        }
        $meetingGroup = $select->field('id,name,join_id,create_uname,create_time')
            ->order('id')
            ->limit($offset, $length)->select();
        foreach ($meetingGroup as &$item) {
            $user = Db::name('user')->whereIn('id', explode(',', $item['join_id']))->field('id,realname')->select();
            if ($user) {
                $item['join_id'] = implode(',', array_column($user, 'realname'));
            }
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }

        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $meetingGroup
        );

        return json($json_data);
    }

    /**
     * 添加/修改会议类型
     * @return \think\response\Json
     */
    public function addMeetingGroup()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'name' => 'require',
            'join_id' => 'require',
        ), array(
            'name.require' => '会议标题必须',
            'join_id.require' => '参会人员必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode('5001', $validate->getError());
        }
        $select = Db::name('meeting_group')->where('name', $params['name']);
        if ($params['id']) {
            $select->where('id', '<>', $params['id']);
        }
        $meetingGroup = $select->find();
        if (!empty($meetingGroup)) {
            return $this->errorCode(5015);
        }
        if (empty($params['id'])) {
            MeetingGroupModel::store($params['name'], $params['join_id']);
        } else {
            MeetingGroupModel::update($params, ['id' => $params['id']]);
        }

        return $this->successCode();
    }

    /**
     * 删除
     * @return \think\response\Json
     */
    public function del()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode('5001', '会议类型ID必须');
        }

        Db::name('meeting_group')->where('id', $id)->delete();
        return $this->successCode();
    }
}