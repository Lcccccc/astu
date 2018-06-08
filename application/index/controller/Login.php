<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/6/2
 * Time: 10:09
 */
namespace app\index\controller;

use app\index\model\UserModel;
use app\index\model\UserRuleModel;
use think\Controller;
use think\Request;
use think\View;

class Login extends Controller
{
    public function index()
    {
        $view = new View();
        return $view->fetch('login');
    }

    public function login()
    {
        $params = Request::instance()->param();

        try {
            $user = (new UserModel())->checkUser($params);
            session('ext_user', $user);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, null, 1);
        }
        $this->success('登录成功', 'Index/index', null, 1);
    }

    public function logout(){
        session('ext_user',null);
        $this->success('退出成功','Login/index',null,1);
    }

    public function _empty()
    {
        $this->redirect('index/admin.login/index.html');
    }
}