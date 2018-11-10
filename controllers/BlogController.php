<?php
namespace controllers;
use models\Blog;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BlogController 
{
    
    public function agreements_list()
    {
        $id = $_GET['id'];

        // 获取这个日志所有点赞的用户
        $model = new \models\Blog;
        $data = $model->agreeList($id);

        // 转成 JSON 返回 
        echo json_encode([
            'status_code' => 200,
            'data' => $data,
        ]);

    }

    // 点赞
    public function aggrements()
    {
        $id = $_GET['id'];
        // 判断登录
        if(!isset($_SESSION['id']))
        {
            echo json_encode([
                'status_code' => '403',
                'message' => '必须先登录'
            ]);
            exit;
        }

        // 点赞
        $model = new \models\Blog;
        $ret = $model->agree($id);
        if($ret)
        {
            echo json_encode([
                'status_code' => '200',
            ]);
            exit;
        }
        else
        {
            echo json_encode([
                'status_code' => '403',
                'message' => '已经点赞过了'
            ]);
            exit;
        }
    }

    public function makeExcel()
    {
        // 获取当前标签页
        $spreadsheet = new Spreadsheet();
        // 获取当前工作
        $sheet = $spreadsheet->getActiveSheet();

        // 设置第1行内容
        $sheet->setCellValue('A1', '标题');
        $sheet->setCellValue('B1', '内容');
        $sheet->setCellValue('C1', '发表时间');
        $sheet->setCellValue('D1', '是发公开');

        // 取出数据库中日志
        $model = new \models\Blog;
        // 获取最新的20个日志
        $blog = $model->getNew();
        $i = 2; 
        foreach($blog as $v)
        {
            $sheet->setCellValue('A'.$i, $v['title']);
            $sheet->setCellValue('B'.$i, $v['content']);
            $sheet->setCellValue('C'.$i, $v['created_at']);
            $sheet->setCellValue('D'.$i, $v['is_show']);
            $i++;
        }
        $date = date('Ymd');
        // 生成excel文件
        $writer = new Xlsx($spreadsheet);
        $writer->save(ROOT . 'excel/'.$date.'.xlsx');

        // 下载文件路径
        $file =ROOT . 'excel/'.$date.'.xlsx';
        // 下载时文件名 
        $fileName = '最新的20条日志-'.$date.'.xlsx';

        // 下载   调用header函数  函数设置协议头，告诉浏览器下载文件
        //告诉浏览器这是一个二进制文件流格式的文件    
        Header ( "Content-type: application/octet-stream" ); 
        //请求范围的度量单位  
        Header ( "Accept-Ranges: bytes" );  
        //Content-Length是指定包含于请求或响应中数据的字节长度    
        Header ( "Accept-Length: " . filesize ( $file ) );  
        //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
        Header ( "Content-Disposition: attachment; filename=" . $fileName ); 


        // 读取服务器上的一个文件，并且以文件流的形式并输出给浏览器
        readfile($file);
                
        
    }

    public function content()
    {
        // 接收id以后，然后取出日志信息
        $id = $_GET['id'];
        $model = new Blog;
        $blog = $model->find($id);

        // 判断这个日志是不是我的日志
        if($_SESSION['id'] != $blog['user_id'])
            die('无权访问！');

        // 加载视图
        view('blogs.content',[
            'blog'=>$blog,
        ]);
    }

    public function update()
    {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $is_show = $_POST['is_show'];
        $id = $_POST['id'];

        $blog = new Blog();
        $blog->update($title,$content,$is_show,$id);

        if($is_show == 1)
        {
            $blog->makeHtml($id);
        }
        else
        {
            $blog->deleteHtml($id);
        }

        message('修改成功！',2,'/blog/index');
    }

    public function edit()
    {
        $id = $_GET['id'];
        $blog = new Blog;
        $data = $blog->find($id);

        view('blogs.edit',[
            'data'=>$data,
        ]);
    }

    public function delete()
    {
        $id = $_POST['id'];
        $blog = new Blog;
        $blog->delete($id);

        message('删除成功',2,'/blog/index');
    }

    public function store()
    {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $is_show = $_POST['is_show'];

        $blog = new Blog;
        $id = $blog->add($title,$content,$is_show);

        if($is_show==1)
        {
            $blog->makeHtml($id);
        }

        // 跳转
        message('发表成功', 2, '/blog/index');
    }

    public function create()
    {
        view('blogs.create');
    }

    public function index()
    {

       $blog = new Blog;
       $data = $blog->search();


        // ******************** 加载视图 ***************//
        view('blogs.index',$data);

    }

    // 为所有的日志生成静态的详情页
    public function content_to_html()
    {   
        $blog = new Blog;
        
        $blog->content2html();

    }



    public function index2html()
    {
        $blog = new Blog;
        $blog->index2html();
    }

    public function update_display()
    {
        
        // 接收id
        $id = (int)$_GET['id'];

        $blog = new Blog;

        echo $blog->getDisplay($id);

    }

    public function display()
    {
        
        $id = (int)$_GET['id'];
        
        $blog = new Blog;

        // 把浏览量+1.并且输出(如果内存中没有就操作数据库，如果内存中有就直接操作数据库)
        $display = $blog->getDisplay($id);

        // 返回多个数据时必须要用json
        echo json_encode([
            'display' => $display,
            'email' => isset($_SESSION['email']) ? $_SESSION['email'] : ''
        ]);
    }

    public function displayToDb()
    {
        $blog = new Blog;
        $blog->displayToDb();
    }


    

}


