<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\MessageModel;
use think\Controller;
use think\Request;
use think\Validate;

class Demo extends Common
{
    public function index()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'name' => 'require|max:25',
        ), array(
            'name.require' => '议题标题必须',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode(5001, $validate->getError());
        }
        return json(array('abc' => 1));
    }

    public function testPush()
    {
        $title = '测试推送';
        $content = '测试内容';

        MessageModel::send_socket(json_encode(array(
            'title' => $title,
            'content' => $content,
            'uid' => array(1,2,3),
            'type' => 'push',
        )), null);
    }
}