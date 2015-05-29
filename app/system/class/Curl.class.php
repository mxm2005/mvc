<?

/**
* php curl 并发 多线程
* @author suconghou <suconghou@126.com>
* @version v1.3 <2014.7.19>
* @link http://blog.suconghou.cn
* $a=$curl->quick_exec($url);
* add 第一个参数网址数组，第二个header，第三个nobody，第四个超时时间
* 默认返回正文，超时10秒
* $a=$curl->add($url_arr,1,0)->add($url_arr2)->exec();
* $a=$curl->add($url)->exec();
* $curl->post($url,$post_data);
* $b=$curl->add($url)->fetch('img');img/src/url/href/或者自定义正则
*/
class Curl 
{
	private $mh;
	private $ch;

	function __construct()
	{
		 $this->mh=curl_multi_init();//创建批处理cURL句柄
	}
	//增加一个/组请求
	//url为array
	function add($url,$header=0,$no_body=0,$timeout=10)
	{
		if(is_null($this->mh))
		{
			self::__construct();
		}
		if(is_array($url))
		{
			$url_array=$url;
		}
		else
		{
			$url_array=array($url);
		}
		foreach ($url_array as $key=>$value)
		{  
			$this->ch[$key]=curl_init();   
			curl_setopt_array($this->ch[$key], array(CURLOPT_URL=>$value,CURLOPT_HEADER=>$header,CURLOPT_TIMEOUT=>$timeout,CURLOPT_NOBODY=>$no_body,CURLOPT_RETURNTRANSFER=>1));
			curl_multi_add_handle($this->mh,$this->ch[$key]);
		}
		return $this;
	}

	///执行所有请求
	function exec($one=null)
	{
		if(!($this->mh&&$this->ch))return false;
		$running=null;
		do
		{
			curl_multi_exec($this->mh, $running);
			curl_multi_select($this->mh);
		}
		while ($running > 0);
		foreach ($this->ch as $key => $value)
		{
		   $result[$key]=curl_multi_getcontent($value);
		   curl_multi_remove_handle($this->mh,$value);
		   curl_close($value);
		}
		curl_multi_close($this->mh);
		$this->mh=$this->ch=null;
		if($one)
		{
			$out=null;
			foreach ($result as $key => $value)
			{
				$out.=$value;
			}
			return $out;
		}
		else
		{
			return $result;             
		}
	}

	//快速发起忽略返回值的并行请求
	function quickExec($url)
	{
		if(!$this->mh)
		{
			$this->mh=curl_multi_init();
		}
		if(is_array($url))
		{
			$url_array=&$url;
		}
		else
		{
			$url_array=array($url);
		}
		foreach ($url_array as $key => $value)
		{

			$this->ch[$key]=curl_init();           
			curl_setopt_array($this->ch[$key], array(CURLOPT_URL=>$value,CURLOPT_HEADER=>0,CURLOPT_TIMEOUT=>1,CURLOPT_NOBODY=>1));
			curl_multi_add_handle($this->mh,$this->ch[$key]);
		}
		$running=null;
		do
		{
			curl_multi_exec($this->mh,$running);
		}
		while($running > 0);
		foreach ($this->ch as $key => $value)
		{
		   curl_multi_remove_handle($this->mh,$value);
		   curl_close($value);
		}
		curl_multi_close($this->mh);
		$this->mh=$this->ch=null;
		return true;

	}
	/**
	 * CURL 发起POST
	 */
	static function post($url,$post_string=null,$timeout=5)
	{
		$ch=curl_init();
		curl_setopt_array($ch, array(CURLOPT_URL=>$url,CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_TIMEOUT=>$timeout,
			CURLOPT_RETURNTRANSFER=>1,CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>is_array($post_string)?http_build_query($post_string):$post_string));
		$result=curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	/**
	 * CURL 发起GET
	 */
	static function get($url,$timeout=5)
	{
		$ch=curl_init($url);
		curl_setopt_array($ch, array(CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_TIMEOUT=>$timeout, CURLOPT_RETURNTRANSFER=>1,CURLOPT_HEADER=>0));
		$result=curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	/**
	 * CURL 发送文件
	 * $data = array("username" => $username,"password"  => $password,"file"  => "@".realpath("1.jpg") );
	 */
	static function sendFile($url,$post_data,$timeout=20)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, 1 );
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt($curl, CURLOPT_POSTFIELDS,$post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$result = curl_exec($curl);
		$error = curl_error($curl);
		return $error ? $error : $result;

	}
	/**
	 * 内部规则 
	 * img  提取 http://xxxx.jpg  图片全地址
	 * src  提取 <img src=''  所有能够自己显示的图片
	 * href 提取 <a href=''  所有连接
	 * url  提取 http://    符合http:// 的地址
	 */
	function fetch($type,$url=null,$index=null)
	{
		if($url)
		{
			$res=$this->add($url)->exec(true);
		}
		else
		{
			$res=$this->exec(true);
		}
		$regex['img']='/https?:\/\/[a-z0-9_-]+(\.[a-z0-9_-]+){1,5}(\/[a-z0-9_-]+){1,9}\.(jpg|jpeg|png|gif|bmp)/i';
		$regex['src']='/<img.+?src=(\"|\')(.{5,}?)(\"|\').+?\/?>/i';
		$regex['url']='/https?:\/\/[a-z0-9_-]+(\.[a-z0-9_-]+){1,5}(\/[a-z0-9_-]+){0,9}(\.\w+)?/i';
		$regex['href']='/<a.+?href=(\"|\')(.+?)(\"|\').+?>.+?<\/a>/i';

		switch ($type)
		{
			case 'img':
				$index=0;
				return $this->filter($res,$regex['img'],$index);
			case 'src':
				$index=2;
				return $this->filter($res,$regex['src'],$index);
			case 'url':
				$index=0;
				return $this->filter($res,$regex['url'],$index);
			case 'href':
				$index=2;
				return $this->filter($res,$regex['href'],$index);
			case 'all':
				$ret['img']=$this->filter($res,$regex['img'],0);
				$ret['src']=$this->filter($res,$regex['src'],2);
				$ret['url']=$this->filter($res,$regex['url'],0);
				$ret['href']=$this->filter($res,$regex['href'],2);
				return $ret;
			default:
				if(!preg_match('/^\/.+\/$/',$type)) {return $res;}//不是正则规则
				return $this->filter($res,$type,$index);
		}
	}
	private function filter($html,$regex,$index=null)
	{
		if(preg_match_all($regex,$html,$matches))
		{
			if(is_null($index))
			{
				return $matches;
			}
			return isset($matches[$index])?array_unique($matches[$index]):array();
		}
		return array();

	}
}