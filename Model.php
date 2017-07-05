<?php
//定义命名空间
//我们用命名空间来将代码以空间的形式分类，在不同空间我们可以起相同的名字，而且我们再用composer来自动载入类时也是通过命名空间来区分相同文件来载入的
namespace houdunwang\model;
class Model{
    //声明配置项将配置项存入属性中
    private static $config;
    //当调用无法访问的方法时会自动触发的方法
    public function __call($name, $arguments){
        //当对象调用无法访问的方法时会获取到它的方法名，获取到之后我们跳转到Base类里面去找这个方法
        return self::parseAction($name,$arguments);
    }
    //当静态调用无法访问的方法时会自动触发的方法
    public static function __callStatic($name, $arguments){
        //当静态调用无法访问的方法时会获取到它的方法名，获取到之后我们跳转到Base类里面去找这个方法
        return self::parseAction($name,$arguments);
    }
    private static function parseAction($name,$arguments){
        //获取到当前类名(也就是表名)
        $table = get_called_class();
        //截取表名
        $table = strtolower(ltrim(strrchr($table,'\\'),'\\'));
        //实例化对象，当将要寻找的方法名传入$name 实例化base寻找这个方法
            return call_user_func_array([new Base(self::$config,$table),$name],$arguments);

    }
    //配置项方法
    public static function setConfig($config){
        //调用配置项
        self::$config = $config;
    }
}