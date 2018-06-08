<?php
namespace app\index\controller;
use app\index\controller\Base;
use think\Request;

class Index extends Base
{
    public function index()
    {
        $ver_arr = array(
            array('name'=>'简体版','value'=>'cn_brief'),
            array('name'=>'繁体版','value'=>'cn_traditional'),
            array('name'=>'English','value'=>'en'),
        );
        $param = Request::instance()->param();
        if($param['optionsRadios']){
            session('version_str',$param['optionsRadios']);
        }else{
            if(!session('version_str')){
                session('version_str','cn_brief');
            }
        }
        for($i=0;$i<count($ver_arr);$i++){
            $ver_arr[$i]['is_select'] = ($ver_arr[$i]['value'] == session('version_str'))?1:0;
        }

        $this->assign('ver_arr',$ver_arr);
        return $this->fetch();
    }
}                   