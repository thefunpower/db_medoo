<?php
/**
*  对数据库操作的封装
*  https://medoo.in/api/where
*/
/**
* 数据库对象
* 建议使用 medoo_db()
*/
global $_db,$_db_active,$_db_connects;
$_db_active  = 'default';
/**
* 数据参数用于分页后生成分页HTML代码
*/
global $_db_par;
/**
* 错误信息
*/
global $_db_error;
/**
* 当前激活数据库名
*/
global $_db_active_lock_name;
/**
* 表前缀
*/
global $db_prefix;
/**
 * 激活平台数据库连接，平台数据库不支持从库读
 */
function db_active_main()
{
    db_active('main');
}
/**
 * 激活读数据库连接
 */
function db_active_read()
{
    db_active('read');
}
/**
 * 激活默认数据库连接
 */
function db_active_default()
{
    db_active('default');
}
/**
* 表前缀
*/
function db_prefix($prefix = null)
{
    global $db_prefix;
    if($prefix === null) {
        return $db_prefix;
    }
    $db_prefix = $prefix;
}
/**
* 表名
*/
function get_db_table_name($table)
{
    global $db_prefix;
    $table = $db_prefix . $table;
    if(function_exists('do_action')) {
        do_action("db.table", $table);
    }
    return $table;
}

/**
 * 激活当前使用哪个数据库
 */
function db_active($name = 'default', $need_rollback_here = false)
{
    global $_db_active;
    $_db_active  = $name;
    global $_db_active_lock_name;
    if($need_rollback_here) {
        $_db_active_lock_name = $name;
    }
}

/**
* 回到上一个连接
*/
function db_active_rollback()
{
    global $_db_active_lock_name;
    if($_db_active_lock_name) {
        db_active($_db_active_lock_name);
    }
}

