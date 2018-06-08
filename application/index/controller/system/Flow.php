<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/7/12
 * Time: 16:28
 */
namespace app\index\controller\system;
use app\index\controller\Base;
use think\Config;
use think\Db;
use think\Request;
use think\Loader;

class Flow extends Base
{
    /**
     * 默认列表
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 获取分组列表
     * @return array
     */
    public function getflowlist()
    {
        $post_data = json_decode(input('request.aoData'), true);
        $offset = $post_data['iDisplayStart'];
        $length = $post_data['iDisplayLength'];
        $key = input('request.key');
        $where = array();
        if (!empty($key)) {
            $where['name'] = array('like', '%' . $key . '%');
        }
        $total = Db::name('flow')->where($where)->count('*');
        $group = Db::name('flow a')
            ->join('flow_step b', 'a.id=b.flow_id', 'left')
            ->where($where)
            ->group('a.id')
            ->field('a.id,a.name,a.model,a.code,count(b.id) step_num')
            ->limit($offset, $length)
            ->select();
        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $group
        );

        return $json_data;
    }

    /**
     * 流程详情页面
     * @return mixed
     */
    public function add()
    {
        $where['id'] = input('request.id');
        $flow = Db::name('flow')->where($where)->find();

        $where1['flow_id'] = input('request.id');
        $step = Db::name('flow_step a')->join('user_group b','a.user_group_id=b.id','left')->field('a.*,b.name user_group_name')->where($where1)->select();

        $user_group = Db::name('user_group')->field('id,name')->select();
        $this->assign('flow',$flow);
        $this->assign('user_group',$user_group);
        $this->assign('step',$step);
        return $this->fetch();
    }


    /**
     * 更新分组信息
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function saveFlow()
    {
        $param = Request::instance()->param();
        if (!$param['name']) {
            return $this->errorCode('5001', '请输入流程名称');
        }

        $add_data['name'] = $param['name'];
        $add_data['model'] = $param['model'];
        $add_data['code'] = $param['code'];
        // id 存在更新，不存在为新增
        if (isset($param['id'])) {
            Db::name('flow')->where('id', $param['id'])->update($add_data);
        } else {
            Db::name('flow')->data($add_data)->insert();
        }

        return $this->successCode();
    }

    /**
     * 删除流程
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function deleteFlow()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode('5001', '流程ID必须');
        }
        Db::name('flow')->where('id', $id)->delete();
        return $this->successCode();
    }

    /**
     * 重置步骤
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function resetStep()
    {
        $param = Request::instance()->param();
        $add_data = array();
        for ($i = 1; $i < count($param['step_name']); $i++) {
            if (!$param['step_name'][$i] || !$param['user_group'][$i]) {
                continue;
            }
            $add_data[] = array(
                'name' => $param['step_name'][$i],
                'user_group_id' => $param['user_group'][$i],
                'step' => $i,
                'flow_id' => $param['flow_id']
            );
        }
        if (count($add_data) <= 0) {
            return $this->errorCode('5001', '请输入完整数据');
        }
        //删除原有流程并添加新流程
        Db::transaction(function () use ($param, $add_data) {
            if (!empty($param['flow_id'])) {
                Db::name('flow_step')->where('flow_id', $param['flow_id'])->delete();
            }
            Db::name('flow_step')->insertAll($add_data);
        });

        return $this->successCode();
    }

    /**
     * 清空步骤
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delStep()
    {
        $flow_id = input('request.flow_id');
        if (empty($flow_id)) {
            return $this->errorCode('5001', '流程ID必须');
        }
        Db::name('flow_step')->where('flow_id', $flow_id)->delete();

        return $this->successCode();
    }
}