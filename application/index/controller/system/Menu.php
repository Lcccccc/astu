<?php
namespace app\index\controller\system;

use app\index\controller\Base;
use app\index\model\CommonModel;
use app\index\model\FileModel;
use app\index\model\MenuModel;
use think\Db;
use think\Error;
use think\Request;
use think\Validate;

class Menu extends Base
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

    //获取菜单详情
    public function get_menu(){
        $id = input('request.id');
        if(!$id){
            return '缺少参数';
        }
        $menu = Db::table('centertree')->where(array('id'=>$id))->find();
        return $menu;
    }

    //右侧默认页面
    public function main(){
        return $this->fetch();
    }

    //编辑菜单栏
    public function edit(){
        $id = input('request.id');
        $pid = input('request.pid');
        if($id){
            $menu = Db::table('centertree')->where(array('id'=>$id))->find();
            $this->assign('menu',$menu);
        }
        if($pid){
            $this->assign('pid',$pid);
        }
        $type_list = Db::table('treetype')->select();
        $this->assign('type_list',$type_list);
        return $this->fetch();
    }


    /**
     * 添加/编辑菜单
     * @return \think\response\Json
     */
    public function update_menu()
    {
        $params = Request::instance()->param();
        if(!$params['strName']|| !$params['treeTypeID']){
            return $this->errorCode(-1,'请填写完整参数');
        }
        $file_url = FileModel::upload('file');
        if($file_url){
            $update['strImgUrl'] = $file_url;
        }
        $update['intParentID'] = $params['intParentID'];
        $update['strName'] = $params['strName'];
        $update['strTitle'] = $params['strTitle'];
        $update['treeTypeID'] = $params['treeTypeID'];
        $update['strUrl'] = $params['strUrl'];
        $update['strDescription'] = $params['strDescription'];
        $update['intSortID'] = $params['intSortID'];
        $update['strVersion'] = session('version_str');
        $update['blnIsShow'] = 'False';
        $type = Db::table('treetype')->where(array('id'=>$params['treeTypeID']))->find();
        $update['strKind'] = $type['strTypeName'];
        if(is_numeric($params['id'])){
            $update['id'] = $params['id'];
            $update['datUpdateTime'] = date('Y-m-d H:i:s',time());
            Db::table('centertree')->update($update);
//            $this->success('编辑栏目成功');
            return $this->successCode(null,'编辑栏目成功');
        }else{
            $update['datCreateTime'] = date('Y-m-d H:i:s',time());
            Db::table('centertree')->data($update)->insert();
//            $this->success('添加栏目成功');
            return $this->successCode(null,'添加栏目成功');
        }
    }

    public function delete_menu(){
        $id = input('request.id');
        if(!$id){
            return $this->errorCode('-1','请选择栏目后再删除');
        }
        $son = Db::table('centertree')->where(array('intParentID'=>$id))->find();
        if($son){
            return $this->errorCode('-1','该栏目有子栏目无法删除');
        }
        $result = Db::table('centertree')->where(array('id'=>$id))->delete();
        return $this->successCode($result,'删除栏目成功');
    }



}