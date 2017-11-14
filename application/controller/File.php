<?php
namespace app\controller;
use app\controller\Base;

class File extends Base
{
	private $error = '';
	private $filename = null;

    public function index()
    {
        return $this->fetch();
    }

	public function upload()
	{
		@set_time_limit(5*60);
		$file          = $_FILES['file'];
		$controller    = input('post.controller');
		$controller_id = input('post.files_id');
		$savename      = $this->move(ROOT_PATH . 'public' . DS . 'uploads' . DS .$controller, $file);
		if(false !== $savename){
			$url           = DS . 'public' . DS . 'uploads' . DS . $controller . DS. $savename;
			$result_file   = $this->store($file['name'], $url, $controller, $controller_id);
			if (!$result_file) {
				$return = [
					'code' => 0,
					'info' => '保存文件数据库失败!'
				];
			}
			$return = [
				'code'      => 1,
				'url'       => DS . 'public' . DS . 'uploads' . DS . $controller . DS. $savename,
				'file_id'   => $result_file,
				'file_name' => $file['name'],
				'ext'       => pathinfo($file['name'], PATHINFO_EXTENSION)
			];
	    }else{
			$return = [
				'code' => 0,
				'info' => '上存文件失败:' . $this->error
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

	public function move($path, $file, $savename = true, $replace = true)
    {
        // 检测合法性
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->error = '非法上传文件';
            return false;
        }

        // 验证上传
        if (!$this->check($file, ['size'=>1024000,'ext'=>'jpg,png,xlsx,xls'])) {
            return false;
        }
        $path = rtrim($path, DS) . DS;
        // 文件保存命名规则
        $saveName = $this->buildSaveName($file['name']);
        $filename = $path . $saveName;

        // 检测目录
        if (false === $this->checkPath(dirname($filename))) {
            return false;
        }

        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            $this->error = '存在同名文件' . $filename;
            return false;
        }

        /* 移动文件 */
       	if (!move_uploaded_file($file['tmp_name'], $filename)) {
            $this->error = '文件上传保存错误！';
            return false;
        }

        return $saveName;
    }


	protected function buildSaveName($file_name)
    {
        $savename = date('Ymd') . DS . md5(microtime(true));
        if (!strpos($savename, '.')) {
            $savename .= '.' . pathinfo($file_name, PATHINFO_EXTENSION);
        }
        return $savename;
    }

	protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            $this->error = "目录 {$path} 创建失败！";
            return false;
        }
    }

	public function check($file, $rule = [])
    {

        /* 检查文件大小 */
        if (isset($rule['size']) && !$this->checkSize($file, $rule['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }

        /* 检查文件Mime类型 */
        if (isset($rule['type']) && !$this->checkMime($file, $rule['type'])) {
            $this->error = '上传文件MIME类型不允许！';
            return false;
        }

        /* 检查文件后缀 */
        if (isset($rule['ext']) && !$this->checkExt($file, $rule['ext'])) {
            $this->error = '上传文件后缀不允许';
            return false;
        }

        return true;
    }

	public function checkSize($file, $size)
    {
        if ($file['size'] > $size) {
            return false;
        }
        return true;
    }

	public function checkMime($file, $mime)
    {
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }
        if (!in_array(strtolower($file['type']), $mime)) {
            return false;
        }
        return true;
    }

	public function checkExt($file, $ext)
    {
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $ext)) {
            return false;
        }
        return true;
    }
}


