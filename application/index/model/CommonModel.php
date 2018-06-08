<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/9/8
 * Time: 18:54
 */
namespace app\index\model;

use think\Config;
use think\Model;
$tree = 0;
class CommonModel extends Model
{
    public static function tree($arr,$id_str='id',$parent_str='parent_id',$first =0,$lvl=1){
        $tree = array();
        foreach($arr as $row){
            if($row[$parent_str]==$first){
                $row['lev'] = $lvl;
                $tmp = self::tree($arr,$id_str,$parent_str,$row[$id_str],$lvl+1);
                if($tmp){
                    $row['son']=$tmp;
                }else{
                    $row['son'] = array();
                }
                $tree[]=$row;
            }
        }
        return $tree;
    }

    /**
     * 树型结构
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
}