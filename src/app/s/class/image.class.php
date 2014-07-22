<?php

/**
* image 图像处理
* 图像缩放,加水印,旋转,裁剪,缩略,验证码
* 生成验证码 $image->vcode($num,$rgb)
* 生成占位符 $image->placeholder($w,$h,$rgb)
* $image->thumb($path,$w,$h) //返回缩略图 ,$path可以是本地或者远程
* 建议开启http缓存使用 C(60); 缓存1个小时
*/
class image 
{
	const dict='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; //产生验证码的字典

	
	function __construct()
	{
		C(60);
	}
	/**
	 * 生成验证码随机数
	 */
	private static function random($num=4)
	{
		$len=strlen(self::dict);
		$dict=self::dict;
		$code=null;
		while($num--)
		{
			$code.=$dict[mt_rand(0,$len-1)];
		}
		return $code;
	}
	/**
	 * 生成验证码,不能有其他输出
	 */
	public function  vcode($num=4,$gb=null)
	{
		
		Header("Content-type: image/PNG");
		$code=self::random($num);
		$width=10*$num+10;
		$im = imagecreate($width,28); // 画一张指定宽高的图片
		$bg = ImageColorAllocate($im, isset($gb[0])?$gb[0]:255,isset($gb[1])?$gb[1]:255,isset($gb[2])?$gb[2]:255); // 定义背景颜色
		for ($i=0; $i <$num ; $i++)
		{ 
			$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255)); // 生成随机颜色
			imagestring($im,6, 5+$i*10, 5, $code[$i], $randcolor);
		}
		for ($j=0,$len=$width*2; $j <$len ; $j++)
		{ 
			$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
    		imagesetpixel($im, rand(1,$width) , rand(1,30), $randcolor); // 画像素点函数
		}
		ImagePNG($im);
     	ImageDestroy($im);
    	return	session_set('VCODE',$code);//设置session
	}
	/**
	 * 生成图片占位符,需提供宽高,背景颜色
	 */
	function placeholder($w,$h,$gb=null)
	{
		Header("Content-type: image/PNG");
		$im=imagecreate($w,$h);
		$bg=ImageColorAllocate($im, isset($gb[0])?$gb[0]:200,isset($gb[1])?$gb[1]:200,isset($gb[2])?$gb[2]:200); // 定义背景颜色
		$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255)); // 生成随机颜色
		$text=$w.'X'.$h;
		$font=ROOT.'static/font/monaco.ttf';
		$size=$w/5>$h/5?$h/5:$w/5;
		$arr=ImageTTFBBox($size, 0, $font,$text);
		$text_w=$arr[2]-$arr[0];
		$text_h=$arr[6]-$arr[7];
		$x=($w-$text_w)/2;
		$y=($h+$text_h)/2;
		ImageTTFText($im, $size,0,$x,$y,$randcolor,$font,$text);
		ImagePNG($im);
     	ImageDestroy($im);

	} 
	/**
	 * 图片采集与缩放
	 */
	function thumb($path,$set_w=null,$set_h=null)
	{
		if(preg_match('/http(s)?:\/\/([a-z0-9]+\.)+([a-z0-9]+\/)+.*/i',$path,$matches)) //远程地址
		{
			$tmp=sys_get_temp_dir().'/thumb.jpg';
			file_put_contents($tmp,file_get_contents($path));
			$path=$tmp;
		}		
		$arr=getimagesize($path); //原始图像大小 $type 1gif 2jpg 3png
		$w=&$arr[0];
		$h=&$arr[1];
		$type=&$arr[2];
		$mime=&$arr['mime'];
		$set_w=is_null($set_w)?1:$set_w;
		$set_h=is_null($set_h)?($set_w<=2?$set_w:1):$set_h;
		$real_w=$set_w<=2?$w*$set_w:$set_w;
		$real_h=$set_h<=2?$h*$set_h:$set_h;
		
		$new_img=imagecreatetruecolor($real_w,$real_h);

		switch ($type)
		{
			case 1: //gif
				$src_image=imagecreatefromgif($path);
				imagecopyresampled($new_img,$src_image,0,0,0,0,$real_w,$real_h,$w,$h);
				header('Content-Type: '.$mime);
				imagegif($new_img);
				break;
			case 2: //jpg
				$src_image=imagecreatefromjpeg($path);
				imagecopyresampled($new_img,$src_image,0,0,0,0,$real_w,$real_h,$w,$h);
				header('Content-Type: '.$mime);
				imagejpeg($new_img);
				break;
			default: //png
				$src_image=imagecreatefrompng($path);
				imagecopyresampled($new_img,$src_image,0,0,0,0,$real_w,$real_h,$w,$h);
				header('Content-Type: '.$mime);
				imagepng($new_img);
				break;
		}
		imagedestroy($new_img);
		imagedestroy($src_image);
		

	}


}