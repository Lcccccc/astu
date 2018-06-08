<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/9/25
 * Time: 9:27
 */
namespace app\index\controller\content;

use app\index\controller\Base;
use app\index\model\CommonModel;
use app\index\model\FileModel;
use think\Db;
use think\Request;

class Infor extends Base
{
    public function index(){
        return $this->fetch('cooperation');
    }

    public function cooperation(){
        return $this->fetch();
    }

    public function cooperationDetail(){
        $param = Request::instance()->param();
        if(!$param['id']){
            $this->error('缺少必要参数');
        }
        $cooperation = Db::table('cooperation')->where(array('id'=>$param['id']))->find();
        $this->assign('cooperation',$cooperation);
        return $this->fetch();
    }

    public function recruit(){
        return $this->fetch();
    }


    //获取合作列表
    public function get_cooperation_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $count = Db::table('cooperation')->count();
        $coop_list = Db::table('cooperation a')->limit(($page-1)*$num,$num)->order('id desc')->select();
        return array('coop_list'=>$coop_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }
    //删除合作信息
    public function delete_cooperation(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('cooperation')->where(array('id'=>$param['id']))->delete();
            $message = '删除成功';
        }else{
            $message = '删除失败';
        }
        return json($message);
    }


    //获取招聘列表
    public function get_recruit_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $count = Db::table('recruit')->count();
        $recruit_list = Db::table('recruit a')->limit(($page-1)*$num,$num)->order('intStatus asc,id desc')->select();
        return array('recruit_list'=>$recruit_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }
    //删除招聘信息
    public function delete_recruit(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('recruit')->where(array('id'=>$param['id']))->delete();
            $message = '删除成功';
        }else{
            $message = '删除失败';
        }
        return json($message);
    }
    //处理招聘信息
    public function handle_recruit(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('recruit')->where(array('id'=>$param['id']))->update(array('intStatus'=>1));
            $message = '处理成功';
        }else{
            $message = '处理失败';
        }
        return json($message);
    }
}