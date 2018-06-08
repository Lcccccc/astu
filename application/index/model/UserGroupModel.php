<?php
namespace app\index\model;

use think\Config;
use think\Db;
use think\Model;

class UserGroupModel extends Model
{
    const STATUS_DISABLE = 0;//停用
    const STATUE_ENABLE = 1;//启用
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'user_group';
        parent::__construct($data);
    }

    /**
     * 获取上一个等级角色用户
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public static function getParentUser($id)
    {
        $userGroup = UserGroupModel::get($id);//获取当前分组
        //获取当前分组上一个等级
        $parentGroup = Db::name('user_group')->where('level', '>', $userGroup['level'])->order('level')->find();
        $user = [];
        if ($parentGroup) {
            $user = Db::name('user')->where('groupid', $parentGroup['id'])->field('id,realname')->select();
        }
        return $user;
    }

    /**
     * 获取组权限
     * @param int $id 组ID
     * @return array
     */
    public static function getGroupRule($id)
    {
        $userGroup = UserGroupModel::get($id);
        $webRule = explode(',', $userGroup['web_rule']);
        if ($webRule) {
            $webRule = UserRuleModel::getRuleById($webRule, UserRuleModel::TYPE_WEB);
        }
        $appRule = explode(',', $userGroup['app_rule']);
        if ($appRule) {
            //APP权限
            $result = UserRuleModel::getRuleById($appRule, UserRuleModel::TYPE_APP);
            $result = array_map(function ($item) {
                return $item['code'];
            }, $result);
            $appRule = implode(',', $result);
        }
        return array($appRule, $webRule);
    }
}