<?php 
namespace libs;

// 在项目中无论使用多少次这个类，只需要一个对象，如果多了就是浪费内存
// 所以我们需要使用单例模式
// 单例模式：三私一公  （无论new多少个都只有一个对象）

// 构造函数是私有的 ：不能让其他的 new 对象了  
// 提供一个公开的方法来获取对象
// 不允许被克隆
// 保存唯一对象（只有静态的属性属于这个类是唯一的） 
class Uploader
{

    private function __construct(){}
    private function __clone(){}
    private static $_obj = null; 

    public static function make()
    {
        if(self::$_obj==null)
        {
            // 生成一个对象
            self::$_obj = new self;
        }
        return self::$_obj;
    }



    // 保存图片的一级目录
    private $_root = ROOT.'public/uploads/';
    // 允许上传的扩展名
    private $_ext = ['image/jpeg','image/jpg','image/ejpeg','image/png','image/gif','image/bmp'];
    private $_maxSize = 1024*1024*1.8;  //最大允许上传的尺寸为1.8M
    // 保存用户上传的图片信息
    private $_file;
    // 二级目录
    private $_subDir;

    // 上传图片
    // 参数一。表单中文件名
    // 参数二。保存到的二级目录
    public function upload($name,$subdir)
    {
        // 把用户图片信息保存到属性上
        $this->_file = $_FILES[$name];
        $this->_subDir = $subdir;

        if(!$this->_checkType())
        {
            die('图片类型不正确！');
        }

        if(!$this->_checkSize())
        {
            die('图片尺寸不正确！');
        }

        // 创建目录
        $dir = $this->_makeDir();
        // 生成唯一的名字
        $name = $this->_makeName();
        // 移动图片
        move_uploaded_file($this->_file['tmp_name'], $this->_root.$dir.$name);

        // 返回二级目录开始的路径
        return $dir.$name;
    }

    // 创建目录
    private function _makeDir()
    {
        // 获取今天的日期
        $dir = $this->_subDir . '/' . date('Ymd');
        if(!is_dir($this->_root.$dir))
            mkdir($this->_root.$dir,0777,TRUE);  
        
            return $dir.'/';
    }

    // 生成唯一的名字
    private function _makeName()
    {
        $name = md5( time() . rand(1,99999));
        // 获取文件的后缀
        // strrchr 从最后某个字符开始截取到最后
        $ext = strrchr($this->_file['name'],'.');
        // 名字加上扩展名
        return $name.$ext;
    }

    private function _checkType()
    {
        return in_array($this->_file['type'], $this->_ext);
    }

    private function _checkSize()
    {
        return $this->_file['size'] < $this->_maxSize;
    }
}
 

?>