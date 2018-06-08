<?php
/**
 * Created by PhpStorm.
 * User: MSI
 * Date: 2017/9/7
 * Time: 0:56
 */
namespace app\index\controller\admin;

use app\index\model\FileModel;
use app\index\model\MenuModel;
use think\Controller;
use app\index\controller\Base;
use think\Db;
use think\Request;
use think\View;

class Usergroup extends Base
{
    //管理员组列表
    public function index(){
        return $this->fetch();
    }

    public function get_group_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $count = Db::table('sysadminusergroup')->count();
        $user_group = Db::table('sysadminusergroup')->limit(($page-1)*$num,$num)->select();
        return array('user_group'=>$user_group,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }

    /**
     * 编辑管理员组
     * @return mixed
     */
    public function editGroup(){
        $param = Request::instance()->param();
        if($param['id']){
            $group = Db::table('sysadminusergroup')->where(array('id'=>$param['id']))->find();
            $this->assign('group',$group);
        }
        $group_list = Db::table('sysadminusergroup')->field('id,strName')->select();
        $this->assign('group_list',$group_list);
        return $this->fetch();
    }

    /**
     * 编辑用户组
     * @throws \think\Exception
     */
    public function update_usergroup(){
        $param = Request::instance()->param();
        $update['strName'] = $param['strName'];
        $update['strDescription'] = $param['strDescription'];
        $update['intSortID'] = $param['intSortID'];
        $update['intParentID'] = $param['intParentID'];
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('sysadminusergroup')->update($update);
            $this->success('编辑用户组成功');
        }else{
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('sysadminusergroup')->data($update)->insert();
            $this->success('添加用户组成功','index');
        }

    }

    public function delete_group(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('sysadminusergroup')->where(array('id'=>$param['id']))->delete();
            $message = '删除成功';
        }else{
            $message = '删除失败';
        }
        return json($message);
    }
}