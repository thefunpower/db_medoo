<?php 
/*
    Copyright (c) 2021-2031, All rights reserved.
    This is  a library, use is under MIT license.
*/
/**
* 以 CodeIgniter 为例
*/
include APPPATH.'config/database.php';
$dbconfig = $db['default'];
$medoo_db_config['db_dsn'] = $dbconfig['dsn'];
$medoo_db_config['db_user'] = $dbconfig['username'];
$medoo_db_config['db_pwd'] = $dbconfig['password'];

/*
include APPPATH.'db_connect.php';  
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
global $_db,$_db_active,$_db_connects; 
//连接默认数据库  
if($medoo_db_config['db_dsn'] && $medoo_db_config['db_user'] && $medoo_db_config['db_pwd']){ 
    try {
        $pdo = new PDO($medoo_db_config['db_dsn'], $medoo_db_config['db_user'], $medoo_db_config['db_pwd']);
        $_db = new Medoo\Medoo([
            'pdo'     => $pdo,
            'type'    => 'mysql',
            'option'  => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ],
            'command' => [
                'SET SQL_MODE=ANSI_QUOTES'
            ],
            'error' => PDO::ERRMODE_WARNING
        ]); 
        $_db_connects['default'] = $_db;
        $_db_active  = 'default';
    } catch (Exception $e) {
        $err = $e->getMessage();
        if(DEBUG){
            pr($err);exit;
        }
        $error = lang('MySql Connect Failed');
        echo "<div style='color:#fff;background:red;padding:10px;width:600px;margin:auto;'>".$error."</div>
        <style>
        html,body{
            background:#eee;
        }
        </style>
        ";exit;
    }
}

/**
 * 连接平台数据库
 */
if(isset($medoo_db_config['main_db_host'])
    && $medoo_db_config['main_db_name']
    && $medoo_db_config['main_db_user']
    && $medoo_db_config['main_db_pwd']

    ){
    $medoo_db_config['main_db_port'] = $medoo_db_config['main_db_port']?:3306; 
    $main_db_config = [
        'db_host' => $medoo_db_config['main_db_host'],
        'db_name' => $medoo_db_config['main_db_name'],
        'db_user' => $medoo_db_config['main_db_user'],
        'db_pwd' => $medoo_db_config['main_db_pwd'],
        'db_port' => $medoo_db_config['main_db_port'],
    ];
    new_db($main_db_config,'main');
}

/**
* 连接读从库
*/

if(isset($medoo_db_config['read_db_host'])
    && $medoo_db_config['read_db_name']
    && $medoo_db_config['read_db_user']
    && $medoo_db_config['read_db_pwd']

    ){
    $medoo_db_config['read_db_port'] = $medoo_db_config['read_db_port']?:3306; 
	$read_db_name = $medoo_db_config['read_db_name'];
	if(is_array($read_db_name)){
		$read_db_name = $read_db_name[array_rand($read_db_name)];
	} 
    $read_db_config = [
        'db_host' => $medoo_db_config['read_db_host'],
        'db_name' => $read_db_name,
        'db_user' => $medoo_db_config['read_db_user'],
        'db_pwd' => $medoo_db_config['read_db_pwd'],
        'db_port' => $medoo_db_config['read_db_port'],
    ];
    new_db($read_db_config,'read');
}
