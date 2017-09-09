<?php
namespace app\controller;
use app\controller\Base;

class File extends Base
{
    public function index()
    {
        return $this->fetch();
    }

	public function upload()
	{
		@set_time_limit(5*60);
		$file          = request()->file('file');
		$file_name     = $_FILES['file']['name'];
		$controller    = input('post.controller');
		$controller_id = input('post.files_id');
		$info          = $file->validate(['size'=>1024000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS .$controller);
		$url           = DS . 'public' . DS . 'uploads' . DS . $controller . DS. $info->getSaveName();
		$result_file   = $this->store($file_name, $url, $controller, $controller_id);
		if (!$result_file) {
			$return = [
				'code' => 0,
				'info' => '保存文件数据库失败!'
			];
		}
		if($info){
			$return = [
				'code'      => 1,
				'url'       => DS . 'public' . DS . 'uploads' . DS . $controller . DS. $info->getSaveName(),
				'file_id'   => $result_file,
				'file_name' => $file_name
			];
	    }else{
			$return = [
				'code' => 0,
				'info' => $file->getError()
			];
	    }
	    return $return;
	}

	public function store($file_name, $url, $controller, $controller_id)
	{
		$max_sort = model('file')->where(['controller'=>$controller, 'controller_id'=>$controller_id])->max('sort');
		$sort = $max_sort+1;
		$data = [
			'controller' => $controller,
			'controller_id' => $controller_id,
			'file_url' => $url,
			'sort' => $sort,
			'file_name' => $file_name,
			'create_time' => time(),
			'is_del' => 0
		];
		$result = model('file')->insertGetId($data);

		return $result;
	}

	public function update_id($old_id, $controller, $new_id)
	{
		$count = model('file')->where(['controller'=>$controller, 'controller_id'=>$old_id])->count();
		if ($count) {
			$result = model('file')->where(['controller'=>$controller, 'controller_id'=>$old_id])->update(['controller_id'=>$new_id]);
			if (false == $result) {
				return '更新图片id失败';
			}
		}
		return true;
	}

	public function editorUpload()
	{
		@set_time_limit(5*60);
		$file          = request()->file('upload');
		$file_name     = $_FILES['upload']['name'];
		$fn            = input('get.CKEditorFuncNum', 1);
		$controller    = 'notice';
		$controller_id = time();
		$info          = $file->validate(['size'=>1024000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS .$controller);
		if($info){
			$url           = url(DS . 'public' . DS . 'uploads' . DS . $controller . DS. $info->getSaveName(), '', '', true);
			$result_file   = $this->store($file_name, $url, $controller, $controller_id);
			if (!$result_file) {
				$message = '保存文件数据库失败';
			}
			$url     = str_replace("\\", "/", $url);
			$message = '上存成功';
	    }else{
			$message = '文件大于1M或不是jpg,png,gif格式';
			$url     = '';
	    }

	    exit("<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($fn, '$url', '$message');</script>");
	}
}


