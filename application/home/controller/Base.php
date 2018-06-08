<?php
/**
 * Created by PhpStorm.
 * User: MSI
 * Date: 2017/9/18
 * Time: 1:42
 */
namespace app\home\controller;

use think\Db;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
        if($_REQUEST['language']){
           session('strVersion',$_GET['language']);
        }else{
            session('strVersion','cn_brief');
        }
        $where['strVersion'] = session('strVersion');
        $where['intParentID'] = 0;
        $menu = Db::table('centertree')->where($where)->select();
        session('menu',$menu);
    }

    public function _empty()
    {
        $this->redirect('home/index/index');
    }
}