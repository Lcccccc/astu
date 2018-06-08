<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/6/15
 * Time: 14:50
 */
namespace app\index\controller\system;
use app\index\controller\Base;
use app\index\model\MeetingModel;
use app\index\model\MessageModel;
use think\Db;
use think\Config;
use think\Request;
use think\Loader;
use think\Session;
use app\index\model\LogModel;

class Message extends Base{

    /**
     * 发送socket消息
     * @return mixed
     */
    public function send_socket(){
        $to = input('request.to_uid');
        $content = input('request.content');
        return MessageModel::send_socket($content,$to);
    }

    public function index()
    {
        return $this->fetch();
    }

    public function getMessageList()
    {
        $post_data = json_decode(input('request.aoData'), true);
        $offset = $post_data['iDisplayStart'];
        $length = $post_data['iDisplayLength'];
        $key = input('request.key');
        $ext_user = Session::get('ext_user');

        //统计总数量
        $select = Db::name('message')->alias('m')
            ->join('__USER__ u', 'm.create_uid=u.id')
            ->where('FIND_IN_SET(' . $ext_user['id'] . ',m.receid)>0');
        if (!empty($key)) {
            $select->where('m.title|m.content|u.realname|m.create_uname', 'like', '%' . $key . '%');
        }

        $total = $select->count();

        //查询结果
        $select = Db::name('message')->alias('m')
            ->join('__USER__ u', 'm.create_uid=u.id')
            ->where('FIND_IN_SET(' . $ext_user['id'] . ',m.receid)>0');
        if (!empty($key)) {
            $select->where('m.title|m.content|u.realname|m.create_uname', 'like', '%' . $key . '%');
        }

        $message = $select->field('m.id,m.title,m.content,u.realname u_realname,m.create_time,m.create_uname')
            ->limit($offset, $length)->order('id', 'DESC')->select();
        foreach ($message as &$item) {
            $item['create_time'] = date('Y-m-d', $item['create_time']);
        }

        $json_data = array(
            'sEcho' => intval($post_data['sEcho']),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $total,
            'aaData' => $message
        );

        return json($json_data);
    }

    /**
     * 查看单条消息
     * @return \think\response\Json
     */
    public function messageDetail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '消息ID必须');
        }
        $message = MessageModel::get($id);
        $viewLog = Db::name('log')->alias('l')->where('mid', $id)
            ->join('__USER__ u', 'u.id=l.create_uid')
            ->where('actionname', LogModel::ACTION_VIEW)
            ->where('tablename', LogModel::MESSAGE)
            ->field('l.id,u.realname,u.face,l.create_time')
            ->order('l.id', 'desc')
            ->select();
        foreach ($viewLog as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }
        //记录查看日志
        LogModel::store($id, LogModel::MESSAGE, LogModel::ACTION_VIEW);

        $message['views'] = $viewLog;
        return json($message);
    }


    /**
     * apicloud推送消息模块
     */
    public function send_push_message(){
        $title = input('request.title')?input('request.title'):"议事通";
        $content = input('request.content')?input('request.content'):"议事通推送内容";
        $userIds = input('request.$userIds');
        MessageModel::send_push_message($title,$content,$userIds);
    }

}