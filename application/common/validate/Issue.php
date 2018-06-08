<?php
namespace app\common\validate;

use think\Validate;

class Issue extends Validate
{
    protected $rule = [
        'name' => 'require'
    ];

    protected $message = [
        'name.require' => '议题标题必须'
    ];
}