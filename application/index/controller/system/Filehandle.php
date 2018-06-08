<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/7/6
 * Time: 10:15
 */
namespace app\index\controller\system;
use app\index\controller\Base;
use app\index\model\FileHandleModel;
use think\console\command\make\Controller;
use think\Db;

class FileHandle extends Controller{
    public function switch_files(){
        $url = input('request.files');
        $url_arr = explode(',',trim($url,','));
        $file_str = '';
        for($i=0;$i<count($url_arr);$i++){
            $ext = pathinfo($url_arr[$i], PATHINFO_EXTENSION);
            if($ext == 'pdf'){
                $switch_file = FileHandleModel::SwitchFile($url_arr[$i],6);
            }elseif($ext == 'xls' || $ext == 'xlsx'|| $ext == 'doc'||$ext == 'docx'||$ext == 'pptx'||$ext == 'ppt'){
                $switch_file = FileHandleModel::SwitchFile($url_arr[$i],3);
            }
            for($j=0;$j<count($switch_file['img']);$j++){
                $file_str .= $switch_file['img'][$j].',';
            }
        }

        return $file_str;
    }


    public function insert_issue_file(){
        ignore_user_abort(true); // 忽略客户端断开
        set_time_limit(0);       // 设置执行不超时
        $url = input('request.files');
        $url_arr = explode(',',trim($url,','));
        for($i=0;$i<count($url_arr);$i++){
            $ext = pathinfo($url_arr[$i], PATHINFO_EXTENSION);
            if($ext == 'pdf'){
                $switch_file = FileHandleModel::SwitchFile($url_arr[$i],6);
            }elseif($ext == 'xls' || $ext == 'xlsx'|| $ext == 'doc'||$ext == 'docx'||$ext == 'pptx'||$ext == 'ppt'){
                $switch_file = FileHandleModel::SwitchFile($url_arr[$i],3);
            }
            for($j=0;$j<count($switch_file['img']);$j++){
                $file_arr[] = $switch_file['img'][$j];
            }
        }

        for($i=0;$i<count($file_arr);$i++){
            $file_data['filename'] = '转换文件';
            $file_data['filepath'] = 'http://'.$file_arr[$i];
            $file_data['create_uid'] = input('request.create_uid');
            $file_data['create_time'] = time();
            $switch_id[] = Db::name('files')->insertGetId($file_data);
        }

        $where['id'] = input('request.issue_id');
        $issue = Db::name('issue')->where($where)->find();
        if(!empty($switch_id)){
            $update['id'] = $issue['id'];
            if($issue['attach_img']){
                $update['attach_img'] = $issue['attach_img'].','.implode(',',$switch_id);
            }else{
                $update['attach_img'] = implode(',',$switch_id);
            }
            Db::name('issue')->update($update);
        }

        echo 'success';exit;
    }

}