<?php
namespace app\common\validate;

use think\Validate;

class Comment extends Validate
{
    protected $rule = [
        'mid' => 'require',
        'content' => 'require',
    ];

    protected $message = [
        'mid.require' => '议题ID必须',
        'content.require' => '评论内容必须',
    ];
}