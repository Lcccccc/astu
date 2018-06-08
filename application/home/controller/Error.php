<?php
/**
 * Created by PhpStorm.
 * User: qinya
 * Date: 2017/12/5
 * Time: 0:52
 */


namespace app\home\controller;

use think\Controller;

class Error extends Controller
{
    public function index()
    {
        $this->redirect('home/index/index');
    }


    public function _empty()
    {
        $this->redirect('home/index/index');
    }
}