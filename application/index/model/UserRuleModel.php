<?php
namespace app\index\model;

use think\Config;
use think\Db;
use think\Model;

class UserRuleModel extends Model
{
    const TYPE_APP = 1;//app权限
    const TYPE_WEB = 2;//后台权限

    const STATUS_ENABLE = 1;//
    const STATUS_DISABLE = 0;//

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'user_rule';
        parent::__construct($data);
    }

    /**
     * 获取权限类型数组或单个标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getUserRuleTypeLabels($key = null)
    {
        $data = [
            self::TYPE_APP => 'APP权限',
            self::TYPE_WEB => '后台权限',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取权限
     * @param string|array $ids 权限ID
     * @param int $type 权限类型
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getRuleById($ids, $type)
    {
        $query = self::field('id,name,type,code,url');
        if (is_array($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->where('id', $ids);
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }
        return $query->select();
    }

    /**
     * 获取用户权限ID列表
     * @param $uid
     */
    public static function getUserRule($uid){
        $where['a.id'] = $uid;
        $user_group = Db::name('user a')
            ->join('_user_group_ b','a.groupid=b.id','left')
            ->field('b.web_rule')
            ->where($where)
            ->find();
        $rules = array_values(array_filter(array_unique(explode(',',$user_group['web_rule']))));
        return $rules;
    }
}