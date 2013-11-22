<?php

class QueryEntity
{
  var $name;
  var $alias;
  var $col;
  var $rel;
  var $rev;
  var $pk;
  var $join;


  function __construct($e)
  {
    if ($e instanceof Entity)
      {
	$this->name = $e->name();
	$this->alias = $e->alias();
	$this->col = $e->_col;
	$this->rel = $e->_rel;
	$this->rev = $e->_rev;
	$this->pk = $e->_pk;
      }
    else if ($e instanceof Attribute)
      {
	$e = $e->entity;
	$this->name = $e->_name;
	$this->alias = $e->_alias;
	$this->col = $e->_col;
	$this->rel = $e->_rel;
	$this->rev = $e->_rev;
	$this->pk = $e->_pk;
      }

      
  }

  function __toString()
  {
    return $this->name;
  }

  function expr()
  {
    $sql = "";
    $sep = "";

    if ($this->col)
      {
	foreach ($this->col as $name => $type)
	  {
	    $sql .= "$sep {$this->alias}.{$name} AS {$this->name}__{$name}";	    
	    $sep = ",\n\t";
	  }
      }
    else if ($this->name != "dual")
      {
	$sql .= "{$this->alias}.*";	    
      }
    return $sql;
  }

  function cond($ref_idx, &$join_tree)
  {
    $on = "";
    $join = "";
    $sql = "";

    Log::dbg("Join cond ------------------------------- ");
    if ($ref_idx >= 0)
      {
	$ref = $join_tree[$ref_idx];	  
	if ($this->join)
	  {
	    Log::dbg("Join cond: ".$this->name);
	    do {
	      $ref = $join_tree[$ref_idx];	  
	      Log::dbg("Join cond: $ref_idx ref ".$ref->name);
	      $lc = false;
	      if (  array_key_exists($this->name, $ref->rel) )
		{
		  $lc = $ref->alias.".".$ref->rel[$this->name];
		  $rc = $this->alias.".".$this->pk;
		}
	      else if ( array_key_exists($this->name, $ref->rev) )
		{
		  $lc = $ref->alias.".".$ref->pk;
		  $rc = $this->alias.".".$ref->rev[$this->name];
		}
	      $ref_idx--;
	      Log::dbg("Join cond: next ref_idx ".$ref_idx);
	    } while (!$lc && $ref_idx>=0);
	    
	    $on = "ON ( $lc = $rc )";
	    $sql = "\n\t{$this->join} {$this->name} AS {$this->alias} $on ";
	  }
	else     	
	  {
	    $sql = ($ref ? ", ":"") . " {$this->name} AS {$this->alias} ";
	  }
      }
    else     	
      {
	$alias = $this->alias != '_dual' ? "AS {$this->alias}" : "";
	$sql = " {$this->name} {$alias} ";
      }
  
    return $sql;    
  }
}

class QueryBuilder
{
  var $expr;
  var $refs;
  var $conditions;
  var $outer_join;
  var $values;
  var $value_counter;
  var $limit;
  var $group;
  var $orders;
  var $count;
  var $collate;
  var $last_sql;
  var $join_tree;
  var $variant;

  var $bridge;

  function __construct()
  {
    $this->reset();
  }

  function __toString()
  {
    return "<QueryBuilder>";
  }

  function reset()
  {
	    
    $this->expr = "";
    $this->refs = "";
    $this->conditions = array();
    $this->outer_join = array();
    $this->values = array();
    $this->value_counter = 0;
    $this->limit = null;
    $this->group = null;
    $this->orders = array();
    $this->collate = false;
    $this->last_sql = "";
    $this->join_tree = array();
    $this->variant = array();

    $this->bridge = new MySql();
  }

  function add_entity($e)
  {
    $this->bridge->add_entity($e);
    $qe = new QueryEntity($e);
    $this->join_tree[$e->alias()] = array($qe);
  }


  function join($type, $lh, $rh)
  {

    $this->bridge->join($type, $lh, $rh);

    $lhq = new QueryEntity($lh);
    $rhq = new QueryEntity($rh);
    Log::dbg("query->join($type, $lhq, $rhq)");

    if ($this->join_tree[$lhq->alias] == null)
      {
	$this->join_tree[$lhq->alias] = array();
      }

    $s =& $this->join_tree[$lhq->alias];

    Log::dbg($s);
    $pos = -1;
    foreach ($s as $idx => $ent)
      {
	if ($ent->alias == $lhq->alias)
	  {
	    $pos = $idx;
	  }
	if ($ent->alias == $rhq->alias)
	  {
	    Log::dbg("join found alias {$rhq->alias}");
	    return ;
	  }
      }

    $rhq->join = $type;
    if ($pos != -1)
      {
	//array_push_after($s,$rhq, $pos);
	$s[] = $rhq;	
      }
    else
     {
	$s[] = $lhq;
	$s[] = $rhq;
      }    

    Log::dbg("JOIN TREE " .$pos );
    Log::dbg($s);

  }

