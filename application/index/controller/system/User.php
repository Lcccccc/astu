<?php
/**
 * Created by PhpStorm.
 * User: maozhiming
 * Date: 2017/6/22
 * Time: 9:34
 */
namespace app\index\controller\system;

use app\index\controller\Base;
use app\index\model\DeptModel;
use app\index\model\UserGroupModel;
use app\index\model\UserModel;
use think\Db;
use think\Request;
use think\Loader;

class User extends Base
{
    public function index()
    {
        $user = Db::name('user')->field('id,username,is_decision')->select();
        $this->assign('user',$user);
        return $this->fetch();
    }

    public function getUserList()
    {
        $post_data = json_decode(input('request.aoData'), true);
        $offset = $post_data['iDisplayStart'];
        $length = $post_data['iDisplayLength'];
        $key = input('request.key');
        //统计总数量
        $select = Db::name('user')->alias('u')
            ->join('dept d', 'u.deptid=d.id', 'LEFT');
        if (!empty($key)) {
            $select->where('d.name|u.ranking|u.realname|u.username', 'like', '%' . $key . '%');
        }
        $total = $select->count();

        //查询结果
        $select = Db::name('user')->alias('u')
            ->join('__DEPT__ d', 'u.deptid=d.id', 'LEFT')
            ->join('__USER_GROUP__ ug', 'ug.id=u.groupid', 'LEFT')
            ->join('__USER__ uu', 'uu.id=u.superid', 'LEFT');
        if (!empty($key)) {
            $select->where('d.name|u.ranking|u.realname|u.username', 'like', '%' . $key . '%');
        }
        $user = $select->field('u.id,u.username,u.face,u.realname,u.gender,u.age,u.phone,u.groupid,uu.realname AS supername,u.ranking,u.status,d.name,ug.name AS groupname')
            ->order('id')
            ->limit($offset, $length)->select();

        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $user
        );

        return json($json_data);
    }

    public function add()
    {
        $id = input('request.id');
        $alldept = Db::name('dept')->where('status', DeptModel::STATUS_ENABLE)->select();
        $dept_list = DeptModel::deptLeft($alldept, 'id', 'pid', '─', 0, 0, 0 * 20);
        $this->assign('dept_list', $dept_list);
        $group_list = UserGroupModel::all(['status' => UserGroupModel::STATUE_ENABLE]);
        $this->assign('group_list', $group_list);
        if (!empty($id)) {
            $user = UserModel::get($id);
            $this->assign('user', $user);

            $super = UserGroupModel::getParentUser($user['groupid']);
            $this->assign('super_list', $super);
        } else {
            $this->assign('face', 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/face/1.jpg');
        }

        return $this->fetch();
    }

    /**
     * 添加用户
     */
    public function addUser()
    {
        $param = Request::instance()->param();
        $validate = Loader::validate('User');

        if (!$validate->check($param)) {
            return $this->errorCode(5001, $validate->getError());
        }

        $add_data['username'] = $param['username'];
        if (!empty($param['password'])) {
            $add_data['password'] = md5($param['password']);
        }
        $add_data['realname'] = $param['realname'];
        $add_data['gender'] = $param['gender'];
        $add_data['age'] = $param['age'];
        $add_data['phone'] = $param['phone'];
        $add_data['ranking'] = $param['ranking'];
        $add_data['status'] = $param['status'];
        $add_data['deptid'] = $param['deptid'];
        $add_data['face'] = $param['face'];
        $add_data['groupid'] = $param['groupid'];
        $add_data['superid'] = $param['superid'];

        // id 存在更新，不存在为新增
        if (!empty($param['id'])) {
            Db::name('user')->where('id', $param['id'])->update($add_data);
        } else {
            $user = UserModel::get(['username' => $param['username']]);
            if (!empty($user)) {
                return $this->errorCode(5008);
            }
            $add_data['create_time'] = time();

            Db::name('user')->insertGetId($add_data);
        }
        return $this->successCode();
    }

    /**
     * 用户上传头像
     * @return \think\response\Json
     */
    public function uploadFace()
    {
        $file = request()->file('file');
        $info = $file->validate(['size' => 8015678, 'ext' => 'jpg,png,gif,pdf,xsl,xslx,txt,pptx,doc,docx'])->move(ROOT_PATH . '/public/uploads/face/');
        if ($info) {
            // 成功上传后 获取上传信息
            $filename = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/face/' . $info->getSaveName();
        } else {
            return $this->errorCode(5009);
        }
        return $this->successCode($filename);
    }

    /**
     * 删除用户
     * @return \think\response\Json
     */
    public function del()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode('5001', '用户ID必须');
        }

        $user = UserModel::get($id);
        $user->delete();

        return $this->successCode();
    }

    /**
     * 获取上级角色用户
     * @return \think\response\Json
     */
    public function getParentUser()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '分组ID必须');
        }
        $user = UserGroupModel::getParentUser($id);
        return $this->successCode($user);
    }

    public function updateDecisionUser(){
        Db::name('user')->where(array('is_decision'=>1))->update(array('is_decision'=>0));
        $id = input('request.user_id');
        $user = Db::name('user')->where(array('id'=>$id))->update(array('is_decision'=>1));
        return $this->successCode($user,'编辑成功');
    }
}
