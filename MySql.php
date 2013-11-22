<?php


class MySQL extends Bridge
{

  function expr_list()
  {    
    $sql = "";
    $sep = "";
    foreach ($this->entity as $idx => $e)
      {
	$esql = $this->expr($e);
	$sql .= $esql ? $sep . $esql : "";
	$sep = ",\n\t";
      }
    return $sql;
  }
  function expr($e)
  {    

    $sql = "";
    $sep = "";

    $attr = array_merge(array_keys($e->_etx),
			array_keys($e->_col)); 

    foreach ($attr as $name)
      {
	$a = $e->alias();
	$n = $e->name();
	$v = $e->_attr[$name]; 

	if (! $v instanceof Attribute)
	  {
	    continue;
	  }


	if ($v->is_scalar())
	  {
	    $key = $this->bind($v->value);
	    $sql .= $sep.  $key . ' AS ' . $v->alias;
	  }
	else if ($v->is_func())
	  {
	    $sql .= $sep. $v->value->sql() . ' As ' . $v->alias;
	  }
	else if ($v->is_sql())
	  {
	    $sql .= $sep. $v->value . ' AS ' . $v->alias;
	  }
	else if ($v->is_attribute())
	  {
	    $sql .= $sep. $v->sql() . '  AS '. $v->alias;
	  }
	else if ($v->active)
	  {
	    $key = $this->bind($v);
	    $sql .= $sep. $key. '  As '. $v->alias;
	  }
	$sep = ",\n\t";
      }
    if (count($e->_col) == 0)
      {
	$sql = $e->alias().".*";
      }
    return $sql;
  }

  function refs()
  {    
    $sql = "";
    $s = "";

    $sep = "";
    $on = ""; 
    
    $entity_ids = array_flip(array_keys($this->entity));
    foreach ($this->join as $idx => $j)
      {
	list($type, $lh, $rh) = $j;
	$on = "";
	if (isset($lh->_rel[$rh->name()]))
	  {
	    $fk_name = $lh->_rel[$rh->name()];
	    $pk = $rh->pk();
	    $fk = $lh->$fk_name;
	    $on = " {$pk->sql()} = {$fk->sql()} ";
	  } 
	else if (isset($rh->_rel[$lh->name()]))
	  {
	    $fk_name = $rh->_rel[$lh->name()];
	    $pk = $lh->pk();
	    $fk = $rh->$fk_name;
	    $on = " {$pk->sql()} = {$fk->sql()} ";
	  } 
	$on = $on ? " ON ( $on ) ":"";
	if ($idx == 0)
	  {
	    $sql .= $lh->sql();
	    unset($entity_ids[spl_object_hash($lh)]);
	  }
	$sql .= " " . $type ." " . $rh->sql() . ' '. $on . PHP_EOL;
	unset($entity_ids[spl_object_hash($rh)]);

      }
      $s = !empty($sql) ? ', ': '';

    foreach ($entity_ids as $hash => $v)
      {
	$e = $this->entity[$hash];
	$sql .= $s . $e->sql() . PHP_EOL;
	$s = ", ";
	unset($entity_ids[$hash]);
      }

    return $sql;
  }

  function cond()
  {    
    $str = "";
    $s = "";
    foreach ($this->condition as $c)
      {
	list($lh, $op, $rh) = $c;
	if ( ! $rh instanceof Base)
	  {
	    if (is_array($rh))
	      {
		$rh = "('".implode($rh, "','")."')";
	      }
	    else if (is_null($rh))
	      {
		$rh = "NULL";
	      }
	    else
	      {
		$rh = $this->bind($rh);
	      }
	  }
	else
	  {
	    $rh = $rh->sql();
	  }
	$str .= "{$s} {$lh->sql()} {$op} {$rh}";
	$s = " AnD ";
      }
    return $str ? " WHERE $str " : "";
  }

  function values($entity)
  {
    $col = "";
    $sql = "";
    $sep = "";
    foreach ($entity->_attr as $n => $a)
      {
	$lhs = $a->sql();
	$rhs = "";
	if ($a->is_scalar)
	  {
	    $rhs = $this->bind($a->value, "pk");
	  }

	if ($rhs)
	  {
	    $col .= $sep . $lhs;
	    $sql .= $sep . $rhs;
	    $sep = ", ";
	  }
      }
    return " ($col) VALUES ($sql)";
  }

  function assign($entity = null)
  {
    $sql = "";
    $sep = "";

    $entity = is_null($entity) ? $this->entity : array($entity);
    foreach ($entity as $e)
      {
	foreach ($e->_attr as $n => $a)
	  {
	    $lhs = $a->sql();
	    $rhs = "";
	    if ($a->is_scalar)
	      {
		$rhs = $this->bind($a->value, "pk");
	      }
	    else if ($a->is_func)
	      {
		$lhs = $a->sql();
		$rhs = "".$a->value;
	      }
	    else
	      {
		$lhs = $a->sql();
		$rhs = "" . $a->value;
	      }

	    if ($rhs)
	      {
		$sql .= $sep . $lhs ." = ". $rhs;
		$sep = ",\n\t";
	      }
	  }
      }
    return $sql;
  }