/**
* 获取当前启用的数据库连接
*/
function get_db_active_name()
{
    global $_db_active;
    return $_db_active;
}
/**
* 判断是否可运行action
*/
function db_can_run_action()
{
    $name = get_db_active_name();
    //数据库连接平台时是不能使用action的
    if($name == 'main') {
        return false;
    }
    return true;
}
/**
* 数据库是否可执行更新操作
*/
function db_can_run_update($sql = '')
{
    $name = get_db_active_name();
    if($name == 'read') {
        if($sql) {
            if(strpos(strtoupper($sql), 'UPDATE') !== false) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
    return true;
}

/**
 * 连接数据库
 */
function new_db($config = [], $name = '')
{
    global $_db_connects;
    $_db = new \Medoo\Medoo([
        'type' => 'mysql',
        'host' => $config['db_host'],
        'database' => $config['db_name'],
        'username' => $config['db_user'],
        'password' => $config['db_pwd'],
        // [optional]
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'port'      => $config['db_port'],
        'prefix'    => '',
        'error'     => PDO::ERRMODE_SILENT,
        // Read more from http://www.php.net/manual/en/pdo.setattribute.php.
        'option'    => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ],
        'command' => [
            'SET SQL_MODE=ANSI_QUOTES'
        ]
    ]);
    if($name) {
        $_db_connects[$name] = $_db;
    }
    return $_db;
}
/**
 * 数据库实例
 *
 * @return object
 */
function medoo_db()
{
    global $_db_connects,$_db_active;
    $db_connect =  $_db_connects[$_db_active];
    $err = '数据库链接异常，如有疑问请联系管理员。';
    if(!$db_connect) {
        if(function_exists('do_action')) {
            do_action('db.err', $err);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            exit($err);
        }
    } else {
        return $db_connect;
    }
}
if(!function_exists('db')) {
    function db()
    {
        return medoo_db();
    }
}
/***
 * 分页查寻
 * @param string $table 表名
 * @param string $column 字段
 * @param array $where  条件 [LIMIT=>1]
 * @return array
 */
function db_pager($table, $join, $columns = null, $where = null)
{
    if(!$columns) {
        $columns = $join;
        $join = '*';
    }
    global $_db_par;
    $flag = true;
    if (!$where) {
        $where   = $columns;
        $columns = $join ?: "*";
        $join    = '';
        if($where && is_string($where)) {
            $where = [];
        }
        $count   = db_pager_count() ?: db_get_count($table, $where);
    } elseif ($join && $where) {
        $flag    = false;
        $count   = db_pager_count() ?: db_get_count($table, $join, get_db_table_name($table) . ".id", $where);
    }
    if($where && is_string($where)) {
        $where = [];
    }
    $current_page  = (int)(g('page') ?: 1);
    $per_page      = (int)(g('per_page') ?: 20);
    $count         = (int)$count;
    $last_page     = ceil($count / $per_page);
    $has_next_page = $last_page > $current_page ? true : false;
    $start         = ($current_page - 1) * $per_page;
    if (is_object($where)) {
        $where->value =  $where->value . " LIMIT $start, $per_page";
    } else {
        $where['LIMIT'] = [$start, $per_page];
    }
    if ($flag) {
        $data  =  db_get($table, $columns, $where);
    } else {
        $data  =  db_get($table, $join, $columns, $where);
    }
    if($data) {
        $i = ($current_page - 1) * $per_page;
        $j = 1;
        foreach($data as &$v) {
            $v['index'] = ++$i;
        }
    }
    $_db_par['size'] = $per_page;
    $_db_par['count'] = $count;
    return [
        'current_page' => $current_page,
        'last_page'    => $last_page,
        'per_page'     => $per_page,
        'total'        => $count,
        'has_next_page' => $has_next_page,
        'data'         => $data,
        'data_count'   => count($data ?: []),
    ];
}
/**
 *  设置分页总记录数
 *  一般情况用不到
 */
function db_pager_count($nums = null)
{
    static $_page_count;
    if ($nums && $nums >= 0) {
        $_page_count = $nums;
    } else {
        return $_page_count;
    }
}
/**
 * 显示分页
 * 调用db_pager后，再调用。
 * db_pager_html([
 *      'url'=>'',
 * ]);
 */
function db_pager_html($arr = [])
{
    global $_db_par;
    if (isset($arr['count'])) {
        $count  = $arr['count'];
    } else {
        $count = $_db_par['count'];
    }
    $page_url = isset($arr['url']) ? $arr['url'] : '';
    if (isset($arr['size'])) {
        $size  = $arr['size'];
    } else {
        $size = $_db_par['size'] ?: 20;
    }
    $paginate = new medoo_paginate($count, $size);
    if ($page_url) {
        $paginate->url = $page_url;
    }
    $limit  = $paginate->limit;
    $offset = $paginate->offset;
    return $paginate->show();
}


/**
* 添加错误信息
*/
function db_add_error($str)
{
    global $_db_error;
    global $_db_thor_err;
    if($_db_thor_err) {
        throw new Exception($str);
    }
    $_db_error[] = $str;
    if(function_exists('do_action')) {
        do_action('db.err', $_db_error);
    }
}
/**
* 获取错误信息
*/
function db_get_error()
{
    global $_db_error;
    if ($_db_error) {
        return $_db_error;
    }
}

function db_get_fast($table, $join = null, $columns = null, $where = null)
{
    if($join == '*') {
        $join = "id";
        $in = db_get($table, $join, $columns, $where);
        if($in) {
            $join = "*";
            $where['id'] = $in;
            return db_get($table, $join, $where);
        } else {
            return;
        }
    } else {
        return db_get($table, $join, $columns, $where);
    }
}
/***
 * 根据表名、字段 、条件 查寻多条记录
 * where https://medoo.in/api/where
 * select($table, $columns)
 * select($table, $columns, $where)
 * select($table, $join, $columns, $where)
 * @param string $table 表名
 * @param string $column 字段
 * @param array $where  条件 [LIMIT=>1]
 * @return array
 */
function db_get($table, $join = null, $columns = null, $where = null)
{
    do_action("db_table.$table", $table);
    if(!$columns || is_numeric($columns)) {
        $columns = $join;
        $join = '*';
        if(is_numeric($columns)) {
            $where['LIMIT'] = $columns;
        }
    }
    if (is_array($join)) {
        foreach ($join as $k => $v) {
            if (is_string($v) && strpos($v, '(') !== false) {
                $join[$k] = db_raw($v);
            }
        }
    }
    if (is_string($columns) && strpos($columns, 'WHERE') !== false) {
        $columns = db_raw($columns);
    }
    $all =  medoo_db()->select(get_db_table_name($table), $join, $columns, $where);
    if($all) {
        foreach($all as &$v) {
            db_row_json_to_array($table, $v);
        }
    }
    //查寻数据
    if(db_can_run_action()) {
        foreach($all as &$v) {
            if($v && is_array($v)) {
                do_action("db_get_one.$table", $v);
            }
        }
    }
    if(medoo_db()->error) {
        db_add_error(medoo_db()->errorInfo[2]);
    }
    return $all;

}
/**
$lists = db_select('do_order', [
            'count' => 'COUNT(`id`)',
            'total' => 'SUM(`total_fee`)',
            'date'  => "FROM_UNIXTIME(`inserttime`, '%Y-%m-%d')"
        ],
        'WHERE `status` = 1 GROUP BY `date` LIMIT 30'
);
 */
function db_select($table, $join = "*", $columns = null, $where = null)
{
    return db_get($table, $join, $columns, $where);
}
/**
 * 写入记录
 *
 * @param string $table 表名
 * @param array  $data  数据
 * @return array
 */
function db_insert($table, $data = [], $don_run_action = false)
{
    do_action("db_table.$table", $table);
    foreach ($data as $k => $v) {
        if (substr($k, 0, 1) == "_") {
            unset($data[$k]);
        }
    }
    //写入数据前
    if(db_can_run_action() && !$don_run_action) {
        do_action("db_insert.$table.before", $data);
        do_action("db_save.$table.before", $data);
    }
    foreach($data as $k => $v) {
        if(get_table_field_is_json($table, $k)) {
            if($v && !is_array($v)) {
                $arr = json_decode($v, true);
                if($arr) {
                    $v = $arr;
                } else {
                    $v = yaml($v);
                }
            }
            if(!$v) {
                $v = [];
            }
        }
        if(is_array($v)) {
            $data[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
        } else {
            $data[$k] = $v;
        }
    }
    $_db    = medoo_db()->insert(get_db_table_name($table), $data);
    $id = medoo_db()->id();
    //写入数据后
    $action_data = [];
    $action_data['id'] = $id;
    $action_data['data'] = $data;
    if(db_can_run_action() && !$don_run_action) {
        do_action("db_insert.$table.after", $action_data);
        do_action("db_save.$table.after", $action_data);
    }
    if(medoo_db()->error) {
        db_add_error(medoo_db()->errorInfo[2]);
    }
    return $id;
}

/**
 * 更新记录
 *
 * @param string $table 表名
 * @param array  $data  数据
 * @return array
 */
function db_update($table, $data = [], $where = [], $don_run_action = false)
{
    do_action("db_table.$table", $table);
    if(!db_can_run_update()) {
        exit('从库禁止运行update操作');
    }
    global $_db_where;
    $_db_where = $where;

    foreach ($data as $k => $v) {
        if (substr($k, 0, 1) == "_") {
            unset($data[$k]);
        }
    }
    //更新数据前
    if(db_can_run_action() && !$don_run_action) {
        do_action("db_update.$table.before", $data);
        do_action("db_save.$table.before", $data);
    }
    foreach($data as $k => $v) {
        if(get_table_field_is_json($table, $k)) {
            if($v && !is_array($v)) {
                $arr = json_decode($v, true);
                if($arr) {
                    $v = $arr;
                } else {
                    $v = yaml($v);
                }
            }
            if(!$v) {
                $v = [];
            }
        }
        if(is_array($v)) {
            $data[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
        } elseif(is_string($v)) {
            $data[$k] = $v;
        }
    }
    $_db    = medoo_db()->update(get_db_table_name($table), $data, $where);
    $error = medoo_db()->error;
    if ($error) {
        throw new Exception($error);
    }
    $count =  $_db->rowCount();
    //更新数据后
    $action_data = [];
    $action_data['where'] = $where;
    $action_data['id']    = $where['id'] ?: '';
    $action_data['data']  = $data;
    if(db_can_run_action() && !$don_run_action) {
        do_action("db_update.$table.after", $action_data);
        do_action("db_save.$table.after", $action_data);
    }
    if(medoo_db()->error) {
        db_add_error(medoo_db()->errorInfo[2]);
    }
    return $count;

}

/**
 * 数据库事务
 *
 */
function db_action($call)
{
    global $is_db_action;
    $is_db_action = true;
    $result = "";
    $_db     = medoo_db();
    $_db->action(function ($_db) use (&$result, $call) {
        $call();
    });
}
/**
* 对数据进行
*/
function db_for_update($table, $id)
{
    global $is_db_action;
    if(!$is_db_action) {
        exit('query error:<br> db_action(function(){<br> db_for_update($table,$id);<br>});');
    }
    return db_get($table, "*", [
        'id' => $id,
        'FOR UPDATE' => true,
    ]);
}
/**
 * 根据表名、字段 、条件 查寻一条记录
 *
 * @param string $table 表名
 * @param string $column 字段
 * @param array  $where 条件
 * @return array
 */
function db_get_one($table, $join  = "*", $columns = null, $where = null)
{
    if (!$where) {
        $columns['LIMIT'] = 1;
    } else {
        $where['LIMIT']   = 1;
    }
    $_db = db_get($table, $join, $columns, $where);
    if ($_db) {
        $one =  $_db[0];
        if($one) {
            db_row_json_to_array($table, $one);
        }
        //查寻数据
        if(db_can_run_action()) {
            if($one && is_array($one)) {
                do_action("db_get_one.$table", $one);
            }
        }
        return $one;
    }
    return;
}
/**
 * SQL查寻
 */
function db_query($sql, $raw = null)
{
    if(!db_can_run_update($sql)) {
        exit('从库禁止运行update操作');
    }
    if ($raw === null) {
        return medoo_db()->query($sql);
    }
    $q = medoo_db()->query($sql, $raw);
    if ($q) {
        $all =  $q->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        //查寻数据
        if(db_can_run_action()) {
            do_action("db_query", $all);
        }
        return $all;
    } else {
        return [];
    }
}
/**
 * 取最小值
 * https://medoo.in/api/min
 * min($table, $column, $where)
 * min($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段
 * @param array $where   条件
 * @return void
 */
function db_get_min($table, $join  = "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->min(get_db_table_name($table), $join, $column, $where);
}

/**
 * 取最大值
 * max($table, $column, $where)
 * max($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段
 * @param array $where   条件
 * @return void
 */
function db_get_max($table, $join =  "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->max(get_db_table_name($table), $join, $column, $where);
}

/**
 * 总数
 * count($table, $where)
 * count($table, $join, $column, $where)
 * @param string $table  表名
 * @param array $where   条件
 * @return void
 */
function db_get_count($table, $join =  "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->count(get_db_table_name($table), $join, $column, $where) ?: 0;
}

/**
 * 是否有记录
 * has($table, $where)
 * has($table, $join, $where)
 * @param string $table  表名
 * @param array $where   条件
 * @return void
 */
function db_get_has($table, $join = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->has(get_db_table_name($table), $join, $where);
}

/**
 * 随机取多条记录
 * rand($table, $column, $where)
 * rand($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段
 * @param array $where   条件
 * @return void
 */
function db_get_rand($table, $join = "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->rand(get_db_table_name($table), $join, $column, $where);
}

/**
 * 取总和
 * sum($table, $column, $where)
 * sum($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段
 * @param array $where   条件
 * @return void
 */
function db_get_sum($table, $join = "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->sum(get_db_table_name($table), $join, $column, $where) ?: 0;
}

/**
 * 取平均值
 * avg($table, $column, $where)
 * avg($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段
 * @param array $where   条件
 * @return void
 */
function db_get_avg($table, $join = "*", $column = null, $where = null)
{
    do_action("db_table.$table", $table);
    return medoo_db()->avg(get_db_table_name($table), $join, $column, $where);
}

/**
 * RAW
 * https://medoo.in/api/raw
 * raw('NOW()')
 * raw('RAND()')
 * raw('AVG(<age>)')
 * @param string $raw
 * @return
 */
function db_raw($raw)
{
    return \Medoo\Medoo::raw($raw);
}

//删除
function db_del($table, $where)
{
    do_action("db_table.$table", $table);
    //删除数据前
    if(db_can_run_action()) {
        do_action("db_del.$table.before", $where);
    }
    if(!$where) {
        return;
    }
    if(is_array($where) && !array_filter($where)) {
        return;
    }
    $data = medoo_db()->delete(get_db_table_name($table), $where);
    $count = $data->rowCount();
    if(db_can_run_action() && $count > 0) {
        do_action("db_del.$table.after", $where);
    }    
    return $count;
}

function db_delete($table, $where)
{
    return db_del($table, $where);
}

/**
 * 显示所有表名
 *
 * @param string $table 表名
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return void
 */
function show_tables($table)
{
    $table = get_db_table_name($table);
    $sql = "SHOW TABLES LIKE '%$table%';";
    $all = medoo_db()->query($sql);
    foreach ($all as $v) {
        foreach ($v as $v1) {
            $list[] = $v1;
        }
    }
    return $list;
}
/**
 * 取表中字段
 */
function get_table_fields($table, $has_key  = true)
{
    $table = get_db_table_name($table);
    $sql   = "SHOW FULL FIELDS FROM `" . $table . "`";
    $lists = medoo_db()->query($sql);
    $arr   = [];
    foreach ($lists as $vo) {
        if ($has_key) {
            $arr[$vo['Field']] = $vo;
        } else {
            $arr[] = $vo['Field'];
        }
    }
    return $arr;
}
/**
 *返回数据库允许的数据，传入其他字段自动忽略
 */
function db_allow($table, $data)
{
    do_action("db_table.$table", $table);
    $table = get_db_table_name($table);
    $fields = get_table_fields($table);
    foreach ($data as $k => $v) {
        if (!$fields[$k]) {
            unset($data[$k]);
        }
    }
    return $data;
}

/**
 * 显示数据库表结构，支持markdown格式
 * @param string $name 数据库名
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 */
function database_tables($name = null, $show_markdown = false)
{
    global $config;
    if (!$name) {
        $name = $config['db_name'];
    }
    $sql  = "SHOW TABLE STATUS FROM `{$name}`";
    $all  = db_query($sql, []);
    foreach ($all as $k => $v) {
        $sql   = "SHOW FULL FIELDS FROM `" . $v['Name'] . "`";
        $lists = medoo_db()->query($sql, []);
        $all[$k]['FIELDS'] = $lists;
    }
    if (!$show_markdown) {
        return $all;
    }
    $str = "";
    foreach ($all as $v) {
        $str .= "###### " . $v['Name'] . " " . $v['Comment'] . "\n";
        $str .= "| 字段  |  类型 | 备注|\n";
        $str .= "| ------------ | ------------ |------------ |\n";
        foreach ($v['FIELDS'] as $vo) {
            $str .= "|  " . $vo['Field'] . " |  " . $vo['Type'] . " |" . $vo['Comment'] . "|\n";
        }
        $str .= "\n\n";
    }
    return $str;
}
/**
 * 取表中json字段
 */
function get_table_field_json($table)
{
    $table = get_db_table_name($table);
    static $table_fields;
    if(!isset($table_fields[$table])) {
        $all = get_table_fields($table);
        $table_fields_row = [];
        foreach($all as $k => $v) {
            if($v['Type'] == 'json') {
                $table_fields_row[$k] = true;
            }
        }
        $table_fields[$table] = $table_fields_row;
    }
    return $table_fields[$table];
}
/**
 * 判断表中的字段是不是json
 */
function get_table_field_is_json($table, $field)
{
    $table = get_db_table_name($table);
    $table_fields = get_table_field_json($table);
    if(isset($table_fields[$field])) {
        return true;
    } else {
        return false;
    }
}

/**
* 把数据库中json字段转成array
* @param $table_name 表名
* @param $row_data 一行记录
*/
function db_row_json_to_array($table_name, &$row_data = [])
{
    $table_name = get_db_table_name($table_name);
    if(is_array($row_data)) {
        foreach ($row_data as $key => $val) {
            if(is_string($val) && get_table_field_is_json($table_name, $key)) {
                $row_data[$key] = json_decode($val, true) ?: [];
            } elseif(is_string($val) && is_json($val)) {
                $row_data[$key] = json_decode($val, true);
            } elseif(is_string($val)) {
                $row_data[$key] = stripslashes($val);
            }
        }
    }
}


/**
 * 数组排序
 * array_order_by($row,$order,SORT_DESC);
 */
if(!function_exists('array_order_by')) {
    function array_order_by()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                if (!$data) {
                    return;
                }
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        if ($args) {
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
        return;
    }
}


/**
 * 判断是否为json
 */
if(!function_exists('is_json')) {
    function is_json($data, $assoc = false)
    {
        $data = json_decode($data, $assoc);
        if ($data && (is_object($data)) || (is_array($data) && !empty(current($data)))) {
            return $data;
        }
        return false;
    }
}


/**
 * 添加动作
 * @param string $name 动作名
 * @param couser $call function
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return mixed
 */
if(!function_exists("add_action")) {
    function add_action($name, $call, $level = 20)
    {
        global $_app;
        if (strpos($name, '|') !== false) {
            $arr = explode('|', $name);
            foreach ($arr as $v) {
                add_action($v, $call, $level);
            }
            return;
        }
        $_app['actions'][$name][] = ['func' => $call,'level' => $level];
    }
}

/**
 * 执行动作
 * @param  string $name 动作名
 * @param  array &$par  参数
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return  mixed
 */
if(!function_exists('do_action')) {
    function do_action($name, &$par = null)
    {
        global $_app;
        if (!is_array($_app)) {
            return;
        }
        $calls  = $_app['actions'][$name];
        $calls  = array_order_by($calls, 'level', SORT_DESC);
        if ($calls) {
            foreach ($calls as $v) {
                $func = $v['func'];
                $func($par);
            }
        }
    }
}




/**
 *  分页
 *  类似淘宝分页
 *
 * @since 2014-2015
 */
/**
 *<code>
 *类似淘宝分页
 *
 *
 *$paginate = new medoo_paginate($row->num,1);
 *$paginate->url = $this->url;
 *$limit = $paginate->limit;
 *$offset = $paginate->offset;
 *
 *$paginate = $paginate->show();
 *
 *
.pagination li{
    list-style: none;
    float: left;
    display: inline-block;
    border: 1px solid #ff523b;
    margin-left: 10px;
    width: 40px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    cursor: pointer;
}
.pagination .active{
   background: #eee;
   border: 1px solid #000;
}

 *</code>
 *
 */


class medoo_paginate
{
    public $page;
    public $pages;
    public $url;
    public $size;
    public $count;
    public $limit;
    public $offset;
    public $get = [];
    public static $class;
    public $query = 'page';
    /**
     * 构造函数
     */
    public function __construct($count, $size = 10)
    {
        $this->count = $count;
        $this->size = $size;
        //总页数
        $this->pages = ceil($this->count / $this->size);
        //当前页面
        $this->page = isset($_GET[$this->query]) ? (int)$_GET[$this->query] : '';
        if ($this->pages < 1) {
            return;
        }
        if ($this->page <= 1) {
            $this->page = 1;
        }
        if ($this->page >= $this->pages) {
            $this->page = $this->pages;
        }

        $this->offset = $this->size * ($this->page - 1);
        $this->limit = $this->size;
    }
    /**
     * 生成URL函数，如有需要，可自行改写
     * 调用函数 ($url,$par);
     * @param string $url
     * @param string $par
     * @return  string
     */
    public function url($url, $par = [])
    {
        $url = $url . '?' . http_build_query($par);
        return $url;
    }
    public function next($class = 'pagination')
    {
        $next = $this->page + 1;
        $p = $_GET;
        $p[$this->query] = $next;
        if ($next <= $this->pages) {
            return '<a rel="' . $next . '" class="' . $class . '" href="' . $this->url($this->url, $p) . '">下一页</a>';
        }
        return;
    }

    /**
     * 显示分页 pagination
     * @param string $class
     * @return  string
     */
    public function show($class = 'pagination')
    {
        if (static::$class) {
            $class = static::$class;
        }
        $str = '<ul class="' . $class . '">';
        $pre = $this->page - 1;
        $p = $_GET;
        $p[$this->query] = $pre > 0 ? $pre : 1;
        if ($pre > 0) {
            $str .= '<li><a href="' . $this->url($this->url, $p) . '">&laquo;</a></li>';
        }
        if ($this->pages < 2) {
            return;
        }
        $pages[1] = 1;
        $pages[2] = 2;
        $i = $this->page - 2 <= 1 ? 1 : $this->page - 2;
        $e = $this->page + 2 >= $this->pages ? $this->pages : $this->page + 2;
        if ($e < 5 && $this->pages >= 5) {
            $e = 5;
        }
        $pages['s'] = null;
        if ($i > 0) {
            for ($i; $i < $e + 1; $i++) {
                $pages[$i] = $i;
            }
        }
        $j = 0;
        foreach ($pages as $k => $v) {
            if ($j == 3) {
                $n = $k;
            }
            $j++;
        }

        if ($this->pages > 5) {
            if ($n != 3) {
                $pages['s'] = "...";
            }
            if ($e < $this->pages) {
                $pages['e'] = "...";
            }
        }
        $p = $_GET;
        if ($this->get) {
            foreach ($this->get as $d) {
                unset($p[$d]);
            }
        }

        foreach ($pages as $j) {
            $active = null;
            if ($j == $this->page) {
                $active = "class='active'";
            }
            if (!$j) {
                continue;
            }
            $p[$this->query] = $j;
            if ($j == '...') {
                $str .= "<li $active><a href='javascript:void(0);' class='no'>$j</a></li>";
            } else {
                $str .= "<li $active><a href='" . $this->url($this->url, $p) . "'>$j</a></li>";
            }
        }

        if ($this->page + 3 < $this->pages && $this->pages > 6) {
            $str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages - 1] + $p) . "'>" . ($this->pages - 1) . "</a></li>";
        }
        if ($this->page + 2 < $this->pages && $this->pages > 6) {
            $str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages] + $p) . "'>$this->pages</a></li>";
        }
        $p[$this->query] = $next = $this->page + 1;
        if ($next <= $this->pages) {
            $str .= '<li><a href="' . $this->url($this->url, $p) . '">&raquo;</a></li>';
        }


        $str .= "</ul>";
        return $str;
    }
}


