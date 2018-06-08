<?php
namespace app\index\controller\system;

use app\index\controller\Base;
use app\index\model\AddressModel;
use think\Db;
use think\Request;
use think\Validate;

class Address extends Base
{
    public function index()
    {
        return $this->fetch();
    }
    public function add()
    {
        $id = input('request.id');
        if (!empty($id)) {
            $address = AddressModel::get($id);
            $this->assign('address', $address);
        }
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
        $select = Db::name('address');
        if (!empty($key)) {
            $select->where('name', 'like', '%' . $key . '%');
        }
        $total = $select->count();

        //查询结果
        $select = Db::name('address');
        if (!empty($key)) {
            $select->where('name', 'like', '%' . $key . '%');
        }
        $address = $select->field('id,name,create_uname,create_time,status')
            ->order('id')
            ->limit($offset, $length)->select();
        foreach ($address as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }

        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $address
        );

        return json($json_data);
    }

    /**
     * 新增权限
     * @return \think\response\Json
     */
    public function addAddress()
    {
        $params = Request::instance()->param();
        if (empty($params['name'])) {
            return $this->errorCode('5001', '权限名称必须');
        }
        $select = Db::name('address')->where('name', $params['name']);
        if ($params['id']) {
            $select->where('id', '<>', $params['id']);
        }
        $address = $select->find();
        if (!empty($address)) {
            return $this->errorCode(5014);
        }
        if (empty($params['id'])) {
            AddressModel::store($params['name']);
        } else {
            AddressModel::update($params, ['id' => $params['id']]);
        }

        return $this->successCode();
    }

    /**
     * 修改权限状态
     * @return \think\response\Json
     */
    public function status()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '会议室ID必须');
        }
        $address = AddressModel::get($id);
        $address->status = $address['status'] == AddressModel::STATUS_ENABLE ? AddressModel::STATUS_DISABLE : AddressModel::STATUS_ENABLE;
        $address->save();

        return $this->successCode();
    }
}