<?php
/**
 * Created by PhpStorm.
 * User: qinya
 * Date: 2017/12/5
 * Time: 0:52
 */


namespace app\index\controller;

use think\Controller;

class Error extends Controller
{
    public function index()
    {
        $this->redirect('index/admin.login/index.html');
    }


    public function _empty()
    {
        $this->redirect('index/admin.login/index');
    }
}