<?php 
/*
    Copyright (c) 2021-2031, All rights reserved.
    This is  a library, use is under MIT license.
*/
/**
//saas系统管理平台数据库
$config['main_db_host'] = '127.0.0.1';
$config['main_db_name'] = 'main';
$config['main_db_user'] = 'root';
$config['main_db_pwd'] = '111111';
$config['main_db_port'] = 3306; 

// 从库读库 
$config['read_db_host'] = '127.0.0.1';
$config['read_db_name'] = ['read2','read1'];
$config['read_db_user'] = 'root';
$config['read_db_pwd'] = '111111';
$config['read_db_port'] = 3306; 

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
//连接默认数据库  
if($config['db_dsn'] && $config['db_user'] && $config['db_pwd']){
    try {
        $pdo = new PDO($config['db_dsn'], $config['db_user'], $config['db_pwd']);
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
if($config['main_db_host']
    && $config['main_db_name']
    && $config['main_db_user']
    && $config['main_db_pwd']

    ){
    $config['main_db_port'] = $config['main_db_port']?:3306; 
    $main_db_config = [
        'db_host' => $config['main_db_host'],
        'db_name' => $config['main_db_name'],
        'db_user' => $config['main_db_user'],
        'db_pwd' => $config['main_db_pwd'],
        'db_port' => $config['main_db_port'],
    ];
    new_db($main_db_config,'main');
}

/**
* 连接读从库
*/

if($config['read_db_host']
    && $config['read_db_name']
    && $config['read_db_user']
    && $config['read_db_pwd']

    ){
    $config['read_db_port'] = $config['read_db_port']?:3306; 
	$read_db_name = $config['read_db_name'];
	if(is_array($read_db_name)){
		$read_db_name = $read_db_name[array_rand($read_db_name)];
	} 
    $read_db_config = [
        'db_host' => $config['read_db_host'],
        'db_name' => $read_db_name,
        'db_user' => $config['read_db_user'],
        'db_pwd' => $config['read_db_pwd'],
        'db_port' => $config['read_db_port'],
    ];
    new_db($read_db_config,'read');
}
