<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\CommentModel;
use think\Db;
use think\Loader;
use think\Request;
use think\Session;

class Comment extends Common
{
    /**
     * 添加文本评论
     * @return \think\response\Json
     */
    public function store()
    {
        $params = Request::instance()->param();
        $validate = Loader::validate('Comment');
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        $user = Session::get('ext_user');
        CommentModel::create([
            'mid' => $params['mid'],
            'content' => $params['content'],
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);
        return $this->successCode();
    }

    /**
     * 评论列表
     * @return \think\response\Json
     */
    public function index()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '议题ID必须');
        }
        $user = Session::get('ext_user');
        $comment = Db::name('comment')->alias('c')
            ->join($this->prefix . 'user u', 'u.id=c.create_uid', 'LEFT')
            ->field('c.id,c.content,c.type,c.duration,c.create_uname,c.create_uid,c.create_time,u.face')
            ->where('c.mid', $id)
            ->order('c.id', 'desc')
            ->select();
        foreach ($comment as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
            $item['own'] = $item['create_uid'] == $user['id'] ? 1 : 0;
        }
        return $this->successCode($comment);
    }

    /**
     * 存储语音评论
     * @return \think\response\Json
     */
    public function voice()
    {
        $id = input('request.mid');//获取
        $duration = input('request.duration', 0);
        $file = request()->file('file');
        if (empty($id)) {
            return $this->errorCode(5001, '议题ID必须');
        }

        $info = $file->validate(['ext' => 'amr'])->move(ROOT_PATH . '/public/uploads/voice');
        if (!$info) {
            return $this->errorCode(4001, $info->getError());
        }
        $user = Session::get('ext_user');
        // 成功上传后 获取上传信息
        $voicePath = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/voice/' . $info->getSaveName();

        CommentModel::create([
            'mid' => $id,
            'content' => $voicePath,
            'type' => CommentModel::TYPE_VOICE,
            'duration' => $duration,
            'create_uid' => $user['id'],
            'create_uname' => $user['realname'],
            'create_time' => time(),
        ]);

        return $this->successCode();
    }

    /**'
     * 删除自己评论
     * @return \think\response\Json
     */
    public function remove()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '评论ID必须');
        }
        $user = Session::get('ext_user');
        $comment = CommentModel::get($id);
        if ($user['id'] != $comment['create_uid']) {
            return $this->errorCode(5006);
        }
        $comment->delete();

        return $this->successCode();
    }
}
