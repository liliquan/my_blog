<?php 
// 声明该类的命名空间
namespace models;
use PDO;

	class User extends Base
	{	
		public function getActiveUsers()
		{
			$redis = \libs\Redis::getInstance();
			$data = $redis->get('active_users');
			// 转回数组(第二个参数 true:数组 false:对象)
			return json_decode($data,true);
		}

		public function computeActiveUsers()
		{
			// 取日志的值
			$stmt = self::$pdo->query('SELECT user_id,count(*)*5 fz
									  FROM blogs
									  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
									  GROUP BY user_id');
			$data1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// 取出评论的值
			$stmt = self::$pdo->query('SELECT user_id,COUNT(*)*3 fz
									   FROM comments
									   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
									   GROUP BY user_id');
			$data2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// 取出赞的分值
			$stmt = self::$pdo->query('SELECT user_id,count(*) fz
									   FROM blog_agrees
									   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
									   GROUP BY user_id');

			$data3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// 合并数组
			$arr = [];

			// 合并第一个数组到空数组
			foreach($data1 as $v)
			{
				$arr[$v['user_id']] = $v['fz'];
			}
			// 合并第二个数组到数组中
			foreach($data2 as $v)
			{
				if(isset($arr[$v['user_id']]))
					$arr[$v['user_id']] += $v['fz'];
				else
					$arr[$v['user_id']] = $v['fz'];
			}

			// 合并第三个数组到数组中
			foreach($data3 as $v)
			{
				if(isset($arr[$v['user_id']]))
					$arr[$v['user_id']] += $v['fz'];
				else
					$arr[$v['user_id']] = $v['fz'];
			}

			// 倒序排序
			arsort($arr);
			
			// 取前20并保存键(第四个参数保留键)
			$data = array_slice($arr,0,20,TRUE);

			// 取出前20用户的ID
			// 从数组中取出所有的键
			$userId = array_keys($data);
			// 数组转字符串
			$userId = implode(',',$userId);

			// 取出用户的头像和email
			$sql = "SELECT id,email,avatar FROM users WHERE id IN($userId)";

			$stmt = self::$pdo->query($sql);
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// 结果保存到redis中，但是redis只能保存字符串，所以我们要将JSON转成字符串
			$redis = \libs\Redis::getInstance();
			$redis->set('active_users',json_encode($data));

		}

		public function setAvatar($path)
		{
			$stmt = self::$pdo->prepare('UPDATE users SET avatar=? WHERE id=?');
			
			$stmt->execute([
				$path,
				$_SESSION['id'],
			]);
		}

		public function getMoney()
		{
			$id = $_SESSION['id'];
			$stmt = self::$pdo->prepare('SELECT money FROM users WHERE id = ?');
			$stmt->execute([
				$id
			]);
			$money = $stmt->fetch(PDO::FETCH_COLUMN);
			$_SESSION['money']=$money; 
			return $money;
		}

		public function addMoney($money,$userId)
		{
			$stmt = self::$pdo->prepare('UPDATE users SET money=money+? where id=?');
			return $stmt->execute([
				$money,
				$userId,
			]);
			
			
			// $_SESSION['money'] += '$money';
		}


		public function add($email,$password)
		{
			$stmt = self::$pdo->prepare('INSERT INTO users (email,password) VALUES (?,?)');
			return $stmt->execute([
				$email,
				$password
			]);
			
		}


		public function getAll()
		{
			$stmt = self::$pdo->query('SELECT * FROM users');
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		public function login($email,$password)
		{
			$stmt = self::$pdo->prepare('SELECT * FROM users WHERE email = ? AND password = ?');
			
			$stmt->execute([
				$email,
				$password
			]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			if($user)
			{
				$_SESSION['id']=$user['id'];
				$_SESSION['email']=$user['email'];
				$_SESSION['money']=$user['money'];
				$_SESSION['avatar']=$user['avatar'];
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}

	} 

?>