 function add_group($attr)
  {
    $this->group = $attr->sql();
    $this->bridge->add_group($attr);
  }

 function add_order($attr, $dir)
  {
    $this->orders[$attr->name] = $dir;
    $this->bridge->add_order($attr, $dir);
  }


  function add_condition($lh, $op, $rh)
  {
    Log::dbg("add_condition($lh, $op, $rh)");
    if (is_object($lh) && $lh instanceof Attribute )
      Log::dbg("add_condition lh " . get_class($lh) . " " . $lh->name);

    if (is_object($rh) )
      Log::dbg("add_condition rh " . get_class($rh));

    $la = $lh->sql();
    if ($rh instanceof Entity )
      {
	if (array_key_exists($lh->entity->_name, $rh->_rev))
	  {
	    $rh = $rh->key();
	  }
	else if (array_key_exists($lh->entity->_name, $rh->_rel))
	  {
	    $rh = $rh->{$rh->_rel[$lh->entity->_name]};
	  }
	$this->join(null, $lh, $rh);
      }

    if ($rh instanceof Attribute )
      {
	$ra = $rh->sql();
	$sql = "{$la} $op {$ra}";
	$this->join(null, $lh, $rh);
      }
    else
      {
	$key = $this->bind($rh);
	$sql = "{$la} $op $key ";
      }

    $this->conditions[] = $sql;
  }

  function add_outer_join($lh, $op, $rh)
  {
    Log::dbg("add_outer_join($lh, $op, $rh)");
    $la = $lh->sql();
    if ($rh instanceof Attribute )
      {
	$ra = $rh->sql();
	$sql = "{$la} $op {$ra}";
      }
    else
      {
	$key = $this->bind($rh);
	$sql = "{$la} $op $key ";
      }
    $this->outer_join[] = $sql;
  }

  function bind($value, $name = "bind")
  {
    if ($value instanceof Func )
      {
	$key = $value->sql();
      }
    else
      {
	$this->value_counter = 1 + $this->value_counter;
	$key = $name . $this->value_counter;
	$this->values[":$key"] = $value;
	$key = ":{$key}";
      }
    return $key;
  }
      

  function select($e)
  {    
    $sql = $this->bridge->select($e);

    /*
    $sql = sprintf("SELECT %s %s \nFROM %s \n%s %s %s %s %s;",
		   $this->select_variant(),
		   $this->expr($e),
		   $this->refs($e),
		   $this->cond(),
		   $this->order(),
		   $this->collate(),
		   $this->group(),
		   $this->limit());
    */
    return $sql;		   
  }

  function insert($e)
  {    
    $sql = sprintf("INSERT INTO %s %s ",
		   $e->name(),
		   $this->values($e));
    
    if ($e->_on_duplicate_update)
      {
	$sql .= sprintf("ON DUPLICATE KEY UPDATE %s",
			$this->assign($e, false));
      }

    $sql .= ";";

    return $sql;		   
  }

  function update($e)
  {    
    $sql = $this->bridge->update($e);
    
    /*
    $sql = sprintf("UPDATE %s SET %s %s;",
		   $this->refs($e),
		   $this->assign($e),
		   $this->cond());
    */
    return $sql;		   
  }

  function delete($e)
  {    
    $sql = sprintf("DELETE %s.* FROM %s AS %s %s;",
		   $e->alias(),
		   $e->name(),
		   $e->alias(),
		   $this->cond($e));
    return $sql;		   
  }

  function save($e)
  {    
    $sql = $this->bridge->save($e);
    /*
    $sql = sprintf("UPDATE %s SET %s %s;",
		   $e->name()." AS " . $e->alias(),
		   $this->assign($e, false),
		   $this->pkcond($e));
    */
    return $sql;		   
  }

  function remove($e)
  {    
    $sql = sprintf("DELETE FROM %s %s;",
		   $e->name(),
		   $this->pkcond($e));
    return $sql;		   
  }


