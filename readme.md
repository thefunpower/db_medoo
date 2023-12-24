# 数据库操作

对 `Medoo Version: 2.1.10` 再封装，让操作更简单。

~~~
composer require thefunpower/db_medoo
~~~

配置

~~~
/**
* 数据库连接
*/
$medoo_db_config['db_name'] = 'dentalbest_erp'; 
$medoo_db_config['db_host'] = '127.0.0.1';
$medoo_db_config['db_user'] = 'root';
$medoo_db_config['db_pwd']  = '111111';
$medoo_db_config['db_port'] = 3306; 


include __DIR__.'/vendor/thefunpower/db_medoo/boot.php';

~~~

读库
~~~
// 从库读库 
$medoo_db_config['read_db_host'] = '127.0.0.1';
$medoo_db_config['read_db_name'] = ['read2','read1'];
$medoo_db_config['read_db_user'] = 'root';
$medoo_db_config['read_db_pwd'] = '111111';
$medoo_db_config['read_db_port'] = 3306; 

~~~

在使用只读库时
~~~
db_active_read();
~~~

切回默认数据库
~~~
db_active_default();
~~~

## 行锁
需要放在事务中
~~~
db_for_update($table,$id);
~~~

## 加+
~~~
"age[+]" => 1
~~~


## $where条件

~~~
'user_name[REGEXP]' => '[a-z0-9]*'
'user_name[FIND_IN_SET]'=>(string)10
'user_name[RAW]' => '[a-z0-9]*'
~~~

~~~
$where = [
	//like查寻
	'product_num[~]' => 379, 
	//等于查寻
	'product_num' => 3669, 
	//大于查寻
	'id[>]' => 1,
	'id[>=]' => 1,
	'id[<]' => 1,
	'id[<=]' => 1,
]; 
$where = []; 
$where['OR'] = [
	'product_num[~]'=>379,
	'product_num[>]'=>366,
];
$where['LIMIT'] = 10;
$where['ORDER'] = ['id'=>'DESC']; 
~~~

# OR
~~~
//(...  AND ...) OR (...  AND ...)
"OR #1" => [
    "AND #2" => $where,
    "AND #3" => $or_where
    ]
];
//(... OR  ...) AND (...  OR ...)
"AND #1" => [
    "OR #2" => $where,
    "OR #3" => $or_where
    ]
];
~~~

## where字段两个日期之间

字段是datetime类型  
~~~
$date1 = '2022-11-01';
$date2 = '2022-12-14';
db_between_date($field,$date1,$date2)
~~~

## where 两个月份之间

~~~
$date1 = '2022-11';
$date2 = '2022-12';
db_between_month($field,$date1,$date2
~~~

## 查寻一条记录

~~~
$res = db_get_one("products","*",$where);
$res = db_get_one("products",$where);
~~~

## 所有记录

~~~
$res = db_get("products","*",$where);
$res = db_get("products",$where);
~~~

## 分页

~~~
$res  = db_pager("products","*",$where);
~~~

## 使用原生方法 

原生方法将不会触发`action`

https://medoo.in/api/where

~~~
$res = db()->select("products",['id'],[]); 
~~~ 

## 查寻某个字段

~~~
$res  = db_get("qr_rule","qr_num",['GROUP'=>'qr_num']);
print_r($res); 
~~~

## 写入记录

~~~
db_insert($table, $data = [],$don_run_action = false)
~~~

## 更新记录

~~~
db_update($table, $data = [], $where = [],$don_run_action = false)
~~~

## 取最小值

~~~
db_get_min($table, $join  = "*", $column = null, $where = null)
~~~

其他一些如取最大值等

~~~
db_get_max
db_get_count
db_get_has
db_get_rand
db_get_sum
db_get_avg 
~~~

## 删除 

~~~
db_del($table, $where)
~~~


##  action 

### 写入记录前

~~~
do_action("db_insert.$table.before", $data);
do_action("db_save.$table.before", $data);
~~~

### 写入记录后

其中`$data`有 `id` 及 `data`

~~~
do_action("db_insert.$table.after", $action_data);
do_action("db_save.$table.after", $action_data);
~~~

### 更新记录前

~~~
do_action("db_update.$table.before", $data);
do_action("db_save.$table.before", $data);
~~~

### 更新记录后

其中`$data`有 `id`   `data` `where`
~~~
do_action("db_update.$table.after", $action_data);
do_action("db_save.$table.after", $action_data); 
~~~

~~~
do_action("db_get_one.$table", $v); 
~~~

## 删除前

~~~
do_action("db_insert.$table.del", $where);
~~~


## 显示所有表名

~~~
show_tables($table)
~~~

## 取表中字段

~~~
get_table_fields($table, $has_key  = true)
~~~

## 返回数据库允许的数据，传入其他字段自动忽略

~~~
db_allow($table, $data)
~~~

## 显示数据库表结构，支持markdown格式

~~~
database_tables($name = null, $show_markdown = false)
~~~

## 数组排序

~~~
array_order_by($row,$order,SORT_DESC);
~~~

## 判断是json数据

~~~
is_json($data)
~~~


## SQL查寻

~~~
db_query($sql, $raw = null)
do_action("db_query", $all) 
~~~

其中`$sql`为`select * from table_name where user_id=:user_id`

`$raw` 为 `[':user_id'=>1]`




## 事务

需要`inner db`支持

~~~
db_action(function()use($data)){

});
~~~

