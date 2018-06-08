<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/6/15
 * Time: 17:12
 */
namespace app\index\model;

use Think\Config;
use think\Model;
use alidayu\top\TopClient;
USE alidayu\top\request\AlibabaAliqinFcSmsNumSendRequest;
use think\Session;

class MessageModel extends Model
{
    const TYPE_PUSH = 'push';
    const TYPE_START_MEET  = 'start_meet';
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'message';
        parent::__construct($data);
    }

    /**
     * 发送通知
     * @param string $title 标题
     * @param string $content 内容
     * @param string $receid 通知人
     * @return mixed
     */
    public static function store($title, $content, $receid)
    {
        $user = Session::get('ext_user');

        $message = new static();
        $message->title = $title;
        $message->content = $content;
        $message->receid = $receid;
        $message->create_uid = $user['id'];
        $message->create_uname = $user['realname'];
        $message->create_time = time();
        return $message->save();
    }


    /**
     * 发送socket消息
     * @param $send
     * @param $to_user
     * @return mixed
     */
    public static function send_socket($content, $to_user)
    {
        // 推送的url地址，上线时改成自己的服务器地址
        $push_api_url = "http://121.201.14.237:2121/";
//        $send = array('data'=>array(1,2,3),'status'=>1,'message'=>'发送消息测试');
        $post_data = array(
            "type" => "publish",
            "content" => json_encode($content),
            "to" => $to_user,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $push_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;
        //var_export($return);
    }

    /**
     * apicloud推送消息模块
     * @param string $title 标题
     * @param string $content 内容
     * @param int $userIds 用户ID 字符串逗号隔开
     * @return mixed
     */
    public static function send_push_message($title='议事通',$content='议事通推送内容',$userIds=0){
        $AppID = Config::get('appcloud_appid');
        $AppKey = Config::get('appcloud_appkey');
        $AppPath = Config::get('appcloud_push_path');
        $timeOut = 30;
        $time = time();
        $SHAKey =  sha1($AppID.'UZ'.$AppKey.'UZ'.$time).'.'.$time;
        $headerInfo = array(
            'Content-Type:application/json',
            'X-APICloud-AppId:'.$AppID,
            'X-APICloud-AppKey:'.$SHAKey
        );
        $newData = array(
            'title'=>$title,
            'content'=>$content,
            'type'=>'1', //– 消息类型，1:消息 2:通知
            'platform'=>'2',
//            "groupName" => "all", //推送组名，多个组用英文逗号隔开.默认:全部组。eg.group1,group2 .
//            "userIds" => "3" //推送用户id, 多个用户用英文逗号分隔，eg. user1,user2。
        );
        if($userIds){
            $newData['userIds'] = $userIds;
        }
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $AppPath);
        curl_setopt ($ch, CURLOPT_POST,true) ;
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headerInfo);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($newData));
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeOut);
        $result = curl_exec($ch);
        @curl_close ( $ch );
        return $result;

    }
}