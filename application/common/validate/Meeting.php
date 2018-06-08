<?php
namespace app\common\validate;

use think\Validate;

class Meeting extends Validate
{
    protected $rule = [
        'title' => 'require',
        'address_id' => 'require',
        'join_id' => 'require',
        'date' => 'require|date',
        'start_time' => 'require',
        'end_time' => 'require',
        'issue_id' => 'require',
    ];

    protected $message = [
        'title.require' => '标题必须',
        'address_id.require' => '地址必须',
        'join_id.require' => '参会人员必须',
        'date.require' => '会议日期必须',
        'date.date' => '会议日期必须是日期格式',
        'start_time.require' => '开始时间必须',
        'end_time.require' => '结束时间必须',
        'issue_id.require' => '会议议题必须',
    ];
}