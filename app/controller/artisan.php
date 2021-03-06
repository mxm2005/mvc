<?php

/**
* 框架命令行工具
*/
final class artisan extends base
{
	
	public function __construct()
	{
		self::onlyCli();
	}

	public function index()
	{

	}
	//发布新版本,更新版本号和缓存,服务端执行
	public function release($update=true)
	{
		function_exists('opcache_reset')&&opcache_reset();
		if(substr(ROOT,0,7)!='phar://')
		{
			$update&&$this->update(0,true);
			$script=$_SERVER['argv'][0];
			$subject=file_get_contents($script);
			$pattern='/base::version\((\d+)\)/';
			$data=preg_replace_callback($pattern,function($matches)
			{
				$version=$matches[1]+1;
				echo PHP_EOL."new version {$version} released at ".date('Y-m-d H:i:s').PHP_EOL;
				return "base::version({$version})";
			},$subject);
			return $data==$subject?false:file_put_contents($script,$data);
		}
	}
	//手动推动至远程服务器,开发端执行
	public function deploy()
	{
		$host='ftp://user:password@example.com/public_html';
		$script=array_shift($_SERVER['argv']);
		$pharName=rtrim($script,'php').'phar';
		$cmd="php {$script}";
		passthru($cmd);
		echo 'uploading...'.PHP_EOL;
		$ret=Curl::sendToFtp($host,$pharName,$script);
		echo ($ret?'upload success':'upload error').' cost time '.app::cost('time').'s'.PHP_EOL;
	}

	//持久化或一次性自动更新任务,服务端执行
	public function update($time=60,$once=false)
	{
		app::timer(function() use($time,$once)
		{
			try
			{
				$cmd='git pull origin master';
				return passthru($cmd);
			}
			catch(Exception $e)
			{
				echo $e->getMessage();
			}
			$once||sleep($time);
		},$once);

	}

}