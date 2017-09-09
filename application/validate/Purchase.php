<?php
namespace app\validate;
use think\Validate;

class Purchase extends Validate
{
    protected $rule = [
        'item_name' => 'require',
        'item_num' => 'require',
        'item_price' => 'require',
		'item_way' => 'require',
        'reason' => 'require',
    ];

    protected $message = [
        'item_name.require'  =>  '物品名称不能为空!',
        'item_num.require'  =>  '物品数量不能为空!',
		'item_price.require'  =>  '物品价格不能为空!',
        'item_way.require'  =>  '购买渠道不能为空!',
		'reason.require'  =>  '购物事由不能为空!',
    ];

    protected $scene = [
        'add' =>  ['item_name', 'item_num', 'item_price', 'item_way', 'reason']
    ];
}
