<?php

namespace models;
use PDO;

class Blog extends Base
{

    // 取出点赞过这个日志的用户信息
    public function agreeList($id)
    {
        $sql = 'SELECT b.id,b.email,b.avatar
                FROM blog_agrees a
                LEFT JOIN users b ON a.user_id = b.id
                WHERE a.blog_id=?';                   

        $stmt = self::$pdo->prepare($sql);

        $stmt->execute([
            $id
        ]);

        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }

     // 点赞
     public function agree($id)
     {
         // 判断是否点过
         $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM blog_agrees WHERE user_id=? AND blog_id=?');
         $stmt->execute([
             $_SESSION['id'],
             $id
         ]);
         $count = $stmt->fetch( PDO::FETCH_COLUMN );
         if($count == 1)
         {
             return FALSE;
         }
         
         // 点赞
         $stmt = self::$pdo->prepare("INSERT INTO blog_agrees(user_id,blog_id) VALUES(?,?)");
         $ret = $stmt->execute([
             $_SESSION['id'],
             $id
         ]);
 
         // 更新点赞数
         if($ret)
         {
             $stmt = self::$pdo->prepare('UPDATE blogs SET agree_count=agree_count+1 WHERE id=?');
             $stmt->execute([
                 $id
             ]);
         }
 
         return $ret;
     }

    // 删除静态页
    public function deleteHtml($id)
    {
        @unlink(ROOT.'public/contents/'.$id.'.html');
    }

    // 生成静态页
    public function makeHtml($id)
    {
        // 取出日志信息
        $blog = $this->find($id);

        // 打开缓冲区，并且加载视图到缓冲区
        ob_start();

        view('blogs.content',[
            'blog'=>$blog,
        ]);

        // 从缓冲区取出视图并写到静态页面中
        $str = ob_get_clean();
        file_put_contents(ROOT.'public/contents/'.$id.'.html',$str);

    }

    public function update($title,$content,$is_show,$id)
    {
        $stmt = self::$pdo->prepare('UPDATE blogs SET title=?,content=?,is_show=? WHERE id=?');
        $ret = $stmt->execute([
            $title,
            $content,
            $is_show,
            $id,
        ]);
    }

    public function find($id)
    {
        $stmt = self::$pdo->prepare('SELECT * FROM blogs WHERE id = ?');
        $stmt -> execute([
            $id,
        ]);
        return $stmt->fetch();
    }

    public function getNew()
    {
        $stmt = self::$pdo->query('SELECT * FROM blogs WHERE is_show=1 ORDER BY id DESC LIMIT 20');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function delete($id)
    {   
        $stmt = self::$pdo->prepare("DELETE FROM blogs WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $id,
            $_SESSION['id'],
        ]);
    }

    
    public function add($title,$content,$is_show)
    {
        $stmt = self::$pdo->prepare("INSERT INTO blogs(title,content,is_show,user_id) VALUES(?,?,?,?)");
        
        $ret = $stmt->execute([
            $title,
            $content,
            $is_show,
            $_SESSION['id'],
        ]);
       
        if(!$ret)
        {
            echo '失败';
            // 获取失败信息
            $error = $stmt->errorInfo();
            exit; 
        }
        // 返回新插入的记录的ID
        return self::$pdo->lastInsertId();
    }



