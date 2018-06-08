<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/9/11
 * Time: 14:59
 */
namespace app\index\controller\content;

use app\index\controller\Base;
use app\index\model\CommonModel;
use app\index\model\FileModel;
use think\Db;
use think\Request;

class Content extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    //左侧菜单栏树状图
    public function get_menu_list(){
        $where['strVersion'] = session('version_str');
        $tree = Db::table('centertree')->where($where)->field('id,intParentID,strName')->select();
        $tree = CommonModel::tree($tree,'id','intParentID');
        return $tree;
    }

    public function main(){
        return $this->fetch();
    }

    /**
     * 根据菜单类型获取菜单具体内容
     * @return mixed
     */
    public function contentDetail(){
        $id = input('request.id');
        if(!$id){
            return $this->fetch('main');
        }
        $menu = Db::table('centertree')->where(array('id'=>$id))->find();
        $this->assign('menu',$menu);
        if($menu['treeTypeID']==1){
            //目录内容页
            $where['intCenterTreeID'] = $menu['id'];
            $pics = Db::table('webrotatepic')->where($where)->select();
            $this->assign('pics',$pics);
            $this->assign('tid',$menu['id']);
            return $this->fetch('menu');
        }elseif($menu['treeTypeID']==2){
            //单页内容页
            $where['intCenterTreeID'] = $menu['id'];
            $content = Db::table('singlecontentinfo')->where($where)->find();
            $this->assign('content',$content);
            return $this->fetch('singlepage');
        }elseif($menu['treeTypeID']==3){
            //文章内容页
            return $this->fetch('articlelist');
        }elseif($menu['treeTypeID']==4){
            //产品内容页
            $this->assign('tid',$menu['id']);
            return $this->fetch('productlist');
        }elseif($menu['treeTypeID']==5){
            //图片列表页
            return $this->fetch('picturelist');
        }else{
            return $this->fetch('index');
        }
    }

    public function menuPic(){
        $param = Request::instance()->param();
        if($param['id']){
            $picture = Db::table('webrotatepic')->where(array('id'=>$param['id']))->find();
            $this->assign('picture',$picture);
        }
        return $this->fetch();
    }

    /**
     * 编辑图片
     * @throws \think\Exception
     */
    public function update_menu_pic(){
        $param = Request::instance()->param();
        $path = FileModel::upload('path');
        if($path){
            $update['path'] = $path;
        }
        $thumbPath = FileModel::upload('thumbPath');
        if($thumbPath){
            $update['thumbPath'] = $thumbPath;
        }
        $centerPath = FileModel::upload('centerPath');
        if($centerPath){
            $update['centerPath'] = $centerPath;
        }

        $update['title'] = $param['title'];
        $update['title2'] = $param['title2'];
        $update['brief'] = $param['brief'];
        $update['url'] = $param['url'];
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('webrotatepic')->update($update);
            $this->success('编辑图片成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['intCenterTreeID'] = $param['tid'];
            Db::table('webrotatepic')->data($update)->insert();
            $this->success('添加图片成功','/index/content.content/contentDetail?id='.$param['tid']);
        }
    }

    /**
     * 删除轮播图片
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete_menu_pic(){
        $id = input('request.id');
        if(!$id){
            return $this->errorCode('-1','获取图片ID失败');
        }
        $result = Db::table('webrotatepic')->where(array('id'=>$id))->delete();
        return $this->successCode($result,'删除图片成功');
    }

    /**
     * 更新单页内容
     */
    public function update_single(){
        $file_url = FileModel::upload('file');
        $param = Request::instance()->param();
        $update['strTitle'] = $param['strTitle'];
        $update['strContent'] = $param['strContent'];
        $update['strIntro'] = $param['strIntro'];
        $update['intClick'] = $param['intClick'];
        $update['intSortID'] = $param['intSortID'];
        $update['datCreateTime'] = $param['datCreateTime'];
        $update['intCenterTreeID'] = $param['tid'];
        if($file_url){
            $update['strImgUrl'] = $file_url;
        }
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('singlecontentinfo')->update($update);
            $this->success('编辑单页成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('singlecontentinfo')->data($update)->insert();
            $this->success('添加单页成功','/index/content.content/contentDetail');
        }
    }


    /**
     * 获取文章列表
     * @return array
     */
    public function get_article_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $where['a.strVersion'] = session('version_str');
        $where['a.intCenterTreeID'] = $param['tid'];
        $count = Db::table('listcontentinfo a')->where($where)->count();
        $article_list = Db::table('listcontentinfo a')
            ->join(['centertree'=> 'b'],'a.intCenterTreeID = b.id','LEFT')
            ->field('a.*,b.strName typeName')
            ->where($where)
            ->limit(($page-1)*$num,$num)
            ->order('id desc')
            ->select();
        for($i=0;$i<count($article_list);$i++){
            if($article_list[$i]['intStatus'] == 0){
                $article_list[$i]['status'] = '待审核';
            }elseif($article_list[$i]['intStatus'] == 1){
                $article_list[$i]['status'] = '审核通过';
            }elseif($article_list[$i]['intStatus'] == -1){
                $article_list[$i]['status'] = '审核失败';
            }
        }
        return array('article_list'=>$article_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }

    /**
     * 文章详情
     */
    public function articleDetail(){
        $param = Request::instance()->param();
        if($param['id']){
            $article = Db::table('listcontentinfo')->where(array('id'=>$param['id']))->find();
            $this->assign('article',$article);
            $this->assign('tid',$article['intCenterTreeID']);
        }
        if($param['tid']){
            $this->assign('tid',$param['tid']);
        }
        return $this->fetch();
    }

    /**
     * 新增/编辑文章
     * @throws \think\Exception
     */
    public function update_article(){
//        dump(Request::instance()->param());exit;
        $file_url = FileModel::upload('file');
        $param = Request::instance()->param();
        $update['strTitle'] = $param['strTitle'];
        $update['strContent'] = $param['strContent'];
        $update['datCreateTime'] = $param['datCreateTime'];
        $update['strIntro'] = $param['strIntro'];
        $update['intSortID'] = $param['intSortID'];
        $update['intClick'] = $param['intClick'];
        $update['intIndexShow'] = $param['intIndexShow'];
        $update['intStatus'] = $param['intStatus'];
        if($file_url){
            $update['strImgUrl'] = $file_url;
        }
        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('listcontentinfo')->update($update);
            $this->success('编辑文章成功');
//            return $this->successCode(null,'编辑文章成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['intCenterTreeID'] = $param['tid'];
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('listcontentinfo')->data($update)->insert();
            $this->assign('id',$param['tid']);
            $this->success('添加文章成功','/index/content.content/contentDetail?id='.$param['tid']);
//            return $this->successCode(null,'添加文章成功');
        }
    }

    /**
     * 删除文章
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete_article(){
        $id = input('request.id');
        if(!$id){
            return $this->errorCode('-1','获取文章ID失败');
        }
        $result = Db::table('listcontentinfo')->where(array('id'=>$id))->delete();
        return $this->successCode($result,'删除文章成功');
    }

    /**
     * 获取产品列表
     * @return array
     */
    public function get_product_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $where['a.strVersion'] = session('version_str');
        $where['a.intCenterTreeID'] = $param['tid'];
        $count = Db::table('productinfo a')->where($where)->count();
        $product_list = Db::table('productinfo a')
            ->join(['centertree'=> 'b'],'a.intCenterTreeID = b.id','LEFT')
            ->field('a.*,b.strName typeName')
            ->where($where)
            ->limit(($page-1)*$num,$num)
            ->order('id desc')
            ->select();
        return array('product_list'=>$product_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }

    /**
     * 文章详情
     */
    public function productDetail(){
        $param = Request::instance()->param();
        if($param['id']){
            $product = Db::table('productinfo')->where(array('id'=>$param['id']))->find();
            $this->assign('product',$product);
            $this->assign('tid',$product['intCenterTreeID']);
        }
        if($param['tid']){
            $this->assign('tid',$param['tid']);
        }
        return $this->fetch();
    }

    /**
     * 新增/编辑产品
     * @throws \think\Exception
     */
    public function update_product(){
        $param = Request::instance()->param();
        $update['strTitle'] = $param['strTitle'];
        $update['strTitle2'] = $param['strTitle2'];
        $update['strIntro1'] = $param['strIntro1'];
        $update['strIntro2'] = $param['strIntro2'];
        $update['strIntro3'] = $param['strIntro3'];
        $update['strDetail1'] = $param['strDetail1'];
        $update['strDetail2'] = $param['strDetail2'];
        $update['strDetail3'] = $param['strDetail3'];
        $strImgList = FileModel::upload('strImgList');
        if($strImgList){
            $update['strImgList'] = $strImgList;
        }
        $strImgListBig = FileModel::upload('strImgListBig');
        if($strImgListBig){
            $update['strImgListBig'] = $strImgListBig;
        }
        $strPDF = FileModel::upload('strPDF');
        if($strPDF){
            $update['strPDF'] = $strPDF;
        }
        $strImgLeft = FileModel::upload('strImgLeft');
        if($strImgLeft){
            $update['strImgLeft'] = $strImgLeft;
        }
        $strImgRight = FileModel::upload('strImgRight');
        if($strImgRight){
            $update['strImgRight'] = $strImgRight;
        }
        $strImgCert = FileModel::upload('strImgCert');
        if($strImgCert){
            $update['strImgCert'] = $strImgCert;
        }
        $update['strContent'] = $param['strContent'];
        $update['strCert'] = $param['strCert'];
        $update['intSortID'] = $param['intSortID'];
        if($param['id']){
            $update['id'] = $param['id'];
            $update['datUpdateTime'] = date('Y-m-d H:i:s',time());
            Db::table('productinfo')->update($update);
            $this->success('编辑产品成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['intCenterTreeID'] = $param['tid'];
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('productinfo')->data($update)->insert();
            $this->assign('id',$param['tid']);
            $this->success('添加产品成功','/index/content.content/contentDetail?id='.$param['tid']);
        }
    }

    /**
     * 删除产品
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete_product(){
        $id = input('request.id');
        if(!$id){
            return $this->errorCode('-1','获取产品ID失败');
        }
        $result = Db::table('productinfo')->where(array('id'=>$id))->delete();
        return $this->successCode($result,'删除产品成功');
    }


    /**
     * 获取图片列表
     * @return array
     */
    public function get_picture_list(){
        $param = Request::instance()->param();
        $page = $param['page']?$param['page']:1;
        $num = $param['num']?$param['num']:10;
        $where['a.strVersion'] = session('version_str');
        $where['a.intCenterTreeID'] = $param['tid'];
        $count = Db::table('pictureinfo a')->where($where)->count();
        $picture_list = Db::table('pictureinfo a')
            ->join(['centertree'=> 'b'],'a.intCenterTreeID = b.id','LEFT')
            ->field('a.*,b.strName typeName')
            ->where($where)
            ->limit(($page-1)*$num,$num)
            ->order('id desc')
            ->select();
        return array('picture_list'=>$picture_list,'total_num'=>$count,'total_page'=>ceil($count/$num),'page'=>$page);
    }


    /**
     * 图片详情
     * @return mixed
     */
    public function pictureDetail(){
        $param = Request::instance()->param();
        if($param['id']){
            $picture = Db::table('pictureinfo')->where(array('id'=>$param['id']))->find();
            $this->assign('tid',$picture['intCenterTreeID']);
        }
        if($param['tid']){
            $this->assign('tid',$param['tid']);
        }
        $this->assign('picture',$picture);
        return $this->fetch();
    }

    /**
     * 编辑图片
     * @throws \think\Exception
     */
    public function update_picture(){
        $param = Request::instance()->param();
        $strImgThumb = FileModel::upload('strImgThumb');
        if($strImgThumb){
            $update['strImgThumb'] = $strImgThumb;
        }
        $strImgBig = FileModel::upload('strImgBig');
        if($strImgBig){
            $update['strImgBig'] = $strImgBig;
        }
        $strImgSmall = FileModel::upload('strImgSmall');
        if($strImgSmall){
            $update['strImgSmall'] = $strImgSmall;
        }
        $update['strName'] = $param['strName'];
        $update['strTitle'] = $param['strTitle'];
        $update['strDescription'] = $param['strDescription'];
        $update['intSortID'] = $param['intSortID'];

        if($param['id']){
            $update['id'] = $param['id'];
            Db::table('pictureinfo')->update($update);
            $this->success('编辑图片成功');
        }else{
            $update['strVersion'] = session('version_str');
            $update['intCenterTreeID'] = $param['tid'];
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('pictureinfo')->data($update)->insert();
            $this->success('添加图片成功','/index/content.content/contentDetail?id='.$param['tid']);
        }
    }

    /**
     * 删除图片
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete_picture(){
        $id = input('request.id');
        if(!$id){
            return $this->errorCode('-1','获取图片ID失败');
        }
        $result = Db::table('pictureinfo')->where(array('id'=>$id))->delete();
        return $this->successCode($result,'删除图片成功');
    }
}