/**
* 返回两个日期之间
* $date1 = '2022-11-01';
* $date2 = '2022-12-14';
* 字段是datetime类型
*/
function db_between_date($field, $date1, $date2)
{
    $start_time = date("Y-m-d 00:00:01", strtotime($date1));
    $end_time   = date("Y-m-d 23:59:59", strtotime($date2));
    $where = [];
    $where[$field . '[>=]'] = $start_time;
    $where[$field . '[<]'] = $end_time;
    return $where;
}
/**
* 返回两个月份之间
* $date1 = '2022-11';
* $date2 = '2022-12';
* 字段是datetime类型
*/
function db_between_month($field, $date1, $date2)
{
    $start_time = date("Y-m-d 00:00:01", strtotime($date1 . "-01"));
    $end_time   = date("Y-m-d 00:00:00", strtotime($date2 . "-01" . " +1 month"));
    $where = [];
    $where[$field . '[>=]'] = $start_time;
    $where[$field . '[<]'] = $end_time;
    return $where;
}

/**
* 跨库数据库事务
*/
function xa_db_action($key_call = [])
{
    global $_db_connects,$_db_thor_err,$_db_error;
    $_db_thor_err = true;
    $commits = [];
    try {
        foreach($key_call as $key => $call) {
            $medoo = $_db_connects[$key];
            $dsn = $medoo->info()['dsn'];
            $pdo = $medoo->pdo;
            if(!is_object($pdo)) {
                throw new Exception("未知的数据库" . $key);
            }
            $commits[] = $pdo;
            $pdo->beginTransaction();
            db_active($key);
            $call();
        }
        foreach($commits as $pdo) {
            $pdo->commit();
        }
        $flag = true;
    } catch (Exception $e) {
        foreach($commits as $pdo) {
            $pdo->rollBack();
        }
        $_db_error[] = $e->getMessage();
        $_db_thor_err = false;
        $flag = false;
    }
    db_active('default');
    return $flag;
}


