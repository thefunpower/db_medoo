<?php 
/**
 * 添加动作
 * @param string $name 动作名
 * @param couser $call function
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return mixed
 */
function add_action($name, $call,$level = 20)
{
    global $_app;
    if (strpos($name, '|') !== false) {
        $arr = explode('|', $name);
        foreach ($arr as $v) {
            add_action($v, $call,$level);
        }
        return;
    }
    $_app['actions'][$name][] = ['func'=>$call,'level'=>$level];  
}
/**
 * 执行动作
 * @param  string $name 动作名
 * @param  array &$par  参数
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return  mixed
 */
function do_action($name, &$par = null)
{
    global $_app;
    if (!is_array($_app)) {
        return;
    }
    $calls  = $_app['actions'][$name]; 
    $calls  = array_order_by($calls,'level',SORT_DESC);  
    if ($calls) {
        foreach ($calls as $v) {
            $func = $v['func'];
            $func($par);
        }
    }
}

/**
 * 数组排序
 * array_order_by($row,$order,SORT_DESC);
 */
function array_order_by order_by()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            if (!$data) return;
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
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
