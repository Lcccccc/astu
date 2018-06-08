<?php
namespace app\home\controller;
use app\index\model\CommonModel;
use app\index\model\FileModel;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Base
{
    //主页
    public function index()
    {
        //首页banner图
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'home';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        //关于
        $where['strTitle'] = 'about';
        $about = Db::table('centertree')->where($where)->find();
        $this->assign('about',$about);
        //服务栏
        $where['strTitle']='service_intro';
        $service = Db::table('centertree')->where($where)->find();
        $where1['intParentID'] = $service['id'];
        $service['list'] = Db::table('centertree')->where($where1)->select();
        $this->assign('service',$service);
        //队伍栏
        $where['strTitle']='team';
        $team = Db::table('centertree')->where($where)->find();
        $where2['intCenterTreeID'] = $team['id'];
        $team['list'] = Db::table('listcontentinfo')->where($where2)->order('intSortID')->select();
        $this->assign('team',$team);

        //白话栏（显示4篇爆料和技术，1活动，1红人）
        $where['strTitle']='talking';
        $talk = Db::table('centertree')->where($where)->find();
        $where3['a.strTitle'] = array('in',array('disclose','technology'));
        $where3['b.intIndexShow'] = 1;
        $talk['list'] = Db::table('centertree a')->join(['listcontentinfo'=>'b', ''],'a.id = b.intCenterTreeID','left')->where($where3)->order('b.id desc')->limit(0,4)->select();
        for($i=0;$i<count($talk['list']);$i++){
            $talk['list'][$i]['datCreateTime'] = substr($talk['list'][$i]['datCreateTime'],0,10);
        }
        $where4['a.strTitle'] = 'red-museum';
        $where4['b.intIndexShow'] = 1;
        $talk['red-museum'] = Db::table('centertree a')->join(['listcontentinfo'=>'b', ''],'a.id = b.intCenterTreeID','left')->where($where4)->order('b.id desc')->find();
        $where5['a.strTitle'] = 'talking-design';
        $where5['b.intIndexShow'] = 1;
        $talk['talking-design'] = Db::table('centertree a')->join(['listcontentinfo'=>'b', ''],'a.id = b.intCenterTreeID','left')->where($where5)->order('b.id desc')->find();
        $this->assign('talk',$talk);

        return $this->fetch();
    }

    //文旅产业
    public function tourismindustry()
    {
        $this->assign('stayEmpty','文旅产业');
        return $this->fetch();
    }
    //服务
    public function services()
    {
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'services';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        //服务详情
        $where['strTitle']='servicedetail';
        $service_detail = Db::table('centertree')->where($where)->find();
        $where1['intCenterTreeID'] = $service_detail['id'];
        $service_detail['list'] = Db::table('pictureinfo')->where($where1)->order('intSortID')->select();
        $this->assign('service_detail',$service_detail);

        //服务分类
        $where['strTitle']='service_intro';
        $service_intro = Db::table('centertree')->where($where)->find();
        $where2['intParentID'] = $service_intro['id'];
        $service_intro['list'] = Db::table('centertree')->where($where2)->select();
        for($i=0;$i<count($service_intro['list']);$i++){
            $where3['intCenterTreeID'] = $service_intro['list'][$i]['id'];
            $service_intro['list'][$i]['content_list'] = Db::table('listcontentinfo')->where($where3)->field('id,strTitle')->select();
        }
        $this->assign('service_intro',$service_intro);
//        dump($service_intro);exit;
        return $this->fetch();
    }

    //案例
    public function cases()
    {
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'cases';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        //获取案例类型列表
        $where['strTitle']='cases';
        $cases = Db::table('centertree')->where($where)->find();
        $where2['intParentID'] = $cases['id'];
        $case_type_list = Db::table('centertree')->where($where2)->select();
        $this->assign('case_type_list',$case_type_list);
        return $this->fetch('case');
    }

    //我们
    public function we()
    {
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'we';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        return $this->fetch();
    }

    //白话
    public function talking()
    {
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'talking';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        $param = Request::instance()->param();
        if($param['tree']){
            $wh['strTitle'] = $param['tree'];
            $tree = Db::table('centertree')->where($wh)->find();
        }
        $where['strTitle']='talking';
        $talking = Db::table('centertree')->where($where)->find();
        $where2['intParentID'] = $talking['id'];
        $talking_type_list = Db::table('centertree')->where($where2)->select();
        $this->assign('talking_type_list',$talking_type_list);
        $this->assign('tree_id',$tree['id']?$tree['id']:0);
        return $this->fetch();
    }

    //合作
    public function cooperation(){
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'cooperation';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        return $this->fetch();
    }

    //招聘
    public function recruit(){
        //banner图
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'recruit';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);

        //招聘岗位信息
        $where['strTitle'] = 'recruitstation';
        $station = Db::table('centertree')->where($where)->find();
        $where1['intCenterTreeID'] = $station['id'];
        $station_list = Db::table('listcontentinfo')->where($where1)->select();
        $this->assign('list',$station_list);
        return $this->fetch();
    }

    //内容详情页面
    public function contentDetail(){
        $param = Request::instance()->param();
        if($param['type'] == 'article'){
            $where['id'] = $param['id'];
            $content = Db::table('listcontentinfo')->where($where)->find();
        }elseif($param['type'] == 'single'){

        }elseif($param['type'] == 'product'){

        }
        $this->assign('content',$content);
        return $this->fetch();
    }

    //公司介绍
    public function comIntro(){
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'comIntro';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        return $this->fetch();
    }
    //联系我们
    public function contactUs(){
        $where['strVersion']= session('strVersion');
        $where['strTitle'] = 'contactUs';
        $home = Db::table('centertree')->where($where)->find();
        $wh1['strVersion']= session('strVersion');
        $wh1['intCenterTreeID'] = $home['id'];
        $banner_pic =  Db::table('webrotatepic')->where($wh1)->select();
        $count_banner = count($banner_pic);
        $this->assign('banner_pic',$banner_pic);
        $this->assign('count_banner',$count_banner);
        return $this->fetch();
    }

    //投稿页面
    public function submission(){
        //获取文章分类列表
        $where['strTitle'] = 'talking';
        $case = Db::table('centertree')->where($where)->find();
        $wh['intParentID'] = $case['id'];
        $case_list = $tree = Db::table('centertree')->field('id,strName')->where($wh)->select();
        $this->assign('case_list',$case_list);
        return $this->fetch();
    }

    public function get_submission(){
        $file_url = FileModel::upload('photo');
        $param = Request::instance()->param();
        $update['strTitle'] = $param['articleTitle'];
        $update['strContent'] = $param['content'];
        $update['intClick'] = 0;
        $update['intIndexShow'] = 0;
        $update['intStatus'] = 0;
        $update['strSendEmail'] = $param['sendemail'];
        if($file_url){
            $update['strImgUrl'] = $file_url;
        }

        $update['strVersion'] = session('strVersion');
        $update['intCenterTreeID'] = $param['articleType'];
        $update['datCreateTime'] = date('Y-m-d H:i:s',time());
        Db::table('listcontentinfo')->data($update)->insert();
        $this->assign('id',$param['tid']);
        $this->success('投稿成功，请等待审核','/home/index/talking');
//            return $this->successCode(null,'添加文章成功');
    }

    public function submit_cooperation(){
        $param = Request::instance()->param();
        $data['strName'] = $param['name'];
        $data['strCompany'] = $param['company'];
        $data['strPhone'] = $param['phone'];
        $data['strQQ'] = $param['qq'];
        $data['summary'] = $param['summary'];
        $data['datCreateTime'] = date('Y-m-d H:i:s');
        if(!$data['strName']){
            return array('msg'=>'请输入名字','code'=>'-1');
        }
        Db::table('cooperation')->data($data)->insert();
        return array('msg'=>'提交需求成功','code'=>'200');
    }

    public function submit_recruit(){
        $param = Request::instance()->param();
        $data['strEmail'] = $param['email'];
        $data['strStation'] = $param['station'];
        $data['datCreateTime'] = date('Y-m-d H:i:s',time());
        if(!$param['email']){
            return array('msg'=>'请输入邮箱','code'=>'-1');
        }
        Db::table('recruit')->data($data)->insert();
        return array('msg'=>'提交成功','code'=>'200');
    }
}