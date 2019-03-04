<?php  
namespace controllers;
use models\User;
use models\Order;
use libs\Redis;
use Intervention\Image\ImageManagerStatic as Image;

	class UserController
	{

		public function setActiveUsers()
		{
			$user = new User;
			$user->computeActiveUsers();
		}

		public function uploadbig()
		{
			// 接收表单中所有的数据
			$count = $_POST['count'];  // 总的数量
			$i = $_POST['i'];	// 当前是第几块
			$img = $_FILES['img']; // 图片
			$name = 'big_img_'.$_POST['img_name'];  //所有分块的名字
			$size = $_POST['size'];  // 每块大小

			// 保存每个分片
			move_uploaded_file($img['tmp_name'],ROOT.'tmp/'.$i);

			// 保存到reids中
			// 如果所有分片都上传完的时候，我们开始合并文件
			$redis = \libs\Redis::getInstance();
			// 每上传一张图片，就把redis中的conn_id+1
			$uploadedCount = $redis->incr($name);
			// 如果上传的数量等于总的分片的数量时就代表上传完毕
			if($uploadedCount == $count)
			{
				// 以追回的方式创建并打开最终的大文件
				$fp = fopen(ROOT.'public/upload/big/'.$name.'.png','a');
				// 循环所有的分片
				for($i=0;$i<$count;$i++)
				{
					// 读取第 i 号 文件并写到大文件中
					fwrite($fp,file_get_contents(ROOT.'tmp/'.$i));
					// 删除第 I 号临时文件
					unlink(ROOT.'tmp/'.$i);
				}
				fclose($fp);
				// 从redies中删除这个文件对应的编号这个变量
				$redis->del($name);
			}

		}

		public function uploadall()
		{
			$root = ROOT.'public/uploads/';
			$date = date('Ymd');
			if(!is_dir($root.$date))
			{
				mkdir($root.$date,0777);
			}

			foreach($_FILES['images']['name'] as $k => $v)
			{
				$name = md5( time() . rand(1,99999));	
				$ext = strrchr($v,'.');
				// 名字加上扩展名
				$name = $name.$ext;

				move_uploaded_file($_FILES['images']['tmp_name'][$k],$root.$date.'/'.$name);
			}

		}

		public function ablum()
		{
			view('user.album');
		}

		public function setavatar()
		{
			$upload = \libs\Uploader::make();
			$path = $upload->upload('avatar', 'avatar');
			// var_dump($path);
			// 裁切图片
			$image = Image::make(ROOT . 'public/uploads/'.$path);
			var_dump($image);
			// 注意：Crop 参数必须是整数，所以需要转成整数：(int)
			$image->crop((int)$_POST['w'], (int)$_POST['h'], (int)$_POST['x'], (int)$_POST['y']);
			// 保存时覆盖原图
			$image->save(ROOT . 'public/uploads/'.$path);

			// 保存到user表中
			$model = new \models\User;
			$model->setAvatar('/uploads/'.$path);

			// 删除原头像
			@unlink(ROOT.'public'.$_SESSION['avatar']);

			// 设置新头像
			$_SESSION['avatar']='/uploads/'.$path;

			message('设置成功',2,'/blog/index');


		}

		public function avatar()
		{
			view('user.avatar');
		}

		public function orderStatus()
		{
			$sn = $_GET['sn'];
			
			$try = 10;
			$model = new Order;
			do
			{
				// 查询订单信息
				$info = $model->findBySn($sn);
				// 如果订单未支付就等待1秒，并减少尝试的次数，如果已经支付就退出循环
				if($info['is_paid'] == 0)
				{
					sleep(1);
					$try--;
				}
				else
					break;
				
			}while($try>0);  // 如果尝试的次数到达指定的次数就退出循环

			echo $info['is_paid'];
		}

		public function money()
		{
			$user = new User;
			echo $user->getMoney();
		}

		public function docharge()
		{
			// 生成订单
			$money = $_POST['money'];
			
			$model = new Order;
			$model->create($money);
			message('充值订单已生成，请立即支付',2,'/user/orders');

		}

		public  function orders()
		{
			$order = new Order;
			// 搜索数据
			$data = $order->search();
			
			// 加载视图
			view('user.order',$data);
		
		}



		public function charge()
		{
			view('user.charge');
		}

		public function logout()
		{
			$_SESSION=[];
			die('退出成功');
		}

		public function dologin()
		{
			$email = $_POST['email'];
			$password = md5($_POST['password']);

			$user = new \models\User;
			
			if( $user->login($email, $password) )
			{
				message('登录成功！', 2, '/blog/index');
			}
			else
			{
				message('账号或者密码错误', 1, '/user/login');
			}

		}

		public function login()
		{
			view('user.login');
		}

		public function hello()
		{
			$user = new User;
			$name = $user->getName();

			// 加载视图	
			return view('user.hello',[
				'name'=>$name
			]);
		}

		public function world(){
			echo 'world';
		}

		public function register()
		{
			view('user.add');
		}

		public function store()
		{
			// 获取表中的数据
			$email = $_POST['email'];
			$password = md5($_POST['password']);

			// 生成一个随机的五位数.   让用于猜不出来激活码
			$code = md5(rand(1,99999));

			// 保存到redis
			$redis = \libs\Redis::getInstance();

			// 序列化(数组转换为json字符串)
			$value = json_encode([
				'email'=>$email,
				'password'=>$password,
			]);

			// 设置激活码的过期时间为5分钟
			$key = "temp_user:{$code}";
			$redis->setex($key,300,$value);

			// 把激活码发送到用户的邮箱中
			// 取出用户的名字
			$name = explode("@",$email);
			// 构造收件人地址
			$from = [$email,$name[0]];
			// 构造消息数组

			$message = [
				'title'=>"智能聊天系统-账号激活",
				'content'=>"点击以下链接进行激活：<br> 点击激活：
				<a href='http://localhost:9999/user/active_user?code={$code}'>
				http://localhost:9999/user/active_user?code={$code}</a><p>
				如果按钮不能点击，请复制上面链接地址，在浏览器中访问来激活账号！</p>",
				'from' => $from,
			];
			// 把消息转成字符串(JSON ==> 序列化)
			$message = json_encode($message);
			// 放到队列中
			$redis = \libs\Redis::getInstance();
			$redis->lpush('email', $message);
			echo 'ok';
		}


		public function active_user()
		{
			// 获取激活码
			$code = $_GET['code'];

			// 取出redis中的账号
			$redis = \libs\Redis::getInstance();

			// 拼出名字
			$key = 'temp_user:'.$code;

			// 取出数据
			$data = $redis->get($key);


			if($data)
			{
				// 从redis中删除激活码
				$redis->del($key);
				// 反序列化(转回数组)
				$data = json_decode($data,true);

				// 插入到数据库
				$user = new \models\User;
				$user->add($data['email'],$data['password']);
				// 跳转到登陆页
				header('Localhost:/user/login');
				echo "真棒,激活成功啦!!!";
			}
			else
			{
				die('激活码无效');
			}

		}


	}
?>