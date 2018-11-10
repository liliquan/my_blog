<?php

namespace libs;

class Log
{
    private $fp;
    // 日志文件名
    public function __construct($fileName)
    {
        // 打开日志文件
        $this->fp = fopen(ROOT . 'logs/' . $fileName. '.log','a');
    }

    // 向日志文件中追加内容
    public function log($content)
    {   
        $date = date('Y-m-d H:i:s');
        $c = $date . "\r\n";
        $c .=  str_repeat('=',120)."\r\n";
        $c .= $content .'\r\n\r\n';
        fwrite($this->fp, $c);

    

    }   
}