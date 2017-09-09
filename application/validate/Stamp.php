<?php
namespace app\validate;
use think\Validate;

class Stamp extends Validate
{
    protected $rule = [
		'stamp_type'      => 'require',
		'is_outer'        => 'require',
		'use_reason'      => 'require',
		'stamp_file_name' => 'require'
    ];

    protected $message = [
		'stamp_type.require'      =>  '申请印章类型必须选择',
		'is_outer.require'        => '是否外借必须选择',
		'use_reason.require'      =>  '印章使用事由必须填写',
		'stamp_file_name.require' =>  '用章文件全称必须填写',
    ];

    protected $scene = [
        'add'   =>  ['stamp_type', 'is_outer', 'use_reason', 'stamp_file_name']
    ];
}
