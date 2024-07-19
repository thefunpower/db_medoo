<?php


class model
{
    protected $table   = '';
    protected $primary = 'id';
    public static $_find_by_id;
    protected $field = [];
    protected $validate_add = [];
    protected $validate_edit = [];
    protected $unique_message = [];
    protected $ignore_after_find_hook;
    protected $has_one;
    protected $has_many;
    public $ignore_relation = true;
    public $_relation_with = [];
    public static $init;
    public static $_cache_find; 
    /**
    * 字段映射 名字=>数据库中字段名
    * 仅支持find方法
    */
    protected $field_ln = [

    ];
    /*
    https://github.com/vlucas/valitron
    */
    protected $validate = [];
    public function __construct()
    {
        $lang = 'zh-cn';
        \lib\Validate::lang($lang);
        $this->init();
    }
    /**
     * 取表名
     */
    public function get_table_name()
    {
        return $this->table;
    }
    /**
     * INIT
     */
    protected function init() {}
    /**
    * 查寻前
    */
    public function before_find(&$where) {}
    /**
    * 查寻后
    */
    public function after_find(&$data)
    {
        $this->ignore_after_find_hook[$this->table . $data['id']] = true;
    }
    /**
     * model instance
     */
    public static function model()
    {
        static::$init = new static();
        return static::$init;
    }
    /**
     * 开启关联查寻
     */
    public function relation($opt = [])
    {
        $this->ignore_relation = false;
        $this->_relation_with = $opt;
        return $this;
    }
    /**
     * 仅用于分于
     */
    public function reset_relation()
    {
        $this->ignore_relation = true;
    }
    /**
     * 处理关联
     */
    public function do_relation(&$data)
    {
        $_relation_with = $this->_relation_with;
        if(!$this->ignore_relation) {
            $has_many = $this->has_many;
            if($has_many) {
                foreach($has_many as $k => $v) {
                    $cls = "\\" . $v[0];
                    $key = $v[1];
                    $pk = $v[2] ?: 'id';
                    $option = $v[3] ?: [];
                    $val = $data[$pk];
                    if($key && $key  && $val) {
                        $where = $option;
                        $where[$key] = $val;
                        if($_relation_with && in_array($k, $_relation_with)) {
                            unset($_relation_with[array_search($k, $_relation_with)]);
                            $data[$k] = $cls::model()->relation($_relation_with)->find($where);
                        } else {
                            $data[$k] = $cls::model()->find($where);
                        }
                    }
                }
            }
            $has_one = $this->has_one;
            if($has_one) {
                foreach($has_one as $k => $v) {
                    $cls = "\\" . $v[0];
                    $key = $v[1];
                    $pk = $v[2] ?: 'id';
                    $option = $v[3] ?: [];
                    $val = $data[$key];
                    if($key && $key  && $val) {
                        $where = $option;
                        $where[$pk] = $val;
                        if($_relation_with && in_array($k, $_relation_with)) {
                            $data[$k] = $cls::model()->relation($_relation_with)->find($where, 1);
                        } else {
                            $data[$k] = $cls::model()->find($where, 1);
                        }
                    }
                }
            }
        }
    }
    /**
    * 查寻后
    */
    public function after_find_inner(&$data)
    {
        $ln = $this->field_ln;
        if($ln) {
            $data['_has_ln'] = true;
            foreach($ln as $k => $v) {
                if($data[$v]) {
                    $data[$k] = $data[$v];
                }
            }
        }
    }

    /**
    * 写入数据前
    */
    public function before_insert(&$data)
    {
        $validate = $this->validate_add ?: $this->validate;
        if($this->field && $validate) {
            $unique = $validate['unique'];
            unset($validate['unique']);
            $vali = validate($this->field, $data, $validate);
            if($vali) {
                json($vali);
            }
            if($unique) {
                foreach($unique as $i => $v) {
                    $where = [];
                    $f1 = "";
                    foreach($v as $f) {
                        $where[$f] = $data[$f];
                        if(!$f1) {
                            $f1 = $f;
                        }
                    }
                    $res = $this->find($where);
                    if($res) {
                        json_error(['msg' => $this->unique_message[$i] ?: '记录已存在','key' => $f1]);
                    }
                }
            }
        }
    }
    /**
    * 写入数据后
    */
    public function after_insert($id) {}

