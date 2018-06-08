<?php
/**
 * Created by PhpStorm.
 * User: MSI
 * Date: 2017/9/7
 * Time: 0:18
 */
namespace app\index\controller\admin;

use app\index\model\FileModel;
use app\index\model\MenuModel;
use think\Controller;
use app\index\controller\Base;
use think\Db;
use think\Request;
use think\View;

class User extends Base
{
    //管理员组列表
    public function index(){
        return $this->fetch();
    }

    /**
     * 获取用户列表
     * @return array
     */
    public function get_user_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $count = Db::table('sysadminuser')->count();
        $user_list = Db::table('sysadminuser a')
            ->join(['sysadminusergroup'=> 'b'],'a.intSysAdminUserGroupID = b.id','LEFT')
            ->field('a.*,b.strName groupName')
            ->limit(($page-1)*$num,$num)
            ->select();
        return array('user_list'=>$user_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }

    /**
     * 编辑管理员组
     * @return mixed
     */
    public function editUser(){
        $param = Request::instance()->param();
        if($param['id']){
            $user = Db::table('sysadminuser')->where(array('id'=>$param['id']))->find();
            $this->assign('user',$user);
        }
        $group_list = Db::table('sysadminusergroup')->field('id,strName')->select();
        $this->assign('group_list',$group_list);
        return $this->fetch();
    }

    /**
     * 编辑用户组
     * @throws \think\Exception
     */
    public function update_user(){
        $param = Request::instance()->param();
        $update['strUserName'] = $param['strUserName'];
        $update['intSysAdminUserGroupID'] = $param['intSysAdminUserGroupID'];
        if($param['strUserPass']){
            $update['strUserPass'] = md5($param['strUserPass']);
        }
        $update['strRealName'] = $param['strRealName'];
        $update['strMobile'] = $param['strMobile'];
        $update['strTel'] = $param['strTel'];
        $update['strDescription'] = $param['strDescription'];
        $update['blnIsUse'] = $param['blnIsUse'];
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('sysadminuser')->update($update);
            $this->success('编辑用户成功');
        }else{
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('sysadminuser')->data($update)->insert();
            $this->success('添加用户成功','index');
        }
    }

    public function delete_user(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('sysadminuser')->where(array('id'=>$param['id']))->delete();
            $message = '删除成功';
        }else{
            $message = '删除失败';
        }
        return json($message);
    }
}