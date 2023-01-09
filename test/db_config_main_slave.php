<?php 
/**
* 主从配置 ，SAAS平台数据库配置
*/
/**
* 数据库连接
*/
$medoo_db_config['db_name'] = 'o2o'; 
$medoo_db_config['db_user'] = 'root';
$medoo_db_config['db_pwd'] = '111111';
$medoo_db_config['db_port'] = 3306; 




/* 
$res = db_pager("products",'*',['LIMIT'=>2]);
echo db_pager_html();
print_r($res);
*/
 
/**
//saas系统管理平台数据库
$medoo_db_config['main_db_host'] = '127.0.0.1';
$medoo_db_config['main_db_name'] = 'main';
$medoo_db_config['main_db_user'] = 'root';
$medoo_db_config['main_db_pwd'] = '111111';
$medoo_db_config['main_db_port'] = 3306; 

// 从库读库 
$medoo_db_config['read_db_host'] = '127.0.0.1';
$medoo_db_config['read_db_name'] = ['read2','read1'];
$medoo_db_config['read_db_user'] = 'root';
$medoo_db_config['read_db_pwd'] = '111111';
$medoo_db_config['read_db_port'] = 3306; 

db_active_main();
可以调用 db_get等方法

db_active_default();

db_active_read();
可以调用 db_get等方法

db_active_default();

测试
db_active_main(); 
$r = db_get('a','*',[]);
pr($r);
db_active_default();

db_active_read(); 
//db_insert('a',['title'=>'read'.time()]);
$r = db_get('a','*',[]);
pr($r);
db_active_default();


 */