  function select_variant()
  {
    if ( array_key_exists("SELECT", $this->variant) )
      {
	return implode(" ", $this->variant["SELECT"]);
      }
    return "";

  }
  function expr($e)
  {

    $sql = "";
    $sep = "";

    Log::dbg("============================================================") ;
    foreach ($e->_etx as $name => $value)
      {
	Log::dbg("entity etx attribute $name: ") ;
	$a = $e->alias();
	$n = $e->name();
	$v = $e->_attr[$name]; 
	if ($v->is_scalar())
	  {
	    Log::dbg("  ext scalar attr " . $v->name) ;
	    $key = $this->bind($v->value);
	    $sql .= $sep.  $key . " AS {$n}__{$name}";

	  }
	else if ($v->is_func())
	  {
	    Log::dbg("  ext func attr " . $v->name) ;
	    $sql .= $sep. $v->value->sql() . " as {$n}__{$name}";
	  }
	else if ($v->is_sql())
	  {
	    Log::dbg("  ext sql attr " . $v->name) ;
	    $sql .= $sep. $v->value . " as {$n}__{$name}";
	  }
	else if ($v->is_attribute())
	  {
	    Log::dbg("  ext inactive attr " . $v->name) ;
	    $sql .= $sep. $v->sql() . " as {$n}__{$name}";
	  }
	else if ($v->active)
	  {
	    Log::dbg("  ext not attribute " . $v) ;
	    $key = $this->bind($v);
	    $sql .= $sep. "$key As {$n}__{$name}";
	  }
	$sep = ",\n\t";
      }
      
    $s = &$this->join_tree[$e->alias()];
    if ($s)
      {
	foreach ($s as $idx => $qe)
	  {
	    $esql = $qe->expr();
	    $sql .= $esql ? $sep . $esql : "";
	    $sep = ",\n\t";
	  }
	return $sql;
      }
    return $this->expr;
  }

  function col($e)
  {
    $sql = "";
    $sep = "";
    foreach ($e->_attr as $n => $a)
      {
	$sql .= $sep . $n;
	$sep = ",\n\t";
      }
    return $sql;
  }

  function values($e)
  {
    $col = "";
    $sql = "";
    $sep = "";
    foreach ($e->_attr as $n => $a)
      {
	Log::dbg($n);
	$v = $a->assign_rhs();
	$k = ($v) ? $a->name : "";

	if ($k)
	  {
	    $col .= $sep . $k;
	    $sql .= $sep . $v;
	    $sep = ", ";
	  }
      }
    return " ($col) VALUES ($sql)";
  }

  function assign($e, $alias = true)
  {
    $sql = "";
    $sep = " ";
    if ($alias !== false)
      {
	$alias = $e->alias().".";
      }
    foreach ($e->_attr as $n => $a)
      {
	$asql = "";

	$lhs = $alias.$n;
	$rhs = $a->assign_rhs();
	if ($rhs)
	  {
	    $sql .= $sep . $lhs ." = ". $rhs;
	    $sep = ",\n\t";
	  }
      }
    return $sql;
  }

  function refs($e)
  {
    $s = &$this->join_tree[$e->alias()];
    if ($s)
      {
	$sql = "";
	$lqe = null;
	foreach ($s as $idx => $qe)
	  {
	    $sql .= $qe->cond($idx-1, $s);
	    $lqe = $qe;
	  }
	return $sql;
      }
    //return $this->refs;

  }

  function cond()
  {
    $sql = "\n";
    $sep = "WHERE ";
    foreach ($this->conditions as $i => $c)
      {
	$sql .= $sep . $c;
	$sep = " AND \n\t";
      }
    return $sql;
  }

  function collate()
  {
    if ($this->collate)
      {
	return " COLLATE utf8_swedish_ci ";
      }
    return "";
  }

  function pkcond($e)
  {
    $sql = "\n";
    $sep = "WHERE ";
    foreach ($e->pk() as $n => $v)
      {
	$key = $this->bind($v, "pk");
	$sql .= $sep . $n . " = " .$key;
	$sep = " AND \n\t";
      }
    return $sql;
  }

  function order()
  {
    
    $sql = "";
    $sep = "";
    
    foreach ($this->orders as $name => $dir)
      {
	if ($name == "rand")
	  {
	    $sql .= "$sep RAND() ";	    
	  }
	else
	  {
	    $sql .= "$sep $name $dir ";
	  }
	$sep = ",\n\t";
      }

    if ($sql)
      {
	$sql = " ORDER BY $sql";
      }

    return $sql;
  }

  function limit()
  {
    if ($this->limit != null)
      {
	return " LIMIT {$this->limit} ";
      }
    return "";
  }

 function group()
  {
    if ($this->group != null)
      {
       	return " GROUP BY {$this->group} ";
      }
    return "";
  }

}

?>
