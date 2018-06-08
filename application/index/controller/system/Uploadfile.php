<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/6/28
 * Time: 17:31
 */
namespace app\index\controller\system;
use app\index\controller\Base;
use app\index\model\JSONModel;

class Uploadfile extends Base
{
    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('fileList');
        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        $info = $file->validate(['size'=>80015678,'ext'=>'jpg,png,gif,pdf,xsl,xslx,txt,pptx,doc,docx,ppt'])->move(ROOT_PATH . '/public/uploads');
        if($info){
            // 成功上传后 获取上传信息
            return '/uploads/'.$info->getSaveName();
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }

    public function ckeditorUpload(){
        $cb = $_GET['CKEditorFuncNum']; //获得ck的回调id
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        $info = $file->validate(['size'=>80015678,'ext'=>'jpg,png,gif,pdf,xsl,xslx,txt,pptx,doc,docx,ppt'])->move(ROOT_PATH . '/public/uploads');
        if($info){
            // 成功上传后 获取上传信息
            $url =   '/uploads/'.$info->getSaveName();
            echo "<script>window.parent.CKEDITOR.tools.callFunction($cb, '$url', '');</script>"; //图片上传成功，通知ck图片的url
        }else{
            // 上传失败获取错误信息
            $error =  $file->getError();
            echo "<script>window.parent.CKEDITOR.tools.callFunction($cb, '', '$error');</script>";//图片上传失败，通知ck失败消息
        }
    }

    /**
     * kindeditor上传
     */
    public function kindeditorUpload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        $info = $file->validate(['size'=>80015678,'ext'=>'jpg,png,gif,pdf,xsl,xslx,txt,pptx,doc,docx,ppt'])->move(ROOT_PATH . '/public/uploads');
        if($info){
            // 成功上传后 获取上传信息
            $file_url =   '/uploads/'.$info->getSaveName();
            return json(array('error' => 0, 'url' => $file_url));
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }


}

