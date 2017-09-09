<?php
namespace app\controller;
use app\controller\Base;

class Regulations extends Base
{
    public function edit()
    {
    	$plugin = ['editor'];
		$filename = ROOT_PATH . "/info/regulation.json";
        $str = file_get_contents($filename);
        $data = json_decode($str, true);

    	return $this->fetch('edit', ['plugin'=>$plugin, 'data'=>$data]);
    }

	public function save()
	{
		set_url('/regulations/edit');
		$content = input('post.content');
		if ($content == '') {
            $this->error('内容不能为空');
        }

        $data = array(
            'author'  => get_user_name(),
            'time'    => time(),
            'content' => $content
            );
        $str = json_encode($data,JSON_UNESCAPED_UNICODE);
        $filename = ROOT_PATH . "/info/regulation.json";
        @chmod($filename, 0777);
        $len = file_put_contents($filename, $str);
        if($len){
            $this->success('配置生成完毕');
        }else{
            $this->error('配置生成失败');
        }
	}
}
