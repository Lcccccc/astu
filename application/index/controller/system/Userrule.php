<?php
namespace app\index\controller\system;

use app\index\controller\Base;
use app\index\model\UserRuleModel;
use think\Db;
use think\Request;
use think\Validate;

class Userrule extends Base
{
    public function index()
    {
        return $this->fetch();
    }
    public function add()
    {
        $id = input('request.id');
        if (!empty($id)) {
            $userRule = UserRuleModel::get($id);
            $this->assign('userRule', $userRule);
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
        $type = input('request.type');
        //统计总数量
        $select = Db::name('user_rule');
        if (!empty($key)) {
            $select->where('name|code', 'like', '%' . $key . '%');
        }
        if ($type>0) {
            $select->where('type',$type);
        }
        $total = $select->count();

        //查询结果
        $select = Db::name('user_rule');
        if (!empty($key)) {
            $select->where('name|code', 'like', '%' . $key . '%');
        }
        if ($type>0) {
            $select->where('type',$type);
        }
        $userRule = $select->field('id,name,type,code,url,status')
            ->order('id')
            ->limit($offset, $length)->select();

        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $userRule
        );

        return json($json_data);
    }

    /**
     * 新增权限
     * @return \think\response\Json
     */
    public function addRule()
    {
        $params = Request::instance()->param();
        $type = get_const_key(UserRuleModel::getUserRuleTypeLabels());
        $validate = new Validate(array(
            'name' => 'require',
            'code' => 'require',
            'type' => "require|in:{$type}",
        ), array(
            'name.require' => '权限名称必须',
            'code.require' => '权限编码必须',
            'type.require' => '权限类型必须',
            'type.in' => "权限类型必须在{$type}之间"
        ));
        if (!$validate->check($params)) {
            return $this->errorCode('5001', $validate->getError());
        }
        $select = Db::name('user_rule')->where('code', $params['code']);
        if ($params['id']) {
            $select->where('id', '<>', $params['id']);
        }
        $userRule = $select->find();
        if (!empty($userRule)) {
            return $this->errorCode(5010);
        }
        if (empty($params['id'])) {
            UserRuleModel::create($params);
        } else {
            UserRuleModel::update($params, ['id' => $params['id']]);
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
            return $this->errorCode(5001, '权限ID必须');
        }
        $userRule = UserRuleModel::get($id);
        $userRule->status = $userRule['status'] == UserRuleModel::STATUS_ENABLE ? UserRuleModel::STATUS_DISABLE : UserRuleModel::STATUS_ENABLE;
        $userRule->save();

        return $this->successCode();
    }
}