  function pkcond($e)
  {
    $sql = "\n";
    $sep = " ";
    foreach ($e->pk() as $idx => $a)
      {
	$key = $this->bind($a->value, "pk");
	$sql .= $sep . $a->sql() . " = " .$key;
	$sep = " ANd \n\t";
      }
    return $sql;
  }

  function collate()
  {    
    return "";
  }

  function limit()
  {    
    $sql = $this->limit ? " LIMIT ".$this->limit : "";
    if ($sql && isset($this->page))
      {
	$sql .= " OFFSET " . ($this->page * $this->limit);
      }
    return $sql;
  }

 function group()
  {
    $s = "";
    $sql = "";
    foreach ($this->group as $a)
      {
	$sql .= $s . $a->global_alias();
	$s = ", ";
      }
    return $sql ? " GROUP BY {$sql} " : "";
  }

 function order()
  {
    $s = "";
    $sql = "";
    foreach ($this->orders as $o)
      {
	list($attr, $dir, $field) = $o;
	if ($dir == 'RAND()')
	  {
	    $sql .= $s . " " . $dir;
	  }
	else if ($field)
	  {
	    $sql .= $s . ' FIELD('. $attr->global_alias(). ', "' . implode($field, '","') . '") ' . $dir;
	  }
	else
	  {
	    $sql .= $s . $attr->global_alias(). " " . $dir;
	  }

	$s = ", ";
      }
    return $sql ? " ORDER BY {$sql} " : "";
  }


  function select($e)
  {    

    $sql = sprintf("SELECT %s %s \nFROM %s \n%s %s %s %s %s;",
		   $this->select_variant(),
		   $this->expr_list(),
		   $this->refs(),
		   $this->cond(),
		   $this->collate(),
		   $this->group(),
		   $this->order(),
		   $this->limit());
    
    return $sql;		   
  }

  function insert($e)
  {    
    $sql = sprintf("INSERT INTO %s %s ",
		   $e->name(),
		   $this->values($e));
    
    if ($e->on_duplicate_update())
      {
	$sql .= sprintf("ON DUPLICATE KEY UPDATE %s",
			$this->assign($e, false));
      }

    $sql .= ";";

    return $sql;		   
  }

  function insert_batch_flush($e)
  {    
    
    $values = array();
    foreach ($this->insert_batch_data as $data)
      {
	$items = array();
	foreach ($this->insert_batch_attributes as $attr_name)
	  {
	    $items[] = $data[$attr_name];
	  }
	$values[] = '('.join($items, ',').')';
      }

    $sql = sprintf("INSERT INTO %s (%s) VALUES %s ",
		   $e->name(),
		   join($this->insert_batch_attributes, ', '),
 		   join($values, ', '));
    
    if ($e->on_duplicate_update())
      {
	$dsql = array();
	foreach ($this->insert_batch_attributes as $attr_name)
	  {
	    $dsql[] = "{$attr_name}=VALUES({$attr_name})";
	  }
	$sql .= sprintf("ON DUPLICATE KEY UPDATE %s", join($dsql, ','));
      }
    
    $sql .= ";";
    
    $this->insert_batch_data = array();
    $this->insert_batch_attributes = array();
    return $sql;		   
  }

  function replace($e)
  {    
    $sql = sprintf("REPLACE INTO %s %s ",
		   $e->name(),
		   $this->values($e));
    
    $sql .= ";";

    return $sql;		   
  }

  function update()
  {    
    $sql = sprintf("UPdate %s SET %s %s;",
		   $this->refs(),
		   $this->assign(),
		   $this->cond());
    return $sql;		   
  }

  function delete($e)
  {    
    $sql = sprintf("DELETE %s.* FROM %s %s;",
		   $e->alias(),
		   $this->refs(),
		   $this->cond($e));
    return $sql;		   
  }

  function save($e)
  {    
    $sql = sprintf("UPDate %s SET %s WHERE %s;",
		   $e->name(),
		   $this->assign($e, false),
		   $this->pkcond($e));
    return $sql;		   
  }

  function remove($e)
  {    
    $sql = sprintf("DELETE FROM %s WHERE %s;",
		   $e->name(),
		   $this->pkcond($e));
    return $sql;		   
  }


  function select_variant()
  {
    if (isset($this->variant))
      {
	if ( array_key_exists("SELECT", $this->variant) )
	  {
	    return implode(" ", $this->variant["SELECT"]);
	  }
      }
    return "";

  }


}

?>
