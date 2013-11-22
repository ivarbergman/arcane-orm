<?php

abstract class Bridge
{

  public $entity;
  public $values;
  public $condition;
  public $join;
  public $orders;
  protected $group;
  public $limit;
  public $page;
  protected $counter;
  public $meta;
  public $variant;
  public $union;

  public $insert_batch_data;
  public $insert_batch_attributes;

  public function __construct()
  {
    $this->reset();
  }

  public function reset()
  {


    $this->condition = array();
    $this->values = array();
    $this->join = array();
    $this->union = array();

    $this->entity = array();
    $this->counter = 0;
    $this->group = array();
    $this->limit = null;
    $this->page = null;
    $this->orders = array();
    $this->variant = array();
    $this->insert_batch_data = array();
    $this->insert_batch_attributes = array();

  }

  public function add_condition($lh, $op, $rh)
  {
    $this->condition[] = array($lh, $op, $rh);
  }

  public function add_entity($e)
  {
    if ($e instanceof Entity)
      {
	$this->entity[spl_object_hash($e)] = $e;
      }
  }

 function add_group($attr)
  {
    $this->group[] = $attr;
  }

 function add_order($attr, $dir, $field = null)
  {
    $this->orders[] = array($attr, $dir, $field);
    Log::dbg("Brigde->add_order({$attr->name}, $dir, $field = null) " . count($this->orders));
  }


 function add_limit($num)
  {
    $this->limit = $num;
  }

 function add_page($num)
  {
    $this->page = $num;
  }

  public function join($type, $lh, $rh)
  {
    //$this->add_entity($lh);
    //$this->add_entity($rh);
    $this->join[] = array($type, $lh, $rh);
  }

  public function info()
  {

    $str = "Entities:" . count($this->entity) . PHP_EOL;

    foreach ($this->entity as $hash => $e)
      {
	$str .= $e->sql() . PHP_EOL;
      }

    foreach ($this->condition as $c)
      {
	$str .= $c[0] ." ". $c[1]." ".  $c[2]. PHP_EOL;
      }
    return $str;
  }

  function bind($value, $name = "bind")
  {
    if ($value instanceof Func )
      {
	$key = $value->sql();
      }
    else if (is_null($value))
      {
	$key = "NULL";
      }
    else
      {
	$this->counter = 1 + $this->counter;
	$key = $name . $this->counter;
	$this->values[":$key"] = $value;
	$key = ":{$key}";
      }
    return $key;
  }

  public function is_single_entity()
  {
    return count($this->entity) <= 1;
  }


  function assign_attribute($entity = null)
  {
    $sql = new StdClass();
    $sep = "";

    $entity = is_null($entity) ? $this->entity : array($entity);
    foreach ($entity as $e)
      {
	foreach ($e->_attr as $n => $a)
	  {
	    if (!$a->active) {
	      Log::dbg("assigning inactive attr " . $a->name);
	      continue;
	    }
	    Log::dbg("assigning active attr " . $a->name);

	    $lhs = $a->sql();
	    $rhs = null;
	    if ($a->is_scalar())
	      {
		$rhs = $this->bind($a->value, "pk");
	      }
	    else if ($a->is_func())
	      {
		$lhs = $a->sql();
		$rhs = "".$a->value;
	      }
	    else if ($a->is_attribute_value())
	      {
		$lhs = $a->sql();
		$rhs = "".$a->value->sql();
	      }
	    else
	      {
		$lhs = $a->sql();
		$rhs = "" . $a->value;
	      }

	    if (isset($rhs))
	      {
		$sql->assign_attributes[$lhs] = $rhs;
		$sql->assign_set .= $sep . $lhs ." = ". $rhs;
		$sql->assign_value_lhs .= $sep . $lhs;
		$sql->assign_value_rhs .= $sep . $rhs;
		$sql->duplicate_key_assign_value .= $sep . "$lhs = VALUES($lhs)";
		$sep = ", ";
	      }
	  }
      }
    return $sql;
  }

  function insert_batch($e)
  {
    $sql = $this->assign_attribute($e);

    $this->insert_batch_data[] = $sql->assign_attributes;
    $this->insert_batch_attributes = array_unique(array_merge($this->insert_batch_attributes, array_keys($sql->assign_attributes)));
  }


  abstract function expr_list();
  abstract function refs();
  abstract function cond();
  abstract function order();
  abstract function limit();
  abstract function assign();
  abstract function pkcond($e);

  abstract function union($e);
  abstract function select($e);
  abstract function insert($e);
  abstract function insert_batch_flush($e);
  abstract function replace($e);
  abstract function update();

  abstract function delete($e);
  abstract function save($e);
  abstract function remove($e);

  abstract function select_variant();
  abstract function collate();

}

?>
