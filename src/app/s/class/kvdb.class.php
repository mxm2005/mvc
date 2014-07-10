<?php

/**
*		基于文本的简易k->v数据库
* 		kvdb API 
* 		
*  		set($key,$value);
*		sets($arr);//一次设置多个
*       get($key);
*		gets($key); 以x开头或者所有
*		del($key);
*		flush();//删除所有
*		like($key);//包含x的
*		初始化参数  tmp--随机零时文件
*					tmp/aa--固定零时文件
*                   其他字符 app/s生成文件，为空加载默认
*/
class kvdb 
{
	private static $dbfile='kvdb.db';///默认数据库
	private static $dbarr;

	function __construct($file=null)
	{

		self::init($file);

	}

	///从文本读入内存
	private static function read()
	{
		$dbarr=json_decode(file_get_contents(self::$dbfile));
		return $dbarr; 
	}
	//存入文本
	private static function write($dbarr)
	{
		$dbarr=json_encode($dbarr);
		return file_put_contents(self::$dbfile,$dbarr);
	}
	private static function init($file=null)
	{
		if($file==null)//加载默认
		{
			self::$dbfile=LIB_PATH.self::$dbfile;
			
		}
		else if($file=='tmp')
		{	
			self::$dbfile=sys_get_temp_dir().'/KVDB.DB'; 
		}
		else //指定文件名
		{
			self::$dbfile=LIB_PATH.$file;
		}
		if(!file_exists(self::$dbfile))
		{
			$init=array('init_time'=>time());
			file_put_contents(self::$dbfile,json_encode($init));
		}
		self::$dbarr=self::read();
	}
	function get($key)
	{
		return isset(self::$dbarr->$key)?self::$dbarr->$key:null;
	}
	function gets($key=null)
	{
		if(empty(self::$dbarr))
		{
			$this->flush();
		}
		foreach (self::$dbarr as $k=> $v)
		{
			if(substr($k,0,strlen($key))==$key)
			{
				$res[$k]=$v;
			}
		}
		return isset($res)?$res:null;

	}
	function set($key,$value)
	{
		if(empty(self::$dbarr))
		{
			$this->flush();
		}
		self::$dbarr->$key=$value;

	}
	function sets($arr)
	{
		if(empty(self::$dbarr))
		{
			$this->flush();
		}
		foreach ($arr as $key => $value)
		{
			self::$dbarr->$key=$value;
		}
	}
	function del($key)
	{
		if(isset(self::$dbarr->$key))
		{
			unset(self::$dbarr->$key);	
		}
		else
		{
			return true;
		}
	}
	function flush()
	{
		self::$dbarr=(object)array('init_time'=>time());
	}

	function like($key=null)
	{
		if(empty(self::$dbarr))
		{
			$this->flush();
		}
		foreach (self::$dbarr as $k => $v)
		{
			if (stripos($k,$key)!==false)
			{
				$res[$k]=$v;
			}
		}
		return isset($res)?$res:null;

	}
	function change($file=null)
	{
		if($file==null)
		{
			self::$dbfile='kvdb.db';
		}
		self::write(self::$dbarr);
		self::init($file);

	}
	function __destruct()
	{
		return self::write(self::$dbarr);

	}

}
