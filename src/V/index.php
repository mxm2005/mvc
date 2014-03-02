<!doctype html> 
<html> 
<head> 
    <meta charset="utf-8"> 
    <title></title> 
    <style>
    body{font: 16px Monaco,Bitstream Vera Sans Mono, Microsoft YaHei, Arial, sans-serif;} 
    #wrapper{width: 80%; margin: 2% auto; box-shadow: 0 0 4px #999; line-height:30px; padding: 4%; } 
    ::-webkit-scrollbar-track-piece {width:6px; background-color: #fdfdfd; } 
    ::-webkit-scrollbar-thumb {height: 50px; background-color: rgba(0,0,0,.7); 
    -webkit-border-radius: 2px; } ::-webkit-scrollbar {width:6px; height: 6px; } 
    ::selection {background: #FFF200; text-shadow: none; } 
    </style> </head> <body>
    <div id='wrapper'>
        <h2>便捷快速的MVC开发框架</h2><span>update 20140218</span>
        <ul>
        	<li>只有两个核心文件入口文件index.php核心文件core.php,快速便捷,适用于各种项目</li>
        	<li></li>
        </ul>
        <h3>系统流程</h3>
        <ul>
        	<li>1.系统启动入口文件,程序计时开始,$GLOBALS['t']为程序启动的时间</li>
        	<li>2.载入系统核心,系统核心包含了运行所必须的函数和类</li>
        	<li>3.加载URI路由,分析路由,控制器,方法等</li>
        	<li>4.检测是否已缓存,若缓存则直接输出,缓存过期则删除.即缓存依据为url</li>
        	<li>5.若不存在缓存,启动核心进程,尝试加载控制器,实例化控制器,执行action</li>
        	<li>6.如不存在控制器,或者控制器名称与类名称不符,或者找不到指定方法,则抛出错误</li>
        </ul>
        <h3>core.php 十大核心类库</h3>
        <ol>
			<li>class controller 控制器类</li>
			<li>class model 数据库类</li>
			<li>class validate 验证类</li>
			<li>class msg 消息类</li>
        </ol>
        <h3>core.php 二十大核心函数</h3>
        <ol>
        	 <li>uri_init() [系统] 分析路由</li>
        	<li>process() [系统] 根据路由启动核心进程</li>
        	<li>show_errorpage() [系统/用户] 系统默认的异常捕获函数,显示当前异常并退出,用户也可随时调用.</li>
        	<li>redirect() [用户] 从定向到指定地址</li>
        	<li>base_url() [用户] 根据uri拼接为完成的url,并可返回特定字段</li>
        	<li>sendmail() [用户] 由smtp信息发送邮件</li>
        	<li>byte_format() [用户] 字节格式化函数</li>
        	<li>sendsms() [用户] 发送飞信短信</li>
        </ol>
        <h3>functions.php 扩展函数库</h3>
        <ul>
        	<li>扩展函数库用户可以自由添加函数,这些函数都是全局函数,但要在使用的时候加载</li>
        	<li>下面提供了一些常用函数</li>
        	<li></li>
        </ul>

        <p align="center">执行时间 <?=$times?>秒</p>
    </div>

</body>
</html>