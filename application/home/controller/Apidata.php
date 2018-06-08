<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/9/18
 * Time: 17:04
 */
namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;

class Apidata extends Controller
{
    //获取菜单详情
    public function get_tree_detail(){
        $param = Request::instance()->param();
        if($param['tree']){
            $where['strTitle'] = $param['tree'];
        }elseif($param['id']){
            $where['id'] = $param['id'];
        }else{
            return array('msg'=>'缺少参数');
        }
        $result = self::tree_data($where);
        return array('code'=>200,'ret'=>$result,'msg'=>'获取菜单数据成功');
    }

    public static function tree_data($where){
        $menu = Db::table('centertree')->where($where)->find();
        if($menu['treeTypeID'] == 1){
            $result = Db::table('centertree')->where(array('intParentID'=>$menu['id']))->select();
            for($i=0;$i<count($result);$i++){
                $wh['id'] = $result[$i]['id'];
                $result[$i]['list'] = self::tree_data($wh);
            }
        }elseif($menu['treeTypeID'] == 2){
            $result = Db::table('singlecontentinfo')->where(array('intCenterTreeID'=>$menu['id']))->find();
        }elseif($menu['treeTypeID'] == 3){
            $result = Db::table('listcontentinfo')->where(array('intCenterTreeID'=>$menu['id'],'intStatus'=>1))->select();
        }elseif($menu['treeTypeID'] == 4){
            $result = Db::table('productinfo')->where(array('intCenterTreeID'=>$menu['id']))->select();
        }elseif($menu['treeTypeID'] == 5){
            $result = Db::table('pictureinfo')->where(array('intCenterTreeID'=>$menu['id']))->select();
        }

        return $result;
    }


    //获取首页案例列表
    public function get_index_case(){
        $where['strTitle']='cases';
        $cases = Db::table('centertree')->where($where)->find();
        $where1['intParentID'] = $cases['id'];
        $list = Db::table('centertree')->where($where1)->select();
        $id_arr = array();
        for($i=0;$i<count($list);$i++){
            $id_arr[] = $list[$i]['id'];
        }
        $where3['intCenterTreeID'] = array('in',$id_arr);
        $where3['intIndexShow']= 1;
        $result = Db::table('listcontentinfo')->where($where3)->order('id desc')->limit(0,8)->select();

        return array('code'=>200,'ret'=>$result,'msg'=>'获取案例数据成功');
    }

    public function get_case_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:8;
        if(!$param['tree_id']){
            $where['strTitle']=$param['strTitle']?$param['strTitle']:'cases';
            $cases = Db::table('centertree')->where($where)->find();
            $where1['intParentID'] = $cases['id'];
            $list = Db::table('centertree')->where($where1)->select();
            $id_arr = array();
            for($i=0;$i<count($list);$i++){
                $id_arr[] = $list[$i]['id'];
            }
            $where3['intCenterTreeID'] = array('in',$id_arr);
            $where3['intStatus'] =1;
            $count = Db::table('listcontentinfo')->where($where3)->count();
            $result = Db::table('listcontentinfo')->where($where3)->field('id,intCenterTreeID,strTitle,strImgUrl,datCreateTime,intClick')->order('id desc')->limit(($page-1)*$num,$num)->select();
        }else{
            $where4['intCenterTreeID'] = $param['tree_id'];
            $where4['intStatus'] =1;
            $count = Db::table('listcontentinfo')->where($where4)->count();
            $result = Db::table('listcontentinfo')->where($where4)->field('id,intCenterTreeID,strTitle,strImgUrl,datCreateTime,intClick')->order('id desc')->limit(($page-1)*$num,$num)->select();
        }
        for($i=0;$i<count($result);$i++){
            $result[$i]['datCreateTime'] = substr($result[$i]['datCreateTime'],0,10);
        }
        return array('list'=>$result,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }

}