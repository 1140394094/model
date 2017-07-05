<?php
//我们用命名空间来将代码以空间的形式分类，在不同空间我们可以起相同的名字，而且我们再用composer来自动载入类时也是通过命名空间来区分相同文件来载入的
namespace houdunwang\model;
//载入类名
//因为我们在调用无效的方法名时要触发View类里面的自动加载方法，虽然我们通过composer在动载入了我们需要的类，但是因为命名空间的存在我们不能调用不同空间的方法，所以我们要载入这个类名，来使用它里面的方法，这里需要用到全局的PDO所以在这里载入一下
use PDOException;
use PDO;
//链接数据库操作
//实例化base就可以直接链接数据库了
class Base{
    //声明经常要用到的变量为属性
    public static $pdo = NULL;
    //因为表名要在多个方法里面调用所以声明为属性
    private $table;
    //where属性是sql语句中的条件，给默认值意为可传可不传参
    private $where = '';
    //创建自动加载方法
    //在实例化Base的时候就会执行里面的获取表名和链接数据库了
    public function __construct($config,$table){
        //链接数据库
        $this->connect($config);
        //调用属性中的表名
        $this->table = $table;
    }
    //链接数据库
    private function connect($config){
        //判断有没有连接过数据库，如果连接过数据库了就不需要重复连接了
        if (!is_null(self::$pdo)) return;
        try{
            //获取配置项数组里的地址和数据库名
            $dsn = "mysql:host=" . $config['db_host'] . ";dbname=" . $config['db_name'];
//            p($dsn);
            //获取配置项数组里的用户名
            $user = $config['db_user'];
            //获取配置项数组里的密码
            $password = $config['db_password'];
            //链接数据库，返回的是对象
            $pdo = new PDO($dsn,$user,$password);
            //设置错误提示
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            //设置字符集
            $pdo->query("SET NAMES " . $config['db_charset']);
            //将$pdo存入到静态属性中
            //因为我们要判断$pdo有没有连接过数据库，将它存入静态属性中会被保存住
            self::$pdo = $pdo;
//            var_dump(self::$pdo);
            //如果发生异常错误时会被catch捕捉到
        }catch (PDOException $e){
            //输出错误并并停止下面代码运行
            exit($e->getMessage());
        }
    }
    //发送sql，获取所有数据
    public function q($sql){
        try{
            //调用pdo属性并发送sql
            $result = self::$pdo->query($sql);
            //获取全部数据
            $data = $result->fetchAll(PDO::FETCH_ASSOC);
//            p($data);
            //将获取到的数据返回出去
            return $data;
            //捕捉异常错误
        }catch (PDOException $e){
            //将捕捉到的异常错误输出并停止下面代码运行
            exit($e->getMessage());
        }
    }
    //获取数据
    public function get(){
        //编辑sql
        //编辑查询请求，可以选择查询全部数据或者加上where查询条件数据
        $sql = "SELECT * FROM {$this->table} {$this->where}";
        //调用q方法，发送编辑请求sql，获取查询的数据
        return $this->q($sql);
    }
    //选择where条件
    public function where($where){
        //编辑sql where条件
        $this->where = "WHERE {$where}";
        //因为调用的时候可能需要链式调用，所以要返回一个对象
        return $this;
    }
    //查询操作，返回的是对象数据
    public function find($pri){
        //获得主键
        $priField = $this->getPri();
        //编辑sql  where条件
        $this->where("{$priField}={$pri}");
        //编辑sql语句 查询所有数据
        $sql = "SELECT * FROM {$this->table} {$this->where}";
        //获取表中所有数据
        $data = $this->q($sql);
        //把原来的数据数组变为一维数组
        $data = current($data);
        //将处理完的一维数组赋值给属性
        $this->data = $data;
        //将当前对象返回出去
        //因为我们要链式连接的时候需要前面是对象，所以这里返回当前对象
        return $this;
    }
    //查找操作，返回的是对象数据里的数组
    public function findArray($pri){
        //调用find查找方法，将里面的条件数组拿出来
        $obj = $this->find($pri);
        //将拿出来的数组返回出去
        return $obj->data;
    }
    public function toArray(){
        return $this->data;
    }
    //获取表的主键方法
    public function getPri(){
        //查看表结构
        $desc = $this->q("DESC {$this->table}");
       //打印查看表结构
//        p($desc);
        //遍历循环表结构这个数组，获取我们要的主键
        foreach ($desc as $v){
            //判断如果Key的值为PRI时就证明这个数组里面有主键
            if ($v['Key'] == 'PRI'){
                //将主键赋值给$priField
                $priField = $v['Field'];
                //当获取到主键后跳出循环
                break;
            }
        }
        //打印查看获取的主键是否正确
//        p($priField);
        //将获得的主键返回出去
        return $priField;
    }
    //统计表中数据
    public function count($field='*'){
        //统计表中数据
        $sql = "SELECT count({$field}) as c FROM {$this->table} {$this->where}";
        //将获取到的数据存入$data
        $data = $this->q($sql);
        //将获得的数据中统计出来的数据返回出去
        return $data[0]['c'];
    }
    //执行无结果集操作，比如增删改
    public function e($sql,$param){
        try{
            //预准备操作
            //将编写好的sql请求发送数据库
            $s = self::$pdo->prepare($sql);
            //将第二次传入的值绑定到数据库表中
            $s->execute($param);
            //接收异常错误
        }catch (PDOException $e){
            //当发生异常错误后会被接收到这里输出出来
            exit($e->getMessage());
        }
    }

    public function c($sql){
        return self::$pdo->exec($sql);
    }













}