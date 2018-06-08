<?php
/**
 * Created by PhpStorm.
 * User: MSI
 * Date: 2017/9/4
 * Time: 22:21
 */
namespace app\index\controller\admin;

use think\Controller;
use app\index\controller\Base;
use think\Db;
use think\Request;
use think\View;

class System extends Base
{
    public function index(){
        $where['strVersion'] = session('version_str');
        $config = Db::table('websystem')->where($where)->find();
        $this->assign('config',$config);
        return $this->fetch();
    }

    public function update_seo(){
        $param = Request::instance()->param();
        $update['webTitle'] = $param['title'];
        $update['webKey'] = $param['key'];
        $update['webDescription'] = $param['description'];
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('websystem')->update($update);
        }else{
            $update['strVersion'] = session('version_str');
            Db::table('websystem')->data($update)->insert();
        }
        $this->success('更新成功');
    }

}