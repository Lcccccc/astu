<?php
/**
 * Created by PhpStorm.
 * User: MSI
 * Date: 2017/9/4
 * Time: 22:21
 */
namespace app\index\controller\system;

use app\index\model\FileModel;
use think\Controller;
use app\index\controller\Base;
use think\Db;
use think\Request;
use think\View;

class System extends Base
{
    //设置首页，查看SEO配置
    public function index(){
        $where['strVersion'] = session('version_str');
        $config = Db::table('websystem')->where($where)->find();
        $this->assign('config',$config);
        return $this->fetch();
    }

    //更新SEO配置
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
        $this->success('SEO更新成功');
    }

    //查看网站文字配置
    public function indexWord(){
        $where['strVersion'] = session('version_str');
        $config = Db::table('websystem')->where($where)->find();
        $this->assign('config',$config);
        return $this->fetch();
    }

    //修改网站文字
    public function update_web_word(){
        $file_url = FileModel::upload();
        $param = Request::instance()->param();
        $update['indexPicWord'] = $param['indexpicword'];
        $update['indexPic'] = $file_url;
        $update['buttonWord'] = $param['buttonword'];
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('websystem')->update($update);
        }else{
            $update['strVersion'] = session('version_str');
            Db::table('websystem')->data($update)->insert();
        }
        $this->success('文字配置更新成功');
    }

    //查看首页轮播图配置
    public function indexPic(){
        $wh['strTitle'] = 'home';
        $home = Db::table('centertree')->where($wh)->find();
        $where['strVersion'] = session('version_str');
        $where['intCenterTreeID'] = $home['id'];
        $pics = Db::table('webrotatepic')->where($where)->select();
        $this->assign('pics',$pics);
        return $this->fetch();
    }

    public function picUpdate(){
        $param = Request::instance()->param();
        if($param['id']){
            $picture = Db::table('webrotatepic')->where(array('id'=>$param['id']))->find();
        }
        $this->assign('picture',$picture);
        return $this->fetch();
    }


    public function update_web_pic(){
        $file_url = FileModel::upload('file');
        $thumb_file = FileModel::upload('thumb_file');
        $center_file = FileModel::upload('center_file');

        $wh['strTitle'] = 'home';
        $home = Db::table('centertree')->where($wh)->find();

        $param = Request::instance()->param();
        $update['title'] = $param['title'];
        $update['url'] = $param['url'];
        $update['brief'] = $param['brief'];
        if($file_url){
            $update['path'] = $file_url;
        }
        if($thumb_file){
            $update['thumbPath'] = $thumb_file;
        }
        if($center_file){
            $update['centerPath'] = $center_file;
        }
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('webrotatepic')->update($update);
            $this->success('编辑图片成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['intCenterTreeID'] = $home['id'];
            Db::table('webrotatepic')->data($update)->insert();
            $this->success('添加图片成功','indexPic');
        }

    }

    public function delete_pic(){
        $param = Request::instance()->param();
        if($param['id']){
            Db::table('webrotatepic')->where(array('id'=>$param['id']))->delete();
            $message = '删除成功';
        }else{
            $message = '删除失败';
        }
        return json($message);
    }

}