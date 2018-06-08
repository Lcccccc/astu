<?php

namespace app\common\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'min:6',
        'status' => 'require|in:1,0',
        'age' => 'number|between:1,200',
        'gender' => 'number|in:1,0'
    ];

    protected $message = [
        'username.require' => '用户名必须',
        'password.min' => '密码最少6位',
        'status.require' => '状态必须',
        'status.in' => '状态只能是1,0',
        'age.number' => '年龄必须是数字',
        'age.between' => '年龄必须在1到200之间',
        'gender.number' => '性别必须是数字',
        'gender.in' => '性别只能是1,0',
    ];
}