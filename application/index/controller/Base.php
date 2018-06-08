<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/6/2
 * Time: 10:02
 */
namespace app\index\controller;

use app\index\model\MenuModel;
use think\Db;

class Base extends Common
{
    public function _initialize()
    {
        if(!session('ext_user')){
            $this->redirect('admin.login/index');
        }

        //获取当前用户菜单列表
//        $menu = Db::name('menu')->where(['status' => MenuModel::STATUS_ENABLED])->select();
//        $menu_tree = MenuModel::navs($menu);
//        $this->assign('menu',$menu_tree);
        //判断用户权限
//        $request = \think\Request::instance();
//        $action_name=$request->path();
//        $where['type'] = 2;
//        $where['url'] = $action_name;
//        $where['status'] = 1;
//        $rule = Db::name('user_rule')->where($where)->find();
//        if($rule){
//            if(!in_array($rule['id'],session('user_rule'))){
//                $this->error('没有权限访问该页面');
//            }
//        }
    }

    public function _empty()
    {
        $this->redirect('index/admin.login/index');
    }
}