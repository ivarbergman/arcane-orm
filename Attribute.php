<?php

class Attribute extends Base
{

  public $active;
  public $scalar;
  public $readonly;
  public $name;
  public $alias;
  public $value;
  public $type;
  public $func;
  public $not_flag;

  function __construct($name, $value = null)
  {

    $this->active = false;
    $this->readonly = iskey($name, $this->_readonly);
    $this->name = $name;
    $this->alias = $this->alias().'__'.$this->name;
    $this->value = $value;
    $this->type = "attribute";
    $this->func = array();
  }

  function copy()
  {
    $a = $this->new_attribute($this->name);
    $a->active = false;
    $a->name = $this->name;
    $a->func = $this->func;
    $a->type = $this->type;
    return $a;
  }


  function reset()
  {
    $this->active = false;
    $this->type = "attribute";
  }

  function type($type = null)
  {
    if (func_num_args() == 0)
      {
	return $this->type;
      }
    switch ($type)
      {
	case "scalar":
	  break;
	case "func":
	  break;
	case "attribute":
	  break;
	case "sql":
	  break;
      }
    $this->active = false;
  }

  function is_scalar()
  {
    return $this->type == "scalar";
  }

  function is_func()
  {
    return $this->type == "func";
  }

  function is_sql()
  {
    return $this->type == "sql";
  }

  function is_attribute()
  {
    return $this->type == "attribute";
  }

  function is_attribute_value()
  {
    return $this->type == "attribute_value";
  }


  function __set($name, $value)
  {
    return $this->$name($value);
  }

  function __get($name)
  {
    return $this->$name();
  }

  public function condition($lh, $op, $rh)
  {
    $n = array("=" => "!=", "IS" => "IS NOT", "IN" => "NOT IN", "LIKE" => "NOT LIKE", "RLIKE" => "NOT RLIKE");
    if ($this->not_flag)
      {
	$op = $n[$op];
	$this->not_flag = false;
      }
    parent::condition($lh, $op, $rh);
  }


  function not() 
  { 
    $this->not_flag = true;
    return $this;  
  }


  function lt($val) {  return $this->__is_smaller($val);  }
  function __is_smaller($val) {

    if ($this->active)
      {
	return $this->value < $val;
      }
    $this->condition($this, "<", $val);
    return true;
  }

  function gt($val) {  return $this->__is_greater($val);  }
  function __is_greater($val) {

    if ($this->active)
      {
	return $this->value > $val;
      }
    $this->condition($this, ">", $val);
    return true;
  }


  function le($val) {  return $this->__is_smaller_or_equal($val);  }
  function __is_smaller_or_equal($val) {

    if ($this->active)
      {
	return $this->value <= $val;
      }
    $this->condition($this, "<=", $val);
    return true;
  }

  function ge($val) {  return $this->__is_greater_or_equal($val);  }
  function __is_greater_or_equal($val) {

    if ($this->active)
      {
	return $this->value >= $val;
      }
    $this->condition($this, ">=", $val);
    return true;
  }

  function eq($val) {  return $this->__is_equal($val);  }
  function __is_equal($val) {

    if ($this->active)
      {
	return $this->value == $val;
      }
    $this->condition($this, "=", $val);
    return true;
  }

  function neq($val) {  return $this->__is_nequal($val);  }
  function __is_nequal($val) {

    if ($this->active)
      {
	return $this->value == $val;
      }
    $this->condition($this, "!=", $val);
    return true;
  }


  function in($val) { 

    if ($this->active)
      {
	return $this->value == $val;
      }
    $this->condition($this, "IN", $val);
    return true;
  }


  function is($val) {  return $this->__is_is($val);  }
  function __is_is($val) {

    if ($this->active)
      {
	return $this->value == $val;
      }
    $this->condition($this, "IS", $val);
    return true;
  }

  function like($val) {  return $this->__like($val);  }
  function __like($val) {

    $this->condition($this, "LIKE", $val);
    return true;
  }

  function rlike($val) {  return $this->__rlike($val);  }
  function __rlike($val) {

    $this->condition($this, "RLIKE", $val);
    return true;
  }

  function group() 
  {                                                            

    $this->_bridge->add_group($this);                                                
    return true;
  }

  function order($dir)
  {
    $this->_bridge->add_order($this, $dir);
  }

  function desc()
  {
    $this->_bridge->add_order($this, "DESC");
  }

  function asc()
  {
    $this->_bridge->add_order($this, "ASC");
  }

  function rand()
  {
    $this->_bridge->add_order($this, "RAND()");
  }

  function order_field($field, $dir = "ASC") { 

    $this->_bridge->add_order($this, $dir, $field);
  }

  function __call($name, $args)
  {
    $a = $this->copy();

    if (method_exists('Func',$name))
      {
	if (count($args))
	  {
	    $a->func[] = Func::$name($args);
	  }
	else 
	  {
	    $a->func[] = Func::$name();
	  }
      }
    else
      {
	Log::dbg(debug_backtrace(false));
      }
    return $a;
  }

  function global_alias()
  {

    $alias = $this->alias;
    if (false && !$this->single())
      {
	$alias = $this->alias() .'__'. $this->alias;
      }

    return $alias;
  }

  function sql()
  {

    $alias = "";
    if (!$this->single())
      {
	$alias = $this->alias().'.';
      }
    
    $sql = $alias . $this->name;
    foreach ($this->func as $func)
      {
	$sql = $func->sql($sql);
      }
    return $sql;
  }


  function assign($name = null)
  {
    $v = $this->assign_rhs();
    if ($v)
      {
	return $this->assign_lhs($name). " = " . $v;
      }
    return "";
  }

  function assign_rhs()
  {

    if ($this->readonly)
      {
	return "";
      }

    if (!$this->active)
      {
	return "";
      }


    if ($this->is_scalar())
      {
	$sql = $this->_bridge->bind($this->value);
      }
    else if ($this->is_func())
      {
	$sql = "".$this->value;
      }
    else if ($this->is_sql())
      {
	$sql = "".$this->value;
      }
    else if ($this->is_attribute())
      {
	$sql = "".$this->sql();
      }

    return $sql;
  }

  function assign_lhs($name = null)
  {    
    return $this->alias().'.'.$this->name;
  }

  function __toString()
  {
    if ($this->active)
      {
	return "".$this->value;
      }
    return "";
  }

  function template($tpl)
  {
    $args = func_get_args();
    array_shift($args);

    $file = $GLOBALS["BASE_PATH"]."view_".$tpl.".php";
    if (!file_exists($file))
      {
	return false;
      }
    $code = file_get_contents($file);
    ob_start();
    $source = "?>". $code ."<? ";
    error_reporting(E_ALL | E_STRICT);  
    set_error_handler('my_eval_error_handler');  
    $result = eval($source);  
    restore_error_handler();
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
  }



}
?>