    public function search()
    {
        
         // 设置的$where
         $where = 'user_id =' .$_SESSION['id'];
 
         // 放预处理对应的值
         $value = [];
 
         // （搜索）如果有keyword并且值不为空
         if(isset($_GET['keyword']) && $_GET['keyword'])
         {
             $where .= " AND (title LIKE ? OR content LIKE ?) ";
             $value[] = '%'.$_GET['keyword'].'%';
             $value[] = '%'.$_GET['keyword'].'%';            
         }
 
         if(isset($_GET['start_date']) && $_GET['start_date'])
         {
             $where .= " AND created_at >= ? ";
             $value[] = $_GET['start_date'];
         }
 
         if(isset($_GET['end_date']) && $_GET['end_date'])
         {
             $where .= " AND created_at <= ? ";
             $value[] = $_GET['end_date'];
         }
 
 
 
         if(isset($_GET['is_show']) && ($_GET['is_show']==1 || $_GET['is_show']==='0'))
         {
             $where .= " AND is_show = ? ";
             $value[] = $_GET['is_show'];
         }
 
         // ******************* 排序 ***********************//
         // 默认的排序方式
         $odby = 'created_at';
         $odway = 'desc';
         // 设置排序字段
         if(@$_GET['odby']== 'display' )
         {
            $odby = 'display';
         }
         // 设置排序方式
         if(@$_GET['odway']== 'asc' )
         {
            $odway = 'asc';
         }
 
         
         // **************  翻页  ******************//
         // 每页条数
         $perpage = 15;
         // 获取当前页码
         $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
         // 计算起始值
         $offset = ($page-1)*$perpage;
 
         $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM blogs WHERE $where");
         $new = $stmt->execute($value);
        
         $count = $stmt->fetch(PDO::FETCH_COLUMN);
 
         // 计算总的记录数
         $pageCount = ceil($count / $perpage);
        
 
         // 制作按钮 
         $btns = '';
         for($i=1;$i<=$pageCount;$i++){
             $params = getUrlParams(['page']);
 
             if($page == $i)
             {
                 $btns .= "<a class='active' href='?{$params}page=$i'> $i </a>";
             }
             else
             {
                 $btns .= "<a href='?{$params}page=$i'> $i </a>";
             }
         }
 
         // ********************执行SQL***************//
         $stmt = self::$pdo->prepare("SELECT * FROM blogs WHERE $where ORDER BY $odby $odway  LIMIT $offset,$perpage");
         // 执行SQL
         $stmt->execute($value);
         // 取数据
         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
         return [
             'btns'=>$btns,
             'data'=>$data
         ];

    }

    public function content2html()
    {

        $stmt = self::$pdo->query('SELECT * FROM blogs');
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        
        foreach($blogs as $v)
        {
            // 加载视图
            view('blogs.content', [
                'blog' => $v,
            ]);
            // 取出缓冲区的内容
            $str = ob_get_contents();
            // 生成静态页
            file_put_contents(ROOT.'public/contents/'.$v['id'].'.html', $str);
            // 清空缓冲区
            ob_clean();
        }
    }

    // ************************  生成首页  *************************
    public function index2html()
    {
        $stmt = self::$pdo->query("SELECT * FROM blogs WHERE is_show=1 ORDER BY id DESC  LIMIT 20");
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        

        ob_start();

        // 加载视图文件
        view('index.index',[
            'blogs'=>$blogs,
        ]);
        
        // 从缓冲区中取出数据
        $str = ob_get_contents();

        // 把页面的内容生成到一个静态页中
        file_put_contents(ROOT.'public/index.html',$str);


    }

    // 从数据库中取出日志的浏览量
    public function getDisplay($id)
    {
        
        // 判断blog_display这个hash中有没有一个键是blog_id
        $key = "blog-{$id}"; 
        

        // 链接Redis
        $redis = \libs\Redis::getInstance();
        
        if($redis->hexists('blog_displays',$key))
        {
            $newNum = $redis->hincrby('blog_displays',$key,1);

            return $newNum;
        }
        else
        {
            $stmt = self::$pdo->prepare('SELECT display FROM blogs WHERE id=?');
            
            $stmt->execute([$id]);
            $display = $stmt->fetch(PDO::FETCH_COLUMN);
            
            // $display = $blog->getDisplay($id);
            $display++;
            $redis->hset('blog_displays',$key,$display);
            return $display;
            
        }
    }


    
    public function displayToDb()
    {
        // 1. 先取出内存中所有的浏览量
        // 连接 Redis
        $redis = \libs\Redis::getInstance();


        $data = $redis->hgetall('blog_displays');

        //  2. 更新回数据库
        foreach($data as $k => $v)
        {
            $id = str_replace('blog-','',$k);
            $sql = "UPDATE blogs SET display={$v} WHERE id = {$id}";
            
            self::$pdo->exec($sql);
        }

    }


}