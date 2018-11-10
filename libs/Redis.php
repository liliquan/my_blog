<?php
namespace libs;

class Redis
{
    private static $redis = null;

    // 私有的克隆，防止克隆
    private function __clone(){}

    // 私有的构造，防止在类的外部new对象
    private function __construct(){}

    // 唯一对外的公共方法，用来获取唯一的redis对象
    public static function getInstance()
    {
        // 如果还没有redis就生成一个
        if(self::$redis === null)
        {
            self::$redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ]);
        }
        return self::$redis;
    } 
}

?>