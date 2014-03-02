<?



/**
* php curl 并发 多线程
* @author suconghou <suconghou@126.com>
* @version v1.0
* @blog http://blog.suconghou.cn
* @date 2013.12.25
* 
*/
class curl 
{
    private $mh;
    private $ch;
    
    function __construct()
    {
         $this-> mh=curl_multi_init();//创建批处理cURL句柄
    }



    //增加一个/组请求,默认不返回header,返回body
    //url为array
    function add($url_array,$header=0,$no_body=0,$timeout=10)
    {
        is_array($url_array)||exit('should be a array');
        foreach ($url_array as  $value)
        {
            $this-> ch[$value]=curl_init();           
            curl_setopt_array($this-> ch[$value], array(CURLOPT_URL=>$value,CURLOPT_HEADER=>$header,CURLOPT_TIMEOUT=>$timeout,CURLOPT_NOBODY=>$no_body,CURLOPT_RETURNTRANSFER=>1));
            curl_multi_add_handle($this-> mh,$this-> ch[$value]);
        }
        return $this;

      
    }

    ///执行所有请求
    function exec()
    {
        $running=null;
        do
        {
            curl_multi_exec($this-> mh, $running);
            curl_multi_select($this-> mh);
        }
        while ($running > 0);
        foreach ($this-> ch as $key => $value)
        {
           $result[$key]=curl_multi_getcontent($value);
           curl_multi_remove_handle($this-> mh,$value);
           curl_close($value);
        }
        curl_multi_close($this-> mh);
        return $result;
    }

    //快速发起忽略返回值的并行请求
    function quick_exec($url_array)
    {

        is_array($url_array)||exit('should be a array');
        foreach ($url_array as $key => $value)
        {
            $this-> ch[$key]=curl_init();           
            curl_setopt_array($this-> ch[$key], array(CURLOPT_URL=>$value,CURLOPT_HEADER=>0,CURLOPT_TIMEOUT=>1,CURLOPT_NOBODY=>1));
            curl_multi_add_handle($this-> mh,$this-> ch[$key]);
        }
        $running=null;
        do
        {
            curl_multi_exec($this-> mh,$running);
        }
        while($running > 0);
        return true;

    }
}