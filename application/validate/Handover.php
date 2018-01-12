<?php
namespace app\validate;
use think\Validate;

class Handover extends Validate
{
    protected $rule = [
		'entry_time'        => 'require',
		'over_time'         => 'require',
		'receiver'          => 'require',
		'content'           => 'require',
    ];

    protected $message = [
		'entry_time.require'        => '入职时间必须填写',
		'over_time.require'         => '交接时间必须填写',
		'receiver.require'          => '交接人必须填写',
		'content.require'           => '交接内容必须填写',
    ];

    protected $scene = [
        'add'   =>  ['entry_time', 'over_time', 'receiver', 'content']
    ];
}
