<?php
namespace controllers;
use models\Blog;

class IndexController
{
    public function index()
    {   

        // 取出最新的日志
        $blog = new \models\Blog;
        $blogs = $blog->getNew();

        // 取出活跃用户
        $user = new \models\User;
        $users = $user->getActiveUsers();

        // 显示页面
        view('index.index',[
            'blogs'=>$blogs,
            'users'=>$users,
        ]);

    }
}