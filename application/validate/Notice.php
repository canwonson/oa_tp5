<?php
namespace app\validate;
use think\Validate;

class Notice extends Validate
{
    protected $rule = [
        'title' => 'require',
        'content'   => 'require',
    ];

    protected $message = [
        'title.require' =>  '标题必须填写',
        'content.require'   =>  '内容必须填写',
    ];

    protected $scene = [
        'add'   =>  ['title', 'content']
    ];
}
