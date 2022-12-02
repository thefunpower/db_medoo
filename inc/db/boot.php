<?php 
include __DIR__.'/Medoo.php';
include __DIR__.'/function.php';
global $_db,$_db_active,$_db_connects; 
//连接默认数据库  
if($medoo_db_config['db_user'] && $medoo_db_config['db_pwd']){ 
    if(!isset($medoo_db_config['db_dsn'])){
        $medoo_db_config['db_dsn'] = "mysql:dbname={$medoo_db_config['db_name']};host={$medoo_db_config['db_host']};port={$medoo_db_config['db_port']}";
    }
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
