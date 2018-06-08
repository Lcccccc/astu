<?php
namespace app\index\model;

use think\Config;
use think\Model;

class CommentModel extends Model
{
    const TYPE_TEXT = 0;//文本消息
    const TYPE_VOICE = 1;//语言消息
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'comment';
        parent::__construct($data);
    }

    /**
     * 获取评论类型标签数组或单个标签
     * @param null $key
     * @return array|mixed|null
     */
    public static function getCommentTypeLabels($key = null)
    {
        $data = [
            self::TYPE_TEXT => '文本评论',
            self::TYPE_VOICE => '语音评论',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}