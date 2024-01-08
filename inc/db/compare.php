<?php
/**
 * 数据库结构比较并生成差量SQL
 */
function create_db_compare_sql($db_compare_config, $need_compare_dbs = [], $is_like = false)
{
    $arr = db_compare_main($db_compare_config);
    $main = $arr['db'];
    $main_sql_struct = $arr['data'];
    if($is_like) {
        $arr = db_compare_sync_like($db_compare_config, $need_compare_dbs);
    } else {
        $arr = db_compare_sync($db_compare_config, $need_compare_dbs);
    }
    $sql = '';
    foreach($arr as $v) {
        $need_sync_sql_struct = $v['data'];
        $sql .= db_compare_create_sync_sql($main_sql_struct, $need_sync_sql_struct, $v['name']);
    }
    return "START TRANSACTION;\n" . $sql . "COMMIT;\n\n";
}
/**
* 主库信息
* @param $db_compare_config 配置 ['host'=>'', 'name'=>'','user'=>'','pwd'=>'',  'port'=>3306]
*/
function db_compare_main($db_compare_config = [])
{
    global $db_compare_table_comment;
    $db_config = [
      'db_host' => $db_compare_config['db_host'],
      'db_name' => $db_compare_config['db_name'],
      'db_user' => $db_compare_config['db_user'],
      'db_pwd'  => $db_compare_config['db_pwd'],
      'db_port' => $db_compare_config['db_port']?: 3306,
    ];
    $db1 = new_db($db_config);
    $name = $db_compare_config['db_name']?:$db_compare_config['name'];
    $sql  = "SHOW TABLE STATUS FROM `{$name}`";
    $all  = $db1->query($sql, [])->fetchAll();
    if($all) {
        foreach ($all as $k => $v) {
            $db_compare_table_comment[$v['Name']] = [
              'Engine' => $v['Engine'],
              'Comment' => $v['Comment'],
            ];
            $sql   = "SHOW FULL FIELDS FROM `" . $v['Name'] . "`";
            $lists = $db1->query($sql, [])->fetchAll();
            $new_list = [];
            foreach($lists as $vv) {
                $str = db_compare_field_append($vv);
                $new_list[$vv['Field']] = $vv['Type'] . $str;
            }
            $new_all[$v['Name']] = $new_list;
        }
    }
    return ['db' => $db1,'data' => $new_all];
}
function db_compare_field_append($vv)
{
    $str = "";
    if($vv['Null'] == 'YES') {
        $str .= " Null ";
    } else {
        $str .= " NOT NULL";
    }
    if($vv['Default']) {
        $str .= " DEFAULT '" . $vv['Default'] . "'";
    }
    if($vv['Extra'] == 'auto_increment') {
        $str .= " AUTO_INCREMENT";
    }
    if($vv['Comment']) {
        $str .= " COMMENT '" . $vv['Comment'] . "'";
    }
    return $str;
}
/**
* 需要同步的数据库
*/
function db_compare_sync($db_compare_config, $sync_tables = [])
{
    foreach($sync_tables as $name) {
        $c = [
          'db_host' => $db_compare_config['db_host'],
          'db_name' => trim($name),
          'db_user' => $db_compare_config['db_user'],
          'db_pwd'  => $db_compare_config['db_pwd'],
          'db_port' => $db_compare_config['db_port']?: 3306,
        ];
        $db1  = new_db($c);
        $sql  = "SHOW TABLE STATUS FROM `{$name}`";
        $all  = $db1->query($sql, [])->fetchAll();
        $new_all = [];
        if($all) {
            foreach ($all as $k => $v) {
                $sql   = "SHOW FULL FIELDS FROM `" . $v['Name'] . "`";
                $lists = $db1->query($sql, [])->fetchAll();
                $new_list = [];
                foreach($lists as $vv) {
                    $str = db_compare_field_append($vv);
                    $new_list[$vv['Field']] = $vv['Type'] . $str;
                }
                $new_all[$v['Name']] = $new_list;
            }
        }
        $ret[] = [
          'db'   => $db1,
          'name' => $name,
          'data' => $new_all,
        ];
    }
    return $ret;
}
/**
* 需要同步的数据库
*/
function db_compare_sync_like($db_compare_config, $need_sync_dbs = [])
{
    $sql = "SHOW DATABASES";
    $c = [
      'db_host' => $db_compare_config['db_host'],
      'db_name' => $db_compare_config['db_name'],
      'db_user' => $db_compare_config['db_user'],
      'db_pwd'  => $db_compare_config['db_pwd'],
      'db_port' => $db_compare_config['db_port']?: 3306,
    ];
    $db1  = new_db($c);
    $all  = $db1->query($sql, [])->fetchAll();
    foreach($all as $v) {
        if(!in_array($v['Database'], ['performance_schema','information_schema'])) {
            $list[] = $v['Database'];
        }
    }
    $new_list = [];
    foreach($need_sync_dbs as $v) {
        foreach($list as $vv) {
            if(strpos($vv, $v) !== false) {
                //包含_admin的不需要
                if(strpos($vv, '_admin') === false) {
                    $new_list[] = $vv;
                }
            }
        }
    }
    if(!$new_list) {
        echo '未找到匹配的数据库，请确认配置正确!';
        exit;
    }
    return db_compare_sync($db_compare_config, $new_list);
}
/**
* 比较两个数据库结构
*/
function db_compare_create_sync_sql($struct_1, $struct_2, $struct_2_db)
{
    global $db_compare_table_comment;
    $str = "";
    foreach($struct_1 as $t => $f) {
        if(!$struct_2[$t]) {
            $field_create = '';
            foreach($f as $f1 => $f2) {
                $field_create .= "`$f1` " . $f2 . ",";
            }
            $Comment = $db_compare_table_comment[$t]['Comment'];
            $Engine = $db_compare_table_comment[$t]['Engine'];
            $str .= "CREATE TABLE IF NOT EXISTS `" . $t . "`(" . $field_create . " PRIMARY KEY(`id`)) ENGINE = " . ($Engine ?: 'InnoDB') . " COMMENT = '" . $Comment . "';\n\n";
        } else {
            //比较字段
            $ff = $struct_2[$t];
            $top_field = '';
            foreach($f as $k1 => $v1) {
                if(!$ff[$k1]) {
                    //缺少字段，添加字段
                    $str .= "ALTER TABLE `" . $t . "` ADD `" . $k1 . "` $v1 AFTER `" . ($top_field ?: 'id') . "`;\n";
                } else {
                    //有字段
                    if($ff[$k1] != $v1) {
                        $str .= "ALTER TABLE `" . $t . "` CHANGE `" . $k1 . "` `" . $k1 . "` $v1;\n";
                    }
                }
                $top_field = $k1;
            }
            //主库删除字段
            foreach($ff as $k1 => $v1) {
                if(!$f[$k1] && g("contain_drop")) {
                    $str .= "ALTER TABLE `" . $t . "` DROP `" . $k1 . "`;\n";
                }
            }

        }
    }
    //ALTER TABLE `debug`.`cart` ADD UNIQUE `index` (`user_id`(1));
    if($str) {
        return "use `$struct_2_db`;\n" . $str;
    }
}
