<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/7/11
 * Time: 9:47
 */
namespace app\index\controller\system;

use app\index\controller\Base;
use app\index\model\UserGroupModel;
use app\index\model\UserRuleModel;
use think\Db;
use think\Request;

class Usergroup extends Base
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
     * 分组详情页面
     * @return mixed
     */
    public function add()
    {
        $id = input('request.id');
        $list = array();
        $userRule = UserRuleModel::all(['status' => UserRuleModel::STATUS_ENABLE]);
        foreach ($userRule as $item) {
            $list[$item['type']][] = array(
                'id' => $item['id'],
                'name' => $item['name'],
                'ingroup' => 0,
            );
        }
        if (!empty($id)) {
            $group = UserGroupModel::get($id);
            $list = $this->buildRule(UserRuleModel::TYPE_APP, $group['app_rule'], $list);
            $list = $this->buildRule(UserRuleModel::TYPE_WEB, $group['web_rule'], $list);
            $this->assign('group', $group);
        }
        $this->assign('apprule', $list[UserRuleModel::TYPE_APP]);
        $this->assign('webrule', $list[UserRuleModel::TYPE_WEB]);
        return $this->fetch();
    }

    /**
     * 生成权限组
     * @param $type
     * @param $rule
     * @param $list
     */
    public function buildRule($type, $rule, $list)
    {
        $rule = explode(',', $rule);
        if (isset($list[$type])) {
            $list[$type] = array_map(function ($item) use ($rule) {
                return array(
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'ingroup' => in_array($item['id'], $rule) ? 1 : 0,
                );
            }, $list[$type]);
        }
        return $list;
    }

    /**
     * 更新分组信息
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function saveGroup()
    {
        $param = Request::instance()->param();
        if (!$param['name']) {
            return $this->errorCode('5001', '请输入组名');
        }

        $add_data['name'] = $param['name'];
        $add_data['app_rule'] = $param['app_rule'];
        $add_data['web_rule'] = $param['web_rule'];
        $add_data['status'] = $param['status'];
        $add_data['level'] = $param['level'] ? $param['level'] : 0;
        $add_data['create_time'] = time();
        if (empty($param['id'])) {
            $userGroup = UserGroupModel::get(['name' => $param['name']]);
            if ($userGroup) {
                return $this->errorCode(5011);
            }
            UserGroupModel::create($param);
        } else {
            UserGroupModel::update($param, ['id' => $param['id']]);
        }
        return $this->successCode();
    }

    /**
     * 删除分组
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function deleteGroup()
    {
        $param = Request::instance()->param();
        if (!$param['id']) {
            return $this->errorCode('5001', '缺少ID，删除失败');
        }

        $result = Db::name('user_group')->where('id', $param['id'])->delete();
        if ($result) {
            $message = '分组删除成功';
        } else {
            $message = '分组删除失败，请重试';
        }

        return json($message);
    }

    /**
     * 获取分组列表
     * @return array
     */
    public function getgrouplist()
    {
        $post_data = json_decode(input('request.aoData'), true);
        $offset = $post_data['iDisplayStart'];
        $length = $post_data['iDisplayLength'];
        $key = input('request.key');
        $where = array();
        if (!empty($key)) {
            $where['name'] = array('like', '%' . $key . '%');
        }
        $total = Db::name('user_group')->where($where)->count('*');
        $group = Db::name('user_group a')
            ->join('__USER__ u', 'a.id=u.groupid', 'LEFT')
            ->where($where)
            ->group('a.id')
            ->field('a.id,a.name,a.status,a.level,count(u.id) user_num')
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
}