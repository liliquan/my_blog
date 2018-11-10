<?php
ini_set('session.save_handler', 'redis');   // 使用 redis 保存 SESSION
ini_set('session.save_path', 'tcp://127.0.0.1:6379?database=3');  // 设置 redis 服务器的地址、端口、使用的数据库    
// ini_set('session.gc_maxlifetime', 600);   // 设置 SESSION 10分钟过期
session_start();


// if($_SERVER['REQUEST_METHOD'] == 'POST')
// {
//     if(!isset($_POST['_token']))
//         die('违法操作！');

//     if($_POST['_token'] != $_SESSION['token'])
//         die('违法操作！');
// }

// 定义常量  
// __FILE__是PHP中的魔术常量，代表当前文件的绝对路径
// dirname php中的内置函数，用来从一个路径中解析出目录的部分
//  /../   因为index.php保存在public文件下
define('ROOT', dirname(__FILE__) . '/../');

require(ROOT.'vendor/autoload.php');

// 实现类的自动加载  使用   spl_autoload_register   函数
// 这个函数的功能就是  我们可以使用这个函数注册一个我们自己定义的函数，当PHP在使用一个类时，如果这个类文件没有被加载，那么被注册的函数就调用了，并把这个类的名字传给这个函数，然后我们就可以写代码在函数中加载类文件
function  autoLoad($class)
{

	$path = str_replace('\\','/',$class);
	require (ROOT . $path .'.php');
} 

spl_autoload_register('autoLoad');



// 添加路由  解析url上的路径  控制器/方法
// 第一个为控制器的名字 
// 第二个是控制器里面的方法 
// 中间斜线隔开
// 
// 获取url上的路径  （使用超全局变量$_SERVER）
// 打印数组结构很乱时，在打印之前输出一个   echo '<pre>'
if(php_sapi_name() == 'cli')
{
	$controller = ucfirst($argv[1]).'controller';
	$action = $argv[2];
}
else
{
	if( isset($_SERVER['PATH_INFO']))
	{
		$pathInfo = $_SERVER['PATH_INFO'];
		// 根据  /  转换成数组  使用explode
		$pathInfo = explode('/' , $pathInfo);


		// 得到控制器名和方法名
		// 首字母大写   ucfirst
		$controller = ucfirst($pathInfo[1]) . 'Controller';

		$action = $pathInfo[2];
		
	}
	else
	{
		// 默认控制器
		$controller = 'IndexController';
		$action = 'index';
	}
}


// 为控制器添加命名空间
$fullController = 'controllers\\'.$controller;


$_C = new $fullController;
$_C->$action();



// 加载视图
// 参数一   加载的视图的文件名
// 参数二	向视图中传的数据
function view($viewFileName,$data=[])
{	
	// 解压数组成变量	
	// extract 函数的功能是把一个数组展成多个变量，解压$data里面传过来的数据
	extract($data);

	// users.hello => users/hello.html
	$path = str_replace('.' , '/' , $viewFileName) . '.html';

	// 加载视图
	require(ROOT . 'views/' .$path); 
}

// 获取当前url上所有的参数,并且能排除某些不需要的参数
function getUrlParams($except = [])
{
	foreach($except as $v)
	{
		unset($_GET[$v]);
	}

	$str = '';
	foreach($_GET as $k => $v)
	{
		$str .= "$k=$v&";
	}
	return $str;
}


function config($name)
{
	static $config = null;
	if($config === null)
	{
		$config = require(ROOT.'config.php');
	}
	return $config[$name];
}
	
function redirect($route)
{
	header('Location:'.$route);
	exit;
}

function back()
{
	redirect($_SERVER['HTTP_REFERER']);
}


// ****************** XSS攻击 过滤  ****************
function e($content)
{
	return htmlspecialchars($content);
}

function hep($content)
{
	static $purifier = null;

	if($purifier === null)
	{
		// 1. 生成配置对象
		$config = \HTMLPurifier_Config::createDefault();

		// 2. 配置
		// 设置编码
		$config->set('Core.Encoding', 'utf-8');
		$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
		// 设置缓存目录
		$config->set('Cache.SerializerPath', ROOT.'cache');
		// 设置允许的 HTML 标签
		$config->set('HTML.Allowed', 'div,b,strong,i,em,a[href|title],ul,ol,ol[start],li,p[style],br,span[style],img[width|height|alt|src],*[style|class],pre,hr,code,h2,h3,h4,h5,h6,blockquote,del,table,thead,tbody,tr,th,td');
		// 设置允许的 CSS
		$config->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,margin,width,height,font-family,text-decoration,padding-left,color,background-color,text-align');
		// 设置是否自动添加 P 标签
		$config->set('AutoFormat.AutoParagraph', TRUE);
		// 设置是否删除空标签
		$config->set('AutoFormat.RemoveEmpty', true);

		// 3. 过滤
		// 创建对象
		$purifier = new \HTMLPurifier($config);
	}
	
	// 过滤
	$clean_html = $purifier->purify($content);
	return $clean_html;
}


// ********************  CSRF攻击  ***************************
function csrf()
{
	if(!isset($_SESSION['token']))
	{
		$token = md5(rand(1,99999).microtime());
		$_SESSION['token'] = $token;
	}
	return $_SESSION['token'];
}



// 提示消息的函数
// type 0:alert   1:显示单独的消息页面  2：在下一个页面显示
// 说明：$seconds 只有在 type=1时有效，代码几秒自动跳动
function message($message,$type,$url,$seconds = 5)
{
	if($type==0)
	{
		echo "<script>alert('{$message}');location.href='{$url}';</script>";
        exit;
	}
	else if($type==1)
	{
		 // 加载消息页面
		 view('common.success', [
            'message' => $message,
            'url' => $url,
            'seconds' => $seconds
        ]);
	}
	else if($type==2)
	{
		// 把消息保存到 SESSION
        $_SESSION['_MESS_'] = $message;
        // 跳转到下一个页面
        redirect($url);
	}
}


?>