## id锁

~~~
db_for_update($table,$id)
~~~

## 设置分页总记录数

~~~
db_pager_count($nums = null)
~~~ 

## 连表查寻

~~~
$data = db_pager("do_order",
["[><]do_mini_user" => ["uid" => "id"]],
[
    "do_order.id",
    "do_order.uid",
    "user" => [
        "do_mini_user.nickName",
        "do_mini_user.avatarUrl",
        "do_mini_user.openid",
    ]
],
$where);
~~~

## db_get复杂查寻

~~~
$lists = db_get('do_order', [ 
    'count' => 'COUNT(`id`)',
    'total' => 'SUM(`total_fee`)',
    'date'  => "FROM_UNIXTIME(`inserttime`, '%Y-%m-%d')"
], 
~~~

## field 排序
~~~
'ORDER'=>['id'=>[1,2]]
~~~

## 跨库数据库事务
调用方式
~~~
xa_db_action([
  'a'=>function(){
    echo "a<br>";
    db_insert("config",['title'=>1]);
  },
  'b'=>function(){
    echo "b<br>";
    db_insert("config",['title'=>'b']);
    //抛出异常时也会回滚
    //throw new Exception("错误");
  }
]);
~~~

其中 `a` `b`是数据库连接

配置数据库

~~~
new_db([
  'db_host'=>"127.0.0.1",
  'db_name'=>"test1",
  'db_user'=>"root",
  'db_pwd'=>"111111",
  'db_port'=>"3306",
],'a');

new_db([
  'db_host'=>"127.0.0.1",
  'db_name'=>"test2",
  'db_user'=>"root",
  'db_pwd'=>"111111",
  'db_port'=>"3306",
],'b'); 
~~~

## 修改表名
~~~ 
add_action("db_table.a",function(&$table){
    $table = 'a_100';
});
~~~
 
###  创建分区表,自动排除已有的

~~~ 
db_struct_table_range_auto('wordpress','my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
    '2024-02',
    '2024-03',
]);
~~~ 

返回创建分区SQL   

~~~
db_struct_table_range('my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
],'created_at','p',true);
~~~

## 使用model

验证规则 

https://github.com/vlucas/valitron

~~~
<?php   
 
namespace model; 

class user extends \model{ 
    protected $table = 'users';

    protected $field = [
        'name'  => '姓名',
        'phone' => '手机号',
        'email' => '邮件',
    ];

    protected $validate = [
        'required'=>[
            'name','phone','email',
        ],
        'email'=>[
            ['email'],
        ],
        'phonech'=>[
            ['phone']
        ],
        'unique'=>[
            ['phone',],
            ['email',], 
        ]
    ]; 

    protected $unique_message = [
        '手机号已存在',
        '邮件已存在',
    ];
    

    /**
    * 写入数据前
    */
    public function before_insert(&$data){ 
        parent::before_insert($data);
        $data['created_at'] = now();
        parent::before_insert($data);
    }
}
~~~



model事件，注意使用`parent::`

~~~
    /**
    * 查寻前
    */
    public function before_find(&$where){
    }
    /**
    * 查寻后
    */
    public function after_find(&$data){
    }
    
    /**
    * 写入数据前
    */
    public function before_insert(&$data){
    }
    /**
    * 写入数据后
    */
    public function after_insert($id){
    }
    
    /**
    * 更新数据前
    */
    public function before_update(&$data,$where){
    }
    /**
    * 更新数据后
    */
    public function after_update($row_count,$data,$where){
    }
    /**
    * 删除前
    */
    public function before_del(&$where)
    {        
    }
    /**
    * 删除后
    */
    public function after_del($row_count,$where)
    {        
    }
~~~

字段映射

~~~
protected $field_ln = [
    'title' => 'name', 
];
~~~

`name`是数据库中真实存在的字段,`title`是自己定义了字段。

~~~
$model->find(['title[~]'=>'test']);
~~~

等同于
~~~
$model->find(['name[~]'=>'test']);
~~~

返回的记录中将同时有`name` `title`

model查询

~~~
$model->find($id) //返回一条记录 $id是int类型
$model->find(['name'=>'t'],$limit=1)  //返回一条记录
$model->find(['name'=>'t'])  //返回所有记录
~~~

insert

~~~
$model->insert($data)
~~~

update
~~~
$model->update($data,$where = '')
~~~

pager
~~~
$model->pager($join, $columns = null, $where = null)
~~~

sum

~~~
$model->sum($filed,$where = '')
~~~

count
~~~
$model->count($where = '')
~~~

delete
~~~
$model->del($where = '')
~~~

max
~~~
$model->max($filed,$where = '')
~~~

min 
~~~
$model->min($filed,$where = '')
~~~

## License

[Apache License 2.0](LICENSE)


