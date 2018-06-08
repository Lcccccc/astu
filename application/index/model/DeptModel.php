<?php
namespace app\index\model;

use think\Config;
use think\Model;
use think\Session;

class DeptModel extends Model
{
    const  STATUS_ENABLE = 1;//启用
    const  STATUS_DISABLE = 0;//停用

    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'dept';
        parent::__construct($data);
    }

    /**
     * 保存部门
     * @param $params
     */
    public static function store($params)
    {
        $user = Session::get('ext_user');
        self::create([
            'name' => $params['name'],
            'pid' => $params['pid'],
            'sort' => $params['sort'],
            'status' => $params['status'],
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);
    }

    /**
     * 部门树级结构
     * @param $menu
     * @param string $id_field
     * @param string $pid_field
     * @param string $lefthtml
     * @param int $pid
     * @param int $lvl
     * @param int $leftpin
     * @return array
     */
    public static function deptLeft($menu,$id_field='id',$pid_field='pid',$lefthtml = '─' , $pid=0 , $lvl=0, $leftpin=0)
    {
        $arr = array();
        foreach ($menu as $v) {
            if ($v[$pid_field] == $pid) {
                $v['lvl'] = $lvl + 1;
                $v['leftpin'] = $leftpin;
                $v['lefthtml'] = '├' . str_repeat($lefthtml, $lvl);
                $arr[] = $v;
                $arr = array_merge($arr, self::deptLeft($menu, $id_field, $pid_field, $lefthtml, $v[$id_field], $lvl + 1, $leftpin + 20));
            }
        }
        return $arr;
    }
}