<?php 
/**
* 数据库配置
*/
include __DIR__.'/db_config.php';
/**
* 启动数据库连接
*/
include __DIR__.'/../inc/db/boot.php';


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
//返回一条记录,*是字段，如果指定字段，速度最快
// 多个字段是数组
//$res = db_get_one("products","*",$where);
//所有记录
//$res = db_get("products","*",$where);
//分页
//$res  = db_pager("products","*",$where);
//使用原生 https://medoo.in/api/where 方法 
//$res = db()->select("products",['id'],[]); 
$res  = db_get("qr_rule","qr_num",['GROUP'=>'qr_num']);
print_r($res);



