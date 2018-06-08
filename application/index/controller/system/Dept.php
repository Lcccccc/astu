<?php
namespace app\index\controller\system;
use app\index\controller\Base;
use app\index\model\DeptModel;
use think\Db;
use think\Request;
use think\Loader;

class Dept extends Base
{
    public function index()
    {
        $alldept = Db::name('dept')->select();
        $arr_all = DeptModel::deptLeft($alldept, 'id', 'pid', '─', 0, 0, 0 * 20);
        $this->assign('dept_list', $arr_all);
        return $this->fetch();
    }

    public function add()
    {
        $id = input('request.id');
        if (!empty($id)) {
            $dept = Db::name('dept')->where('id', $id)->find();
            $this->assign('dept', $dept);
        }
        $dept_list = Db::name('dept')->where('status', DeptModel::STATUS_ENABLE)->select();
        $dept_list = DeptModel::deptLeft($dept_list, 'id', 'pid', '─', 0, 0, 0 * 20);
        $this->assign('dept_list', $dept_list);
        return $this->fetch();
    }

    /**
     * 添加部门
     */
    public function addDept()
    {
        $param = Request::instance()->param();
        $validate = Loader::validate('Dept');

        if (!$validate->check($param)) {
            return $this->errorCode('5001', $validate->getError());
        }

        // id 存在更新，不存在为新增
        if (empty($param['id'])) {
            DeptModel::store($param);
        } else {
            Db::name('dept')->where('id', $param['id'])->update($param);
        }
        return $this->successCode();
    }

    /**
     * 状态更新
     * @return \think\response\Json
     */
    public function status()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '部门ID必须');
        }
        $dept = DeptModel::get($id);
        $dept->save(['status' => ($dept['status'] == DeptModel::STATUS_ENABLE ? DeptModel::STATUS_DISABLE : DeptModel::STATUS_ENABLE)]);
        return $this->successCode();
    }
}