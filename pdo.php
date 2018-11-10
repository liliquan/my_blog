<?php

$host = '127.0.0.1';
$dbname = 'blog';
$user = 'root';
$pass = '';

// 连接数据库
$pdo = new PDO("mysql:host={$host};dbname={$dbname}",$user,$pass);

// 设置编码
$pdo->exec('SET NAMES utf8');

$stmt = $pdo -> prepare("INSERT INTO blogs (title,content) VALUES(?,?)");
$ret = $stmt->execute([
    '标题！！',
    '内容！！',
]);

if($ret)
{
    echo "添加成功新纪录的id".$pdo->lastInsertId();
}
else
{
    $error = $stmt->errorInfo();
    var_dump($error);
}


// ****************query*****************//
// 取出前面十条数据
// $stmt = $pdo->query("SELECT * FROM blogs LIMIT 10");

// 获取一条数据，返回的是一维数组
// $data = $stmt->fetch();

// 获取所有数据，返回二维数组
// $data = $stmt->fetchAll();
// var_dump($data);




// ***********************************  exec ******//
// 随机插入100条数据
// for($i=0;$i<100;$i++)
// {
//     $title = getChar(rand(20,50));
    
//     $content = getChar(rand(50,100));
    
//     $pdo->exec("INSERT INTO blogs (title,content) VALUES('$title','$content')");
   
// }

// function getChar($num)  // $num为生成汉字的数量
//     {
//         $b = '';
//         for ($i=0; $i<$num; $i++) {
//             // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
//             $a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
//             // 转码
//             $b .= iconv('GB2312', 'UTF-8', $a);
//         }
//         return $b;
//     }

// 插入数据
// $pdo->exec("INSERT INTO blogs(id,title,content) VALUES(null,'标题001','内容001')");

// 修改
// $pdo->exec("UPDATE blogs SET title='标题1',content='内容1' where id = 2");


// 删除
// 删除id为4的一条数据
// $pdo->exec("DELETE FROM blogs WHERE id = 4");
// $pdo->exec("DELETE FROM blogs");

// 清空表,并且充值id
// $pdo->exec('TRUNCATE blogs');

// 