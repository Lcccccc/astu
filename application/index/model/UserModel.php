<?php

namespace app\index\model;

use rongcloud\RongCloud;
use think\Config;
use think\Db;
use think\Model;

class UserModel extends Model
{
    protected $hidden = ['password'];

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'user';
        parent::__construct($data);
    }

    /**
     * 检测用户是否登录
     * @param $params
     * @return array
     */
    public function checkLoginUser($params)
    {
        if (empty($params['token'])) {
            $user = $this->where('username', $params['username'])->find();
            if (empty($user)) {
                return [false, 5002];
            }
            if ($user['password'] != md5($params['password'])) {
                return [false, 5003];
            }

            //登录刷新token
            $user['token'] = $this->refreshToken($params['username'], $user['id']);
        } else {
            $user = $this->where('token', $params['token'])->find();
            if (empty($user)) {
                return [false, 5004];
            }
        }
        if (empty($user['rongcloud_token'])) {
            $token = UserModel::rongCloudToken($user['id'], $user['realname'], $user['face']);
            $this->where('id', $user['id'])->update(['rongcloud_token' => $token]);
            $user['rongcloud_token'] = $token;
        }
        //设置权限
        if (!empty($user['groupid'])) {
            list($appRule, $webRule) = UserGroupModel::getGroupRule($user['groupid']);
            $user['app_rule'] = $appRule;
            $user['web_rule'] = $webRule;
        }
        return [true, $user];
    }

    /**
     * 获取下级成员
     * @param int $id 会员ID
     * @param array $list  会员
     * @return array
     */
    public static function getDownUser($id, &$list = array())
    {
        $user = self::where('superid', $id)->select();
        if (!empty($user)) {
            foreach ($user as $item) {
                array_push($list, $item);
                self::getDownUser($item['id'], $list);
            }
        }
        return $list;
    }

    /**
     * 刷新token
     * @param $username
     * @param $uid
     * @return string
     */
    public function refreshToken($username, $uid)
    {
        //登录刷新token
        $token = md5($username . time());
        $this->update(array('token' => $token), array('id' => $uid));
        return $token;
    }

    /**
     * 后台用户登录检测
     * @param $params
     * @return array|false|\PDOStatement|string|Model
     */
    public function checkUser($params)
    {
        $user = Db::table('sysadminuser')->where('strUserName', $params['username'])->find();
        if (empty($user)) {
            exception(config('error.5002'));
        }
        if ($user['strUserPass'] != md5($params['password'])) {
            exception(config('error.5003'));
        }
        return $user;
    }

    /**
     * 构造会员列表
     * @param null $uid
     * @return array
     */
    public static function buildMemberList($uid = null)
    {
        if (is_string($uid)) {
            $uid = explode(',', $uid);
        }
        //获取分组
        $gQuery = Db::name('user')->alias('u')
            ->join('user_group ug', 'u.groupid=ug.id');
        if (!empty($uid)) {
            $gQuery->whereIn('u.id', $uid);
        }
        $group = $gQuery->where('u.status', UserGroupModel::STATUE_ENABLE)
            ->field('ug.id,ug.name')
            ->distinct('ug.id')
            ->select();

        $list = [];
        foreach ($group as $item) {
            //获取分组下成员
            $mQuery = Db::name('user');
            if (!empty($uid)) {
                $mQuery->whereIn('id', $uid);
            }
            $member = $mQuery->field('id,face,realname,phone')
                ->where('groupid', $item['id'])
                ->select();

            $list[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'member' => $member,
                'count' => count($member),
            ];
        }
        return $list;
    }

    /**
     * 获取融云token
     * @param int $uid 用户ID
     * @param string $name 用户名字
     * @param string $face 用户头像
     * @return bool
     */
    public static function rongCloudToken($uid,$name,$face)
    {
        $appKey = Config::get('extend.rongcloud_app_key');
        $appSecret = Config::get('extend.rongcloud_app_secret');
        $rongcloud = new RongCloud($appKey, $appSecret);
        $ret = $rongcloud->User()->getToken($uid, $name, $face);
        if (!$ret) {
            return false;
        }
        $ret = json_decode($ret, true);
        return isset($ret['token']) ? $ret['token'] : false;
    }
}