<?php
namespace app\index\model;

use think\Config;
use think\Model;
use think\Session;

class MenuModel extends Model
{
    const STATUS_ENABLED = 1;//启用
    const STATUS_DISABLED = 0;//停用
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'menu';
        parent::__construct($data);
    }

    /**
     * 菜单级别
     * @param $menu
     * @param string $id_field
     * @param string $pid_field
     * @param string $lefthtml
     * @param int $pid
     * @param int $lvl
     * @param int $leftpin
     * @return array
     */
    public static function menuLeft($menu,$id_field='id',$pid_field='pid',$lefthtml = '─' , $pid=0 , $lvl=0, $leftpin=0)
    {
        $arr = array();
        foreach ($menu as $v) {
            if ($v[$pid_field] == $pid) {
                $v['lvl'] = $lvl + 1;
                $v['leftpin'] = $leftpin;
                $v['lefthtml'] = '├' . str_repeat($lefthtml, $lvl);
                $arr[] = $v;
                $arr = array_merge($arr, self::menuLeft($menu, $id_field, $pid_field, $lefthtml, $v[$id_field], $lvl + 1, $leftpin + 20));
            }
        }
        return $arr;
    }

    /**
     * 侧边栏
     * @param $menu
     * @param int $pid
     * @return array
     */
    public static function navs($menu, $pid = 0, $lv = 0)
    {
        $arr = array();
        foreach ($menu as $v) {
            if ($v['pid'] == $pid) {
                $v['level'] = $lv + 1;
                $v['child'] = self::navs($menu, $v['id'], $v['level']);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    /**
     * 新增菜单
     * @param $params
     */
    public static function store($params)
    {
        $user = Session::get('ext_user');
        self::create([
            'name' => $params['name'],
            'url' => $params['url'],
            'pid' => $params['pid'],
            'css' => $params['css'],
            'status' => $params['status'],
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);
    }

    /**
     * 获取状态的标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_DISABLED => '停用',
            self::STATUS_ENABLED => '启用',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}