/**
* 创建分区表,自动排除已有的

db_struct_table_range_auto('wordpress','my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
    '2024-02',
    '2024-03',
]);

*/
function db_struct_table_range_auto($db_name, $table, $year_month = [], $datetime_field = 'created_at', $name = 'p')
{
    $all = db_struct_show_range($db_name, $table);
    sort($year_month);
    if($all) {
        $list = [];
        $last = '';
        foreach($all as $v) {
            $k = str_replace($name, '', $v);
            $list[$k] = true;
            $last = $k;
        }
        foreach($year_month as $k => $v) {
            $a = date("Ym", strtotime($v));
            if($list[$a]) {
                unset($year_month[$k]);
            }
            if($last >= $a) {
                unset($year_month[$k]);
            }
        }
    }
    if(!$year_month) {
        return false;
    }
    return db_struct_table_range($table, $year_month, $datetime_field, $name);
}
/**
* 创建分区表，内部调用
db_struct_table_range('my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
],'created_at','p',false);

*/
function db_struct_table_range($table, $year_month = [], $datetime_field = 'created_at', $name = 'p', $return_sql = false)
{
    $table = addslashes($table);
    $next = '';
    foreach($year_month as $v) {
        $a = date("Ym", strtotime($v));
        $b = date("Y-m-01", strtotime("+1 month", strtotime($v)));
        $next .= "PARTITION " . $name . $a . " VALUES LESS THAN ('" . $b . "'),";
    }
    $next = substr($next, 0, -1);
    $table_info = db_show_create_table($table);
    if(strpos($table_info, 'PARTITION') === false) {
        $sql = " 
            ALTER TABLE `$table` 
            PARTITION BY RANGE COLUMNS($datetime_field) (
              " . $next . "
            );
        ";
    } else {
        $sql = " 
            ALTER TABLE `$table` 
            ADD PARTITION (
              " . $next . "
            );
        ";
    }
    if($return_sql) {
        return $sql;
    }
    return db_query($sql);
}
/**
* 分区表名称
*/
function db_struct_show_range($db_name = '', $table = '')
{
    $db_name = addslashes($db_name);
    $table   = addslashes($table);
    $sql = "SELECT * FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = '" . $db_name . "' AND TABLE_NAME = '" . $table . "'";
    $res = db_query($sql, []);
    foreach($res as $v) {
        $p[] = $v['PARTITION_NAME'];
    }
    return $p;
}
/**
* 删除某个分区表
* 删除后分区数据也会一起删除
*/
function drop_struct_range($table, $p)
{
    $table = addslashes($table);
    $p     = addslashes($p);
    $sql = "ALTER TABLE `$table` DROP PARTITION " . $p;
    return db_query($sql);
}
/**
* 显示create table语句
*/
function db_show_create_table($table, $return_val = true)
{
    $table = addslashes($table);
    $sql = 'SHOW CREATE TABLE `' . $table . '` ';
    $res = db_query($sql, [])[0];
    $val = $res['Create Table'];
    $list[$res['Table']] = $val;
    if($return_val) {
        return $val;
    }
    return $list;
}