    /**
    * 更新数据前
    */
    public function before_update(&$data, $where)
    {
        $id = $where[$this->primary];
        $validate = $this->validate_edit ?: $this->validate;
        if($this->field && $validate) {
            $unique = $validate['unique'];
            unset($validate['unique']);
            $vali  = validate($this->field, $data, $validate);
            if($vali) {
                json($vali);
            }
            if($unique) {
                foreach($unique as $i => $v) {
                    $con = [];
                    $f1 = "";
                    foreach($v as $f) {
                        $con[$f] = $data[$f];
                        if(!$f1) {
                            $f1 = $f;
                        }
                    }
                    $res = $this->find($con, 1);
                    if($res && $res[$this->primary] != $id) {
                        json_error(['msg' => $this->unique_message[$i] ?: '记录已存在','key' => $f1]);
                    }
                }
            }
        }

    }
    /**
    * 更新数据后
    */
    public function after_update($row_count, $data, $where) {}
    /**
    * 删除前
    */
    public function before_del(&$where) {}
    /**
    * 删除后
    */
    public function after_del($where) {}
    /**
     * 忽略HOOK 更新
     */
    public function f_update($data, $where = '')
    {
        return $this->update($data, $where, true);
    }
    /**
    * 更新数据
    */
    public function update($data, $where = '', $ignore_hook = false)
    {
        if(!$where) {
            return false;
        }
        $this->_where($where);
        if(!$ignore_hook) {
            $this->before_update($data, $where);
        }
        $data_db = db_allow($this->table, $data);
        if(!$data_db) {
            return false;
        }
        $row_count = db_update($this->table, $data_db, $where);
        if(!$ignore_hook) {
            $this->after_update($row_count, $data, $where);
        }
        return $row_count;
    }
    /**
    * 忽略HOOK 写入数据
    */
    public function f_insert($data)
    {
        return $this->insert($data, true);
    }
    /**
    * 写入数据
    */
    public function insert($data, $ignore_hook = false)
    {
        if(!$ignore_hook) {
            $this->before_insert($data);
        }
        $data_db = db_allow($this->table, $data);
        if(!$data_db) {
            return false;
        }
        $id = db_insert($this->table, $data_db);
        if(!$ignore_hook) {
            $this->after_insert($id);
        }
        return $id;
    }
    /**
    * 忽略HOOK 批量写入数据
    */
    public function f_inserts($data)
    {
        return $this->inserts($data, true);
    }
    /**
    * 批量写入数据
    */
    public function inserts($data, $ignore_hook = false)
    {
        $new_data = [];
        foreach($data as &$v) {
            if(!$ignore_hook) {
                $this->before_insert($v);
            }
            $allow_data = db_allow($this->table, $v);
            if($allow_data) {
                $new_data[] = $allow_data;
            }
        }
        if(!$new_data) {
            return false;
        }
        db()->insert($this->table, $new_data);
        return true;
    }
    /**
    * 忽略HOOK 分页
    */
    public function f_pager($join, $columns = null, $where = null)
    {
        return $this->pager($join, $columns, $where, true);
    }
    /**
    * 分页
    */
    public function pager($join, $columns = null, $where = null, $ignore_hook = false)
    {
        if($join['select']) {
            $columns = $join;
            unset($columns['select']);
            $join = $join['select'];
        }
        $this->_where($where);
        $all =  db_pager($this->table, $join, $columns, $where);
        if($all['data']) {
            foreach($all['data'] as &$v) {
                $this->do_relation($v);
                $this->after_find_inner($v);
                if(!$ignore_hook) {
                    $this->after_find($v);
                }
            }
        }
        $this->reset_relation();
        return $all;
    }
    /**
    * SUM
    */
    public function sum($filed, $where = '')
    {
        $this->_where($where);
        return db_get_sum($this->table, $filed, $where);
    }
    /**
    * COUNT
    */
    public function count($where = '')
    {
        $this->_where($where);
        return db_get_count($this->table, $this->primary, $where);
    }
    /**
    * MAX
    */
    public function max($filed, $where = '')
    {
        $this->_where($where);
        return db_get_max($this->table, $filed, $where);
    }
    /**
    * MIN
    */
    public function min($filed, $where = '')
    {
        $this->_where($where);
        return db_get_min($this->table, $filed, $where);
    }
    /**
    * AVG
    */
    public function avg($filed, $where = '')
    {
        $this->_where($where);
        return db_get_avg($this->table, $filed, $where);
    }
    /**
    * 忽略HOOK 删除数据
    */
    public function f_delete($where)
    {
        return $this->del($where, true);
    }
    /**
    * 忽略HOOK 删除数据
    */
    public function f_del($where)
    {
        return $this->del($where, true);
    }
    /**
    * 删除数据
    */
    public function delete($where = '', $ignore_hook = false)
    {
        return $this->del($where, $ignore_hook);
    }
    /**
    * DEL
    */
    public function del($where = '', $ignore_hook = false)
    {
        $this->_where($where);
        if(!$ignore_hook) {
            $this->before_del($where);
        }
        if(!$where) {
            return false;
        }
        $res = db_del($this->table, $where);
        if(!$ignore_hook) {
            $this->after_del($where);
        }
        return $res;
    }
    /**
    * 原生
    * select(['@phone']) distinct
    */
    public function select($join, $columns = null, $where = null)
    {
        $res = medoo_db()->select($this->table, $join, $columns, $where);
        return $res;
    }
    /**
    * 查寻一条记录
    */
    public function find_one($where = '', $ignore_hook = false)
    {
        return $this->find($where, 1, false, $ignore_hook);
    }
    /**
     * 根据ID查寻
     */
    public function find_by_id($id, $ignore_hook = false)
    {
        $data = self::$_find_by_id[$id];
        if($data) {
            return $data;
        } else {
            self::$_find_by_id[$id] = $data = $this->find_one($id, $ignore_hook = false);
            return $data;
        }
    }
    /**
    * 查寻多条记录
    */
    public function find_all($where = '', $ignore_hook = false)
    {
        return $this->find($where, '', false, $ignore_hook);
    }
    /**
    * 忽略HOOK 删除数据
    */
    public function f_find($where = '', $limit = '', $use_select = false)
    {
        return $this->find($where, $limit, $use_select, true);
    }
    /**
    * 查寻记录
    */
    public function find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        $data = $this->_find($where, $limit, $use_select, $ignore_hook);
        $this->reset_relation();
        return $data;
    }
    /**
    * 查寻记录
    */
    public function find_cache($where = '', $limit = '', $use_select = false, $ignore_hook = false){
        $uni = "";
        if(is_array($where)){
            $uni = md5(json_encode($where));
        }else{
            $uni = $where; 
        }
        $uni = md5($uni.$limit.'a'.$use_select.'b'.$ignore_hook);
        $key = 'model:cache:'.$this->table.":".$uni;
        $res = self::$_cache_find[$key];
        if($res){
            return $res;
        }
        $res = $this->find($where, $limit, $use_select, $ignore_hook);
        self::$_cache_find[$key] = $res; 
        return $res;
    }
    /**
    * 查寻记录
    */
    public function _find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        $select = "*";
        if($where && is_array($where)) {
            $select = $where['select'] ?: "*";
            unset($where['select']);
        }
        if(!is_array($where) && $where) {
            $limit = 1;
        }
        $this->_where($where);
        if($limit) {
            $where['LIMIT'] = $limit;
        }
        $this->before_find($where);
        $ln = $this->field_ln;
        if($use_select) {
            foreach($where as $k => $v) {
                if(is_string($v) && substr($v, 0, 1) == '@') {
                    $find = substr($v, 1);
                    if($ln && $ln[$find]) {
                        $where[$k] = "@" . $ln[$find];
                    }
                }
                if(is_object($v)) {
                    $vv = $v->value;
                    if($vv && is_string($vv) && strpos($vv, 'DISTINCT') !== false) {
                        preg_match_all("/<(.*)>/", $vv, $matches);
                        $a = $matches[0];
                        $b = $matches[1];
                        if($a && $b) {
                            foreach($b as $k_b => $b1) {
                                if($ln[$b1]) {
                                    $vv = str_replace($a[$k_b], $ln[$b1], $vv);
                                }
                            }
                            $where[$k]->value = $vv;
                        }
                        $use_select = true;
                    }
                }
            }
        }
        if($limit && $limit == 1) {
            if($use_select) {
                $res = $this->select($where);
            } else {
                $res = db_get_one($this->table, $select, $where);
            }
            if(is_array($res)) {
                $this->do_relation($res);
                $this->after_find_inner($res);
                if(!$ignore_hook) {
                    if(is_array($res) && !$this->ignore_after_find_hook[$this->table . $res['id']]) {
                        $this->after_find($res);
                    }
                }
            }
        } else {
            if($use_select) {
                $res = $this->select($where);
            } else {
                $res = db_get($this->table, $select, $where);
            }
            foreach($res as &$v) {
                if(is_array($v)) {
                    $this->do_relation($v);
                    $this->after_find_inner($v);
                    if(!$ignore_hook) {
                        if(is_array($v) && !$this->ignore_after_find_hook[$this->table . $v['id']]) {
                            $this->after_find($v);
                        }
                    }
                }
            }
        }
        return $res;
    }

    protected function _where(&$where)
    {
        if($where && !is_array($where)) {
            $where = [$this->primary => $where];
        }
        if(!$where) {
            $where = [];
        }
        $ln = $this->field_ln;
        if($ln) {
            foreach($where as $k => $v) {
                if(strpos($k, '[') !== false) {
                    $k1 = substr($k, 0, strpos($k, '['));
                    $k2 = substr($k, strpos($k, '['));
                    if($ln[$k1]) {
                        unset($where[$k]);
                        $where[$ln[$k1] . $k2] = $v;
                    }
                } elseif($ln[$k]) {
                    unset($where[$k]);
                    $where[$ln[$k]] = $v;
                }
            }
        }
    }

    /**
    * 向上取递归
    * 如当前分类是3，将返回 123所有的值
    * $arr = get_tree_up($v['catalog_id'],true);
    * foreach($arr as $vv){
    *   $title[] = $vv['title'];
    * }
    * id pid
    * 1  0
    * 2  1
    * 3  2
    */
    public function get_tree_up($id, $is_frist = true)
    {
        static $_data;
        if($is_frist) {
            $_data = [];
        }
        $end = $this->f_find(['id' => $id], 1);
        $_data[] = $end;
        if($end['pid'] > 0) {
            $this->get_tree_up($end['pid'], false);
        }
        return array_reverse($_data);
    }
    /**
    * 递归删除
    */
    public function tree_del($id = '', $where = [])
    {
        if($where) {
            $catalog = $this->f_find($where);
        }
        $all = array_to_tree($catalog, $pk = 'id', $pid = 'pid', $child = 'children', $id);
        if($id) {
            $this->delete(['id' => $id]);
        }
        if($all) {
            $this->_loop_del_tree($all);
        }
    }
    /**
     * 数组转成tree
     */
    public function array_to_tree($new_list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0, $my_id = '')
    {
        $list = array_to_tree($new_list, $pk, $pid, $child, $root, $my_id);
        $list = array_values($list);
        return $list;
    }
    /**
    * 向下递归
    */
    public function get_tree_id($id, $where = [], $get_field = 'id')
    {
        $list = $this->f_find($where);
        $tree = array_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $id);
        $tree[] = $this->_find(['id' => $id], 1, false, true);
        $all = $this->_loop_tree_deep_inner($tree, $get_field, $is_frist = true);
        return $all;
    }
    /**
    * 内部实现
    */
    public function _loop_del_tree($list)
    {
        foreach($list as $v) {
            $this->delete(['id' => $v['id']]);
            if($v['children']) {
                $this->_loop_del_tree($v['children']);
            }
        }
    }
    /**
    * 内部实现
    */
    public function _loop_tree_deep_inner($all, $get_field, $is_frist = false)
    {
        static $_data;
        if($is_frist) {
            $_data = [];
        }
        foreach($all as $v) {
            $_data[] = $v[$get_field];
            if($v['children']) {
                $this->_loop_tree_deep_inner($v['children'], $get_field);
            }
        }
        return $_data;
    }
}
