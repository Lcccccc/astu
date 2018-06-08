<?php
/**
 * Created by PhpStorm.
 * User: huarui
 * Date: 2017/7/11
 * Time: 16:12
 */

namespace app\common\validate;

use think\Validate;

class Dept extends Validate
{
    protected $rule = [
        'name' => 'require',
        'pid' => 'require',
        'status' => 'require|in:1,0'
    ];

    protected $message = [
        'name.require' => '部门名称必须',
        'pid.require' => '上级部门必须',
        'status.require' => '状态必须',
        'status.in' => '状态只能是1,0',
    ];
}