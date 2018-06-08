<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\AddressModel;
use think\Db;

class Address extends Common
{
    /**
     * 会议室列表
     * @return \think\response\Json
     */
    public function index()
    {
        $address = AddressModel::all(['status' => AddressModel::STATUS_ENABLE]);
        return $this->successCode($address);
    }

    /**
     * 新增/编辑会议室
     * @return \think\response\Json
     */
    public function store()
    {
        $name = input('request.name');
        if (empty($name)) {
            return $this->errorCode(5001, '会议室名称必须');
        }
        $id = input('request.id');
        if (empty($id)) {
            AddressModel::store($name);
        } else {
            Db::name('address')->where('id', $id)->update(['name' => $name]);
        }
        return $this->successCode();